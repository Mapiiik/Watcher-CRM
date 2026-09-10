<?php
declare(strict_types=1);

namespace Files\View\Helper;

use Cake\Core\Configure;
use Cake\View\Helper;
use Files\Model\Entity\FileLink;

/**
 * The way to the record a document is filed against.
 *
 * The plugin knows a record by the name of its model and nothing more, so what that name leads to
 * is the application's to say - `Files.records` maps each one to a page. A name nobody declared
 * gets no link, which is better than a guess landing somewhere that is not there.
 *
 * @property \CakeDC\Users\View\Helper\AuthLinkHelper $AuthLink
 * @extends \Cake\View\Helper<\App\View\AppView>
 */
class RecordHelper extends Helper
{
    /**
     * Helpers used by this one.
     *
     * @var array<string>
     */
    protected array $helpers = ['CakeDC/Users.AuthLink'];

    /**
     * Names the record, as a link to it where there is one to offer.
     *
     * @param \Files\Model\Entity\FileLink $link What is filed.
     * @return string
     */
    public function linkTo(FileLink $link): string
    {
        $url = $this->urlFor($link);
        // Nothing is drawn where the operator may not go, and an empty cell would say less than
        // the plain name does.
        $drawn = $url === null ? '' : $this->AuthLink->link((string)$link->model, $url);

        return $drawn !== '' ? $drawn : h((string)$link->model);
    }

    /**
     * The page that record is on, where the application has said there is one.
     *
     * @param \Files\Model\Entity\FileLink $link What is filed.
     * @return array<string, mixed>|null The URL, or null where that model leads nowhere.
     */
    public function urlFor(FileLink $link): ?array
    {
        $declared = Configure::read('Files.records');
        $url = is_array($declared) ? $declared[$link->model] ?? null : null;

        if (!is_array($url) || $link->foreign_key === null) {
            return null;
        }

        $url[] = $link->foreign_key;

        return $url;
    }
}
