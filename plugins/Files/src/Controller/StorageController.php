<?php
declare(strict_types=1);

namespace Files\Controller;

use Files\Model\Table\FilesTable;
use Files\Service\FileStorage;

/**
 * Storage Controller
 *
 * What is actually on the shelf, each piece of content once - including the pieces nothing points
 * at any more, which is the whole reason this is a page of its own rather than a filter on the
 * documents.
 */
class StorageController extends AppController
{
    /**
     * The table this reads.
     *
     * Said outright because it cannot be worked out: the controller is named for the question it
     * answers rather than for the table it asks.
     *
     * @var string|null
     */
    protected ?string $defaultTable = 'Files.Files';

    /**
     * Index method
     *
     * @return void Renders view
     */
    public function index(): void
    {
        $query = $this->files()->find(contain: ['FileLinks']);

        $mime = $this->getRequest()->getQuery('mime_type');
        if (is_string($mime) && $mime !== '') {
            $query->where([$this->files()->aliasField('mime_type') => $mime]);
        }

        // The beginning of a hash is enough to find something by, and it is what a check or a
        // log names when it has something to complain about.
        $search = $this->getRequest()->getQuery('search');
        if (is_string($search) && trim($search) !== '') {
            $query->where([$this->files()->aliasField('hash') . ' LIKE' => trim($search) . '%']);
        }

        if ($this->getRequest()->getQuery('unused') === '1') {
            $query->find('unused');
        }

        $this->paginate = [
            'order' => [
                'created' => 'DESC',
            ],
        ];

        $this->set('files', $this->paginate($query));
        $this->set('mimeTypes', $this->mimeTypes());
        $this->set('totals', $this->totals());
    }

    /**
     * View method
     *
     * @param string|null $id File id.
     * @return void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null): void
    {
        $file = $this->files()->get($id, contain: ['FileLinks', 'Creators']);

        $this->set('file', $file);
        $this->set('onTheShelf', (new FileStorage())->has($file));
    }

    /**
     * How much there is of it, which is the figure the operator came for.
     *
     * @return array<string, int>
     */
    private function totals(): array
    {
        $query = $this->files()->find();
        $totals = $query
            ->select([
                'files' => $query->func()->count('*'),
                'bytes' => $query->func()->coalesce([$query->func()->sum('byte_size'), 0]),
            ])
            ->disableHydration()
            ->first();

        return [
            'files' => (int)(is_array($totals) ? $totals['files'] ?? 0 : 0),
            'bytes' => (int)(is_array($totals) ? $totals['bytes'] ?? 0 : 0),
            'unused' => $this->files()->find('unused')->count(),
        ];
    }

    /**
     * The kinds of content on the shelf, for the filter to offer.
     *
     * @return array<string, string>
     */
    private function mimeTypes(): array
    {
        $files = $this->files();

        $values = $files->find()
            ->select(['mime_type'])
            ->distinct([$files->aliasField('mime_type')])
            ->orderBy([$files->aliasField('mime_type') => 'ASC'])
            ->all()
            ->extract('mime_type')
            ->toList();

        return array_combine($values, $values);
    }

    /**
     * @return \Files\Model\Table\FilesTable
     */
    private function files(): FilesTable
    {
        /** @var \Files\Model\Table\FilesTable $files */
        $files = $this->fetchTable();

        return $files;
    }
}
