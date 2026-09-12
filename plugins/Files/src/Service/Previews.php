<?php
declare(strict_types=1);

namespace Files\Service;

use Cake\Core\Configure;
use Cake\Log\Log;
use Files\Model\Entity\File;
use Imagick;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * A picture of what is filed, for looking at before the thing itself is opened.
 *
 * Two sizes, because they answer different questions: a thumbnail says which page this is, and a
 * preview is the page being read. Neither is the document - whoever wants that still asks for it.
 *
 * Made when it is asked for, and ahead of that by `generate_previews` from cron. Making them as
 * the files arrive would put the wait on whoever uploaded them, and a round of thirty photographs
 * is thirty conversions to sit through before the form comes back.
 *
 * Kept beside the store rather than in it. The store answers "what is on file", and everything
 * under it is content some row stands for - which is what lets `check_files` call anything else
 * it finds there bytes nobody has a row for. A preview has no row and never will, so it lives
 * next door, where deleting the lot costs nothing but the making again.
 *
 * Keyed by the content rather than by the use of it: the same scan filed against five records is
 * one picture rather than five, which is the whole point of a store that keeps content once.
 */
class Previews
{
    /**
     * The two sizes, by the name they are asked for under.
     */
    public const THUMBNAIL = 'thumb';
    public const PREVIEW = 'preview';

    /**
     * How large each may be, in pixels along its longer side.
     *
     * A thumbnail sits in a strip or a column and only has to be recognisable. A preview is being
     * read, so it is large enough that a scanned page can be - but not so large that it costs
     * what the page itself would.
     *
     * @var array<string, int>
     */
    private const BOXES = [
        self::THUMBNAIL => 240,
        self::PREVIEW => 1600,
    ];

    /**
     * What there is any point trying to make a picture of.
     *
     * The same list the images allow ImageMagick to open, which is deliberate: that policy lives
     * in `docker/imagemagick-policy.xml`, and asking here for anything it refuses would only earn
     * a refusal further down. The two need changing together.
     *
     * Wider than what a browser will draw by itself, which is the point of the exercise: a photo
     * from a phone arrives as HEIC and a scanner sends TIFF, and neither shows without this.
     *
     * @var list<string>
     */
    private const MADE_FROM = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/heic',
        'image/heif',
        'image/avif',
        'image/tiff',
        'application/pdf',
    ];

    /**
     * What comes out, and how hard it is squeezed. WebP because it is smaller than JPEG at the
     * same quality, and every browser that can run the viewer can show it.
     */
    private const FORMAT = 'webp';
    private const EXTENSION = '.webp';
    private const QUALITY = 82;

    /**
     * What a page of a PDF is rendered at. Enough that the small print on a scanned contract can
     * be read at the preview size, and no more.
     */
    private const PDF_RESOLUTION = 150;

    /**
     * What making one may take. The policy in the image is the ceiling and this is the ask - it
     * can only come out lower, never higher, so a deployment that wants less says so there.
     */
    private const MEMORY_LIMIT = 268435456;
    private const MAP_LIMIT = 536870912;

    /**
     * How the path is cut up, the same way the store cuts its own: two levels of two characters
     * keeps any one directory to a few hundred entries however much is kept.
     */
    private const PATH_SEGMENT = 2;
    private const PATH_DEPTH = 2;

    /**
     * @param \Files\Service\FileStorage|null $storage Where the content is read from. Built when
     *   nothing is passed, which is every case but a test.
     */
    public function __construct(private ?FileStorage $storage = null)
    {
    }

    /**
     * A small picture of the content, or null where there is none to be had.
     *
     * @param \Files\Model\Entity\File $file The content.
     * @return string|null The file it went into.
     */
    public function thumbnail(File $file): ?string
    {
        return $this->generate($file, self::THUMBNAIL);
    }

    /**
     * A large one.
     *
     * @param \Files\Model\Entity\File $file The content.
     * @return string|null The file it went into.
     */
    public function preview(File $file): ?string
    {
        return $this->generate($file, self::PREVIEW);
    }

    /**
     * Either size, made if it has not been already.
     *
     * Null rather than an exception for anything there is no picture of, because that is an
     * answer and not a fault: a spreadsheet has none, and neither does a file this build of
     * ImageMagick cannot read. Whoever asked gets to decide what to show instead.
     *
     * @param \Files\Model\Entity\File $file The content.
     * @param string $size Which of the two.
     * @return string|null The file it went into.
     * @throws \InvalidArgumentException When asked for a size there is not.
     */
    public function generate(File $file, string $size): ?string
    {
        if (!isset(self::BOXES[$size])) {
            throw new InvalidArgumentException(sprintf('`%s` is not a size a preview comes in.', $size));
        }

        if (!self::generates($file->mime_type ?? null)) {
            return null;
        }

        $target = self::pathFor((string)$file->id, $size);
        if (is_file($target)) {
            return $target;
        }

        if (!extension_loaded('imagick')) {
            return null;
        }

        try {
            $this->write($file, $size, $target);
        } catch (Throwable $e) {
            // One file that will not convert is not a reason to fail the page it was asked from.
            // It is worth knowing about, though: a deployment where nothing converts looks the
            // same from the outside as one with nothing to convert.
            Log::warning(sprintf('Files: no %s of %s: %s', $size, $file->id, $e->getMessage()));

            return null;
        }

        return $target;
    }

    /**
     * Whether this kind of content is something a picture can be made of.
     *
     * @param string|null $mime_type What kind of content it is.
     * @return bool
     */
    public static function generates(?string $mime_type): bool
    {
        return $mime_type !== null && in_array($mime_type, self::MADE_FROM, true);
    }

    /**
     * The root they are kept under.
     *
     * Its own setting, falling back to a place beside the store under the data root.
     *
     * @return string
     */
    public static function root(): string
    {
        $root = Configure::read('Files.previews');

        if (is_string($root) && $root !== '') {
            return $root;
        }

        return (string)Configure::read('Data.root') . DS . 'files-previews';
    }

    /**
     * Where the picture of one file at one size goes.
     *
     * @param string $id Which content.
     * @param string $size Which of the two sizes.
     * @return string
     */
    public static function pathFor(string $id, string $size): string
    {
        $segments = [self::root(), $size];
        for ($level = 0; $level < self::PATH_DEPTH; $level++) {
            $segments[] = substr($id, $level * self::PATH_SEGMENT, self::PATH_SEGMENT);
        }

        return implode(DS, $segments) . DS . $id . self::EXTENSION;
    }

    /**
     * Makes it and puts it where it goes.
     *
     * @param \Files\Model\Entity\File $file The content.
     * @param string $size Which of the two.
     * @param string $target Where it goes.
     * @return void
     * @throws \RuntimeException When it cannot be made or written.
     */
    private function write(File $file, string $size, string $target): void
    {
        $this->makeDirectory(dirname($target));

        $page = $this->page($file);

        try {
            // Only ever made smaller. Asking for a 1600 pixel preview of a 400 pixel photograph
            // would hand back a blurred copy several times the size of the thing it came from.
            $box = self::BOXES[$size];
            if ($page->getImageWidth() > $box || $page->getImageHeight() > $box) {
                $page->thumbnailImage($box, $box, true);
            }

            $page->setImageFormat(self::FORMAT);
            $page->setImageCompressionQuality(self::QUALITY);

            // Where the photograph was taken is none of the viewer's business, and it would ride
            // along otherwise.
            $page->stripImage();

            $this->put($page, $target);
        } finally {
            $page->clear();
        }
    }

    /**
     * Writes it beside where it goes and moves it into place.
     *
     * Two people opening the same document at the same moment both make one. Written straight to
     * where it goes, the second would be reading the first one's half-written file.
     *
     * @param \Imagick $page What came out.
     * @param string $target Where it goes.
     * @return void
     * @throws \RuntimeException When it cannot be written or moved.
     */
    private function put(Imagick $page, string $target): void
    {
        $part = $target . '.' . uniqid('', true) . '.part';

        if (!$page->writeImage($part)) {
            throw new RuntimeException(sprintf('Nothing was written to %s.', $part));
        }

        if (!rename($part, $target)) {
            unlink($part);

            throw new RuntimeException(sprintf('Could not move it into %s.', $target));
        }
    }

    /**
     * The one page of the content that the picture is made of.
     *
     * @param \Files\Model\Entity\File $file The content.
     * @return \Imagick
     * @throws \RuntimeException When the content cannot be read.
     */
    private function page(File $file): Imagick
    {
        Imagick::setResourceLimit(Imagick::RESOURCETYPE_MEMORY, self::MEMORY_LIMIT);
        Imagick::setResourceLimit(Imagick::RESOURCETYPE_MAP, self::MAP_LIMIT);

        $imagick = new Imagick();

        if ($file->mime_type === 'application/pdf') {
            return $this->pageOfPaper($imagick, $file);
        }

        $handle = $this->storage()->readStream($file);

        try {
            $imagick->readImageFile($handle);
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }
        }

        // A TIFF from a scanner can hold several pages and a photograph can hold more than one
        // picture. The first of them is the one being shown.
        $imagick->setIteratorIndex(0);
        $page = $imagick->getImage();
        $imagick->clear();

        // Which way up the camera was held is written in the file rather than in the pixels, and
        // stripping that above would otherwise leave the picture on its side. The extension
        // renamed the method along the way and both names are still out there, so it is asked
        // for by whichever one this build answers to.
        foreach (['autoOrient', 'autoOrientImage'] as $turn) {
            if (method_exists($page, $turn)) {
                $page->{$turn}();

                break;
            }
        }

        return $page;
    }

    /**
     * The first page of a PDF.
     *
     * Which page is said on the end of the filename, so the bytes have to be a file rather than
     * something to read through - and the resolution has to be set before it is read, because it
     * is what the page is rendered at and not something done to it afterwards.
     *
     * @param \Imagick $imagick What renders it.
     * @param \Files\Model\Entity\File $file The content.
     * @return \Imagick
     * @throws \RuntimeException When the content cannot be read.
     */
    private function pageOfPaper(Imagick $imagick, File $file): Imagick
    {
        $imagick->setResolution(self::PDF_RESOLUTION, self::PDF_RESOLUTION);

        $scratch = $this->scratch($file);

        try {
            $imagick->readImage($scratch . '[0]');
        } finally {
            unlink($scratch);
        }

        // A paper is read on paper. Left alone, everything the page did not put ink on comes out
        // black, which is most of a contract.
        $imagick->setImageBackgroundColor('white');
        $page = $imagick->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
        $imagick->clear();

        return $page;
    }

    /**
     * The content as a file of its own, for the things that will only take one.
     *
     * Beside the pictures rather than in the system's temporary directory, which on these
     * deployments is in memory - and a scan is not something to put there.
     *
     * @param \Files\Model\Entity\File $file The content.
     * @return string The file, for the caller to delete.
     * @throws \RuntimeException When it cannot be written.
     */
    private function scratch(File $file): string
    {
        $directory = self::root() . DS . '.scratch';
        $this->makeDirectory($directory);

        $path = $directory . DS . uniqid('', true);

        $source = $this->storage()->readStream($file);
        $target = fopen($path, 'wb');

        if ($target === false) {
            if (is_resource($source)) {
                fclose($source);
            }

            throw new RuntimeException(sprintf('Could not open %s to write.', $path));
        }

        try {
            if (stream_copy_to_stream($source, $target) === false) {
                throw new RuntimeException(sprintf('Could not copy the content into %s.', $path));
            }
        } finally {
            if (is_resource($source)) {
                fclose($source);
            }
            fclose($target);
        }

        return $path;
    }

    /**
     * @param string $directory The directory to have.
     * @return void
     * @throws \RuntimeException When it cannot be made.
     */
    private function makeDirectory(string $directory): void
    {
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException(sprintf('Could not make %s.', $directory));
        }
    }

    /**
     * @return \Files\Service\FileStorage
     */
    private function storage(): FileStorage
    {
        return $this->storage ??= new FileStorage();
    }
}
