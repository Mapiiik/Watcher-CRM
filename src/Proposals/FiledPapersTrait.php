<?php
declare(strict_types=1);

namespace App\Proposals;

use App\Model\Enum\DocumentVariant;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Locator\LocatorAwareTrait;
use Files\Model\Table\FileLinksTable;

/**
 * Reading back what is filed against a kind of proposal.
 *
 * Both agendas ask the same questions of the same table and differ only in what they are asking
 * about, so the questions live here. What a using class says for itself is which model its papers
 * hang on and which documents it knows.
 */
trait FiledPapersTrait
{
    use LocatorAwareTrait;

    /**
     * The documents this agenda draws up, by the value they are filed under.
     *
     * @return array<string, string>
     */
    abstract public function documentLabels(): array;

    /**
     * What papers a handful of proposals have, sorted into the shape a page reads them in.
     *
     * Asked of the whole handful at once, so that a customer with six proposals is one query
     * rather than six.
     *
     * @param iterable<\Cake\Datasource\EntityInterface> $proposals Whose papers.
     * @return array<string, array<string, array<string, list<\Files\Model\Entity\FileLink>>>>
     *   By proposal, then by document, then by variant.
     */
    public function filedAgainst(iterable $proposals): array
    {
        $keys = [];
        foreach ($proposals as $proposal) {
            $keys[] = (string)$proposal->get('id');
        }

        /** @var iterable<\Files\Model\Entity\FileLink> $found */
        $found = $this->fileLinks()
            ->find('forAny', model: static::MODEL, foreign_keys: $keys)
            ->contain(['Files'])
            ->all();

        $filed = [];
        foreach ($found as $link) {
            $filed[$link->foreign_key][$link->document_type][$link->variant][] = $link;
        }

        return $filed;
    }

    /**
     * The documents a proposal has actually been drawn up as.
     *
     * Only those, because nothing else can have come back. That is what makes offering them on
     * the signature page short enough to be worth having there at all.
     *
     * @param \Cake\Datasource\EntityInterface $proposal Whose papers.
     * @return array<string, string> The document type, and how it reads.
     */
    public function printedTypes(EntityInterface $proposal): array
    {
        $labels = $this->documentLabels();
        $printed = [];

        foreach ($this->filedAgainst([$proposal])[(string)$proposal->get('id')] ?? [] as $document_type => $byVariant) {
            if (!isset($labels[$document_type])) {
                continue;
            }

            foreach (array_keys($byVariant) as $variant) {
                if (DocumentVariant::tryFrom((string)$variant)?->isGeneratedByUs() ?? false) {
                    $printed[$document_type] = $labels[$document_type];
                    break;
                }
            }
        }

        return $printed;
    }

    /**
     * @return \Files\Model\Table\FileLinksTable
     */
    protected function fileLinks(): FileLinksTable
    {
        /** @var \Files\Model\Table\FileLinksTable $links */
        $links = $this->fetchTable(FileLinksTable::class);

        return $links;
    }
}
