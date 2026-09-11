<?php
declare(strict_types=1);

namespace Files\Service;

use Cake\Core\Configure;
use Cake\ORM\Locator\LocatorAwareTrait;
use Files\Model\Entity\File;
use Files\Model\Entity\FileLink;
use Files\Model\Table\FileLinksTable;
use Files\Model\Table\FilesTable;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\Local\LocalFilesystemAdapter;
use RuntimeException;
use Throwable;

/**
 * The way in and out of the store.
 *
 * Content is addressed by what is in it: the same bytes arriving twice are written once and
 * pointed at twice, and the path they are written to is derived from their hash rather than
 * from anything anybody typed. That is what makes a second copy free, and what makes putting
 * an archive back over an existing one a no-op instead of a conflict.
 *
 * Nothing here reads a whole file into a string unless it is handed one. Scans arrive as
 * hundreds of megabytes and there is no reason for any of it to pass through memory.
 */
class FileStorage
{
    use LocatorAwareTrait;

    /**
     * How many characters of the hash each level of the path takes, and how many levels there
     * are. Two levels of two keeps any one directory to a few hundred entries however much is
     * stored, which is what the tools that walk it want.
     */
    private const PATH_SEGMENT = 2;
    private const PATH_DEPTH = 2;

    /**
     * @param \League\Flysystem\FilesystemOperator|null $filesystem Where the bytes live. Built
     *   from the configuration when nothing is passed, which is every case but a test.
     */
    public function __construct(private ?FilesystemOperator $filesystem = null)
    {
    }

    /**
     * Puts content in the store and hands back the row that stands for it.
     *
     * For what is already in memory - a document the application has just drawn.
     *
     * @param string $bytes The content.
     * @param string $mime_type What kind of content it is.
     * @return \Files\Model\Entity\File
     * @throws \RuntimeException When the content cannot be written or recorded.
     */
    public function store(string $bytes, string $mime_type): File
    {
        return $this->keep(
            hash(FilesTable::HASH_ALGORITHM, $bytes),
            strlen($bytes),
            $mime_type,
            function (string $path) use ($bytes): void {
                $this->filesystem()->write($path, $bytes);
            },
        );
    }

    /**
     * The same, for content that is already a file.
     *
     * For what has been uploaded. The bytes are hashed and copied without ever being held whole:
     * a form carrying five scanned pages would otherwise cost a gigabyte of memory for nothing.
     *
     * @param string $path The file to take.
     * @param string $mime_type What kind of content it is.
     * @return \Files\Model\Entity\File
     * @throws \RuntimeException When the file cannot be read, written or recorded.
     */
    public function storeFile(string $path, string $mime_type): File
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new RuntimeException(sprintf('There is nothing to store at %s.', $path));
        }

        $hash = hash_file(FilesTable::HASH_ALGORITHM, $path);
        if ($hash === false) {
            throw new RuntimeException(sprintf('Could not read %s.', $path));
        }

        return $this->keep(
            $hash,
            (int)filesize($path),
            $mime_type,
            function (string $target) use ($path): void {
                $handle = fopen($path, 'rb');
                if ($handle === false) {
                    throw new RuntimeException(sprintf('Could not open %s.', $path));
                }

                try {
                    $this->filesystem()->writeStream($target, $handle);
                } finally {
                    if (is_resource($handle)) {
                        fclose($handle);
                    }
                }
            },
        );
    }

    /**
     * Records that some record has a use for this content.
     *
     * @param \Files\Model\Entity\File $file The content.
     * @param string $model What kind of record has it.
     * @param string $foreign_key Which one.
     * @param string $document_type Which document it is.
     * @param string $variant Which variant of that document this is.
     * @param array<string, mixed> $options `position`, `name` and `meta`, where they are known.
     * @return \Files\Model\Entity\FileLink
     * @throws \RuntimeException When the link cannot be recorded.
     */
    public function link(
        File $file,
        string $model,
        string $foreign_key,
        string $document_type,
        string $variant,
        array $options = [],
    ): FileLink {
        $links = $this->fileLinks();

        $link = $links->newEntity([
            'model' => $model,
            'foreign_key' => $foreign_key,
            'document_type' => $document_type,
            'variant' => $variant,
            'position' => $options['position'] ?? $this->nextPosition($model, $foreign_key, $document_type, $variant),
            'name' => $options['name'] ?? null,
            'meta' => $options['meta'] ?? [],
        ]);
        $link->set('file_id', $file->id);

        if (!$links->save($link)) {
            throw new RuntimeException(
                'The file could not be linked to the record: ' . json_encode($link->getErrors()),
            );
        }

        return $link;
    }

    /**
     * Lets go of one use of some content, and of the content itself when it was the last.
     *
     * The row goes before the bytes. The other way round leaves a row pointing at nothing, which
     * cannot be put right; this way the worst case is bytes nobody asked for, which can.
     *
     * @param \Files\Model\Entity\FileLink $link The use to let go of.
     * @return void
     * @throws \RuntimeException When the link cannot be removed.
     */
    public function unlink(FileLink $link): void
    {
        $links = $this->fileLinks();

        if (!$links->delete($link)) {
            throw new RuntimeException('The file could not be unlinked from the record.');
        }

        $remaining = $links->find()->where(['file_id' => $link->file_id])->count();
        if ($remaining > 0) {
            return;
        }

        $files = $this->files();
        $file = $files->find()->where(['id' => $link->file_id])->first();

        if (!$file instanceof File) {
            return;
        }

        $files->delete($file);
        $this->filesystem()->delete($file->path);
    }

    /**
     * The content, as a string.
     *
     * @param \Files\Model\Entity\File $file The content.
     * @return string
     */
    public function read(File $file): string
    {
        return $this->filesystem()->read($file->path);
    }

    /**
     * The content, as something to read through.
     *
     * @param \Files\Model\Entity\File $file The content.
     * @return resource
     */
    public function readStream(File $file)
    {
        return $this->filesystem()->readStream($file->path);
    }

    /**
     * Whether the bytes a row stands for are actually stored.
     *
     * @param \Files\Model\Entity\File $file The content.
     * @return bool
     */
    public function has(File $file): bool
    {
        return $this->filesystem()->fileExists($file->path);
    }

    /**
     * Where the bytes live.
     *
     * @return \League\Flysystem\FilesystemOperator
     */
    public function filesystem(): FilesystemOperator
    {
        return $this->filesystem ??= new Filesystem(new LocalFilesystemAdapter(self::root()));
    }

    /**
     * The root the store is kept under.
     *
     * Its own setting, falling back to a place under the data root - which is where a deployment
     * already keeps everything else that is not the database.
     *
     * @return string
     */
    public static function root(): string
    {
        $root = Configure::read('Files.root');

        if (is_string($root) && $root !== '') {
            return $root;
        }

        return (string)Configure::read('Data.root') . DS . 'files';
    }

    /**
     * Where content of this hash is written.
     *
     * @param string $hash The hash.
     * @return string
     */
    public static function pathFor(string $hash): string
    {
        $segments = [];
        for ($level = 0; $level < self::PATH_DEPTH; $level++) {
            $segments[] = substr($hash, $level * self::PATH_SEGMENT, self::PATH_SEGMENT);
        }
        $segments[] = $hash;

        return implode('/', $segments);
    }

    /**
     * Writes the bytes and records them, unless this content is already on file.
     *
     * @param string $hash The hash of the content.
     * @param int $byte_size How much of it there is.
     * @param string $mime_type What kind of content it is.
     * @param callable $write What puts the bytes where they go.
     * @return \Files\Model\Entity\File
     * @throws \RuntimeException When the content cannot be written or recorded.
     */
    private function keep(string $hash, int $byte_size, string $mime_type, callable $write): File
    {
        $existing = $this->onFile($hash);
        if ($existing instanceof File) {
            // The bytes may have gone missing under it - a restore taken of the wrong moment, a
            // hand in the wrong directory - so this is also where that is put right.
            if (!$this->has($existing)) {
                $write($existing->path);
            }

            return $existing;
        }

        $path = self::pathFor($hash);
        $write($path);

        $files = $this->files();
        $file = $files->newEntity(['mime_type' => $mime_type]);
        $file->set('hash', $hash);
        $file->set('hash_type', FilesTable::HASH_ALGORITHM);
        $file->set('byte_size', $byte_size);
        $file->set('path', $path);

        try {
            if (!$files->save($file)) {
                throw new RuntimeException('The content could not be recorded.');
            }
        } catch (Throwable $e) {
            // Two requests storing the same content at the same moment: the unique index lets
            // one of them through and the other finds it here, which is the right answer anyway.
            $raced = $this->onFile($hash);
            if ($raced instanceof File) {
                return $raced;
            }

            throw new RuntimeException('The content could not be recorded: ' . $e->getMessage(), 0, $e);
        }

        return $file;
    }

    /**
     * The row for this content, where there is one.
     *
     * Only what was worked out the way we work it out today. Content hashed by an algorithm we
     * have since left behind is a row that will not be found here, and will be written again
     * under the new one - which is how a change of algorithm gets made, a file at a time.
     *
     * @param string $hash The hash.
     * @return \Files\Model\Entity\File|null
     */
    private function onFile(string $hash): ?File
    {
        /** @var \Files\Model\Entity\File|null $file */
        $file = $this->files()
            ->find('byHash', hash: $hash, hash_type: FilesTable::HASH_ALGORITHM)
            ->first();

        return $file;
    }

    /**
     * Where the next page of a group goes.
     *
     * @param string $model What kind of record.
     * @param string $foreign_key Which one.
     * @param string $document_type Which document.
     * @param string $variant Which variant of that document this is.
     * @return int
     */
    private function nextPosition(string $model, string $foreign_key, string $document_type, string $variant): int
    {
        // Asked for as one number rather than by reading the group's last row. The group finder
        // sorts the pages into reading order, and a sort added to that is appended rather than
        // applied instead, so asking it for the last row hands back the first one.
        $links = $this->fileLinks();
        $query = $links->find();

        $highest = $query
            ->select(['highest' => $query->func()->max($links->aliasField('position'))])
            ->where([
                $links->aliasField('model') => $model,
                $links->aliasField('foreign_key') => $foreign_key,
                $links->aliasField('document_type') => $document_type,
                $links->aliasField('variant') => $variant,
            ])
            ->disableHydration()
            ->first();

        $position = is_array($highest) ? $highest['highest'] ?? null : null;

        return $position === null ? 0 : (int)$position + 1;
    }

    /**
     * @return \Files\Model\Table\FilesTable
     */
    private function files(): FilesTable
    {
        /** @var \Files\Model\Table\FilesTable $files */
        $files = $this->fetchTable(FilesTable::class);

        return $files;
    }

    /**
     * @return \Files\Model\Table\FileLinksTable
     */
    private function fileLinks(): FileLinksTable
    {
        /** @var \Files\Model\Table\FileLinksTable $links */
        $links = $this->fetchTable(FileLinksTable::class);

        return $links;
    }
}
