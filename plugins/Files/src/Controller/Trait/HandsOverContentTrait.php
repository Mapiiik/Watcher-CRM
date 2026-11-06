<?php
declare(strict_types=1);

namespace Files\Controller\Trait;

use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Files\Model\Entity\FileLink;
use Files\Service\FileStorage;
use Files\Service\Previews;
use Files\Service\Viewable;
use Laminas\Diactoros\Stream;

/**
 * Handing the content of a link over, as itself or as a picture of it.
 *
 * Whichever door it leaves through, it leaves the same way.
 */
trait HandsOverContentTrait
{
    /**
     * What a picture is handed over as. One kind whatever it was made from, which is the point
     * of making it: a HEIC from a phone comes back as something a browser will show.
     *
     * @var string
     */
    private const SENT_AS = 'webp';

    /**
     * What the content is, is what we say it is.
     *
     * Everything handed out here came from outside, and a folder takes whatever the work
     * produced - so the kind is whatever the bytes turned out to be rather than anything we
     * chose. A browser left to guess can decide a file is something else entirely and run it,
     * which is the one thing a download must never become.
     */
    private const NEVER_GUESSED = ['X-Content-Type-Options' => 'nosniff'];

    /**
     * A picture of the content, either size.
     *
     * By the link rather than by the content, so that whoever may look at a document is exactly
     * whoever may look at a picture of it. The picture itself is shared between the links, which
     * is the storage's business and not the door's.
     *
     * @param \Files\Model\Entity\FileLink $link What is filed, with its file.
     * @param string $size Which size.
     * @return \Cake\Http\Response
     * @throws \Cake\Http\Exception\NotFoundException When there is no picture to be had.
     */
    private function pictureOf(FileLink $link, string $size): Response
    {
        $picture = (new Previews())->generate($link->file, $size);
        $handle = $picture === null ? false : fopen($picture, 'rb');

        if ($handle === false) {
            throw new NotFoundException(__d('files', 'There is no picture of this document.'));
        }

        // A picture answers for content that cannot change - the store is addressed by what is
        // in it - so it is worth keeping for as long as the browser will. Privately, though: it
        // is behind a login, and a shared cache has no business handing it to the next person.
        return $this->told($this->getResponse())
            ->withType(self::SENT_AS)
            ->withHeader('Cache-Control', 'private, max-age=31536000, immutable')
            ->withBody(new Stream($handle));
    }

    /**
     * The content, either way round.
     *
     * @param \Files\Model\Entity\FileLink $link What is filed, with its file.
     * @param bool $inline Whether to offer it for looking at rather than for keeping.
     * @return \Cake\Http\Response
     * @throws \Cake\Http\Exception\NotFoundException When the content is not in the store.
     */
    private function handOver(FileLink $link, bool $inline): Response
    {
        $storage = new FileStorage();

        if (!$storage->has($link->file)) {
            throw new NotFoundException(__d('files', 'The content of this document is not in the store.'));
        }

        $response = $this->told($this->getResponse())->withType($link->file->mime_type);

        // Anything the browser would run rather than draw is handed over to be kept, whatever was
        // asked for. The content came from outside, so opening it in our own origin would be
        // handing a stranger the session.
        $response = $inline && Viewable::opens($link->file->mime_type)
            ? $response->withHeader(
                'Content-Disposition',
                'inline; filename="' . str_replace('"', '', $link->downloadName()) . '"',
            )
            : $response->withDownload($link->downloadName());

        // Handed over as a stream: a scan runs to hundreds of megabytes and there is no reason
        // for any of it to pass through memory on the way out.
        return $response->withBody(new Stream($storage->readStream($link->file)));
    }

    /**
     * The response with the headers every piece of content leaves under.
     *
     * @param \Cake\Http\Response $response What is going out.
     * @return \Cake\Http\Response
     */
    private function told(Response $response): Response
    {
        foreach (self::NEVER_GUESSED as $header => $value) {
            $response = $response->withHeader($header, $value);
        }

        return $response;
    }
}
