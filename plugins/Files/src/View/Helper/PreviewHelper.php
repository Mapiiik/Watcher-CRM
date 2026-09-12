<?php
declare(strict_types=1);

namespace Files\View\Helper;

use Cake\View\Helper;
use Files\Model\Entity\FileLink;
use Files\Service\Previews;
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
     * What the group is called is handed over rather than worked out here. A record is a model
     * and a key to this plugin and nothing more, so which contract it belongs to and what it is
     * for are the application's to say.
     *
     * @param list<\Files\Model\Entity\FileLink> $links The pages, in the order they read.
     * @param string $gallery What tells this group apart from the others on the page.
     * @param string $caption What the group is, in the application's own words.
     * @param int $startAt Which page it opens on.
     * @return string The mark, or an empty string where there is nothing to show.
     */
    public function flipThrough(array $links, string $gallery, string $caption = '', int $startAt = 0): string
    {
        $pages = $this->pages($links, $caption);
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
     * One page of a group, as a picture of itself that opens the group at it.
     *
     * For a listing that shows what each page looks like. The group is not repeated here - the
     * mark only names it, and the viewer finds the pages on whichever mark carries them. A table
     * of five pages would otherwise carry the same list five times over.
     *
     * The whole run is handed over rather than the one page, because which page of the viewer
     * this is depends on the others: the viewer only holds the ones it can show, and a run may
     * hold a scan in a format we can draw but no browser can. Such a page still gets its picture,
     * and the picture is a plain link to the file, because there is no slide to open at.
     *
     * Empty where there is no picture to be had, so that a listing leaves the cell alone rather
     * than drawing a broken one.
     *
     * @param list<\Files\Model\Entity\FileLink> $links The pages of the run, in the order they read.
     * @param int $at Which of them this is.
     * @param string $gallery What the run is called.
     * @return string
     */
    public function pageMark(array $links, int $at, string $gallery): string
    {
        $link = $links[$at] ?? null;
        if ($link === null || !Previews::generates($link->file->mime_type ?? null)) {
            return '';
        }

        $picture = $this->Html->image(
            [
                'plugin' => 'Files',
                'controller' => 'Documents',
                'action' => 'thumbnail',
                $link->id,
            ],
            [
                'alt' => '',
                'loading' => 'lazy',
                'class' => 'files-thumb',
            ],
        );

        $options = ['target' => '_blank', 'rel' => 'noopener', 'escape' => false];

        $where = $this->positionOf($links, $at);
        if ($where !== null) {
            $options['class'] = 'files-viewer';
            $options['data-files-gallery'] = $gallery;
            $options['data-files-start'] = $where;
        }

        return $this->Html->link(
            $picture,
            [
                'plugin' => 'Files',
                'controller' => 'Documents',
                'action' => 'open',
                $link->id,
            ],
            $options,
        );
    }

    /**
     * Which page of the viewer one of a run is, or null where it is not one of them at all.
     *
     * @param list<\Files\Model\Entity\FileLink> $links The pages of the run.
     * @param int $at Which of them.
     * @return int|null
     */
    private function positionOf(array $links, int $at): ?int
    {
        $shown = 0;

        foreach ($links as $index => $link) {
            $shows = Viewable::typeOf($link->file->mime_type ?? null) !== null;

            if ($index === $at) {
                return $shows ? $shown : null;
            }

            if ($shows) {
                $shown++;
            }
        }

        return null;
    }

    /**
     * What the viewer needs to know about each page it can show.
     *
     * Only what can be shown goes in. A file the browser would download rather than draw has no
     * place in a gallery, and leaving it out is better than a slide that stays blank. The pages
     * are counted after that, so the count is of what can actually be turned to.
     *
     * @param list<\Files\Model\Entity\FileLink> $links The pages.
     * @param string $caption What the group is, in the application's own words.
     * @return list<array<string, string>>
     */
    private function pages(array $links, string $caption): array
    {
        $shown = [];

        foreach ($links as $link) {
            $type = Viewable::typeOf($link->file->mime_type ?? null);
            if ($type !== null) {
                $shown[] = [$link, $type];
            }
        }

        $pages = [];
        $of = count($shown);

        foreach ($shown as $at => [$link, $type]) {
            $pages[] = [
                'href' => $this->Url->build([
                    'plugin' => 'Files',
                    'controller' => 'Documents',
                    'action' => 'open',
                    $link->id,
                ]),
                'type' => $type,
                'title' => $caption,
                'description' => $this->descriptionOf($link, $at + 1, $of),
                // What the strip under the page shows. The address is given whether or not there
                // is anything at the end of it yet - the picture is made the first time it is
                // asked for, and a strip that waited for the making would show nothing at all.
                'thumb' => $this->Url->build([
                    'plugin' => 'Files',
                    'controller' => 'Documents',
                    'action' => 'thumbnail',
                    $link->id,
                ]),
            ];
        }

        return $pages;
    }

    /**
     * Which file that page is, when it was filed, and which of how many it is.
     *
     * The line under the name, and the only part of it this plugin is in a position to know. It
     * is written as markup because the viewer puts it on the page as markup, which is also what
     * lets the count sit at the far end of the line instead of trailing the filename.
     *
     * Which of how many is only said where there is more than one, since `1/1` tells nobody
     * anything they did not already know.
     *
     * @param \Files\Model\Entity\FileLink $link The page.
     * @param int $at Which page this is.
     * @param int $of How many there are.
     * @return string
     */
    private function descriptionOf(FileLink $link, int $at, int $of): string
    {
        // Written the way the application writes every other moment. Which format that is, is a
        // matter for the installation and not for this plugin - and a scan filed in a batch is
        // told apart from its neighbours by the seconds, so they are not taken away here.
        $filed = (string)$link->created;
        $named = $filed === '' ? $link->downloadName() : $link->downloadName() . ' (' . $filed . ')';

        $said = '<span class="files-file">' . h($named) . '</span>';
        if ($of > 1) {
            $said .= '<span class="files-page">' . h($at . '/' . $of) . '</span>';
        }

        return $said;
    }
}
