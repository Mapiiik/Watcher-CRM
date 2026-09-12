<?php
declare(strict_types=1);

namespace Files\View\Helper;

use Cake\View\Helper;
use Files\Service\Viewable;

/**
 * Looking through the pages of a document without leaving the page they are listed on.
 *
 * A helper rather than a trait or a base class: this is markup, and the application pages that
 * want it have nothing else in common. Whoever holds a group of pages asks for one mark, and the
 * mark carries the whole group with it.
 *
 * The group travels as JSON on the element rather than as one hidden anchor per page. A table of
 * three documents would otherwise carry a dozen anchors nobody can see, and the strip of
 * thumbnails wants the list in one piece anyway.
 *
 * Where the viewer cannot run, the mark is a plain link to the first page - which is what the
 * table did before any of this.
 *
 * The viewer itself is fetched by load(), which the page asks for before it draws the table. It
 * cannot be asked for from inside the table: that is a cell, a cell renders in a view of its own,
 * and a script asked for there lands in a block the layout never reads.
 *
 * @property \Cake\View\Helper\HtmlHelper $Html
 * @property \Cake\View\Helper\UrlHelper $Url
 * @extends \Cake\View\Helper<\App\View\AppView>
 */
class PreviewHelper extends Helper
{
    /**
     * Helpers used by this one.
     *
     * @var array<string>
     */
    protected array $helpers = ['Html', 'Url'];

    /**
     * Whether this page has asked for the viewer already.
     */
    private bool $loaded = false;

    /**
     * Fetches the viewer, for a page that is about to draw documents.
     *
     * Asked for by the page rather than by the table, because the table is a cell and a cell
     * renders in a view of its own - what it puts in a block, the layout never sees. Asking twice
     * costs nothing, so a page drawing both sides of its papers need not keep track.
     *
     * A page that forgets loses the overlay and nothing else: the mark is still a link, and it
     * still opens the document.
     *
     * @return void
     */
    public function load(): void
    {
        if ($this->loaded) {
            return;
        }

        $this->loaded = true;

        $this->Html->css(
            'https://cdn.jsdelivr.net/npm/glightbox@3.3/dist/css/glightbox.min.css',
            ['block' => true],
        );
        $this->Html->script(
            'https://cdn.jsdelivr.net/npm/glightbox@3.3/dist/js/glightbox.min.js',
            ['block' => true],
        );
        $this->Html->script('Files.viewer', ['block' => true]);

        // After the viewer's own, so that what it puts right stays put right.
        $this->Html->css('Files.viewer', ['block' => true]);
    }

    /**
     * A mark that opens the pages of one document, one after another.
     *
     * @param list<\Files\Model\Entity\FileLink> $links The pages, in the order they read.
     * @param string $gallery What tells this group apart from the others on the page.
     * @param int $startAt Which page it opens on.
     * @return string The mark, or an empty string where there is nothing to show.
     */
    public function flipThrough(array $links, string $gallery, int $startAt = 0): string
    {
        $pages = $this->pages($links);
        if ($pages === []) {
            return '';
        }

        return $this->Html->link(
            __d('files', 'Look through ({0})', count($pages)),
            $pages[0]['href'],
            [
                'class' => 'files-viewer',
                'data-files-gallery' => $gallery,
                'data-files-pages' => json_encode($pages),
                'data-files-start' => $startAt,
                'target' => '_blank',
                'rel' => 'noopener',
                'escape' => true,
            ],
        );
    }

    /**
     * What the viewer needs to know about each page it can show.
     *
     * Only what can be shown goes in. A file the browser would download rather than draw has no
     * place in a gallery, and leaving it out is better than a slide that stays blank.
     *
     * @param list<\Files\Model\Entity\FileLink> $links The pages.
     * @return list<array<string, string>>
     */
    private function pages(array $links): array
    {
        $pages = [];

        foreach ($links as $link) {
            $type = Viewable::typeOf($link->file->mime_type ?? null);
            if ($type === null) {
                continue;
            }

            $pages[] = [
                'href' => $this->Url->build([
                    'plugin' => 'Files',
                    'controller' => 'Documents',
                    'action' => 'open',
                    $link->id,
                ]),
                'type' => $type,
                'title' => $link->downloadName(),
            ];
        }

        return $pages;
    }
}
