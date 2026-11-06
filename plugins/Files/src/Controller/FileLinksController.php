<?php
declare(strict_types=1);

namespace Files\Controller;

use Cake\Http\Response;
use Files\Controller\Trait\HandsOverContentTrait;
use Files\Model\Entity\FileLink;
use Files\Model\Table\FileLinksTable;
use Files\Service\FileStorage;
use Files\Service\Previews;
use Throwable;

/**
 * Documents Controller
 *
 * What is filed against what, which is the question somebody comes here with.
 */
class FileLinksController extends AppController
{
    use HandsOverContentTrait;

    /**
     * The table this reads.
     *
     * Said outright rather than taken from the controller's own name, because the controllers
     * here are named for the questions they answer and not for the tables they ask. That is what
     * lets several of them read the same table - this one is every use of some content, and one
     * that wants only the photographs will be another - and it keeps the path from saying "files"
     * twice over into the bargain.
     *
     * @var string|null
     */
    protected ?string $defaultTable = 'Files.FileLinks';

    /**
     * Index method
     *
     * @return void Renders view
     */
    public function index(): void
    {
        $conditions = [];

        foreach (['model', 'document_type', 'variant'] as $field) {
            $value = $this->getRequest()->getQuery($field);
            if (is_string($value) && $value !== '') {
                $conditions[$this->fileLinks()->aliasField($field)] = $value;
            }
        }

        $search = $this->getRequest()->getQuery('search');
        if (is_string($search) && trim($search) !== '') {
            $conditions[$this->fileLinks()->aliasField('name') . ' ILIKE'] = '%' . trim($search) . '%';
        }

        $this->paginate = [
            'order' => [
                'created' => 'DESC',
            ],
        ];

        $documents = $this->paginate($this->fileLinks()->find(
            'all',
            contain: ['Files'],
            conditions: $conditions,
        ));

        $this->set(compact('documents'));
        $this->set('models', $this->distinct('model'));
        $this->set('documentTypes', $this->distinct('document_type'));
        $this->set('variants', $this->distinct('variant'));
    }

    /**
     * Hands the content over under the name it arrived with.
     *
     * By the link rather than by the content, because the name belongs to the link: the same
     * bytes filed twice may well have arrived called two different things.
     *
     * @param string|null $id File link id.
     * @return \Cake\Http\Response
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function download(?string $id = null): Response
    {
        return $this->handOver($this->withItsFile($id), false);
    }

    /**
     * Hands the content over to be looked at rather than kept.
     *
     * Most of what is filed is looked at once to check it is the right paper, which is a step
     * shorter without it landing in a folder first.
     *
     * @param string|null $id File link id.
     * @return \Cake\Http\Response
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function open(?string $id = null): Response
    {
        return $this->handOver($this->withItsFile($id), true);
    }

    /**
     * A small picture of what is filed, for saying which page this is.
     *
     * @param string|null $id File link id.
     * @return \Cake\Http\Response
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function thumbnail(?string $id = null): Response
    {
        return $this->pictureOf($this->withItsFile($id), Previews::THUMBNAIL);
    }

    /**
     * A large one, for reading the page rather than recognising it.
     *
     * @param string|null $id File link id.
     * @return \Cake\Http\Response
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function preview(?string $id = null): Response
    {
        return $this->pictureOf($this->withItsFile($id), Previews::PREVIEW);
    }

    /**
     * Moves one use of some content to the trash.
     *
     * @param string|null $id File link id.
     * @return \Cake\Http\Response|null Redirects back to where it was asked from.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $this->getRequest()->allowMethod(['post', 'delete']);

        $link = $this->fileLinks()->get($id);

        try {
            (new FileStorage())->unlink($link);
            $this->Flash->success(__d('files', 'The document has been removed.'));
        } catch (Throwable $e) {
            $this->Flash->error(__d('files', 'The document could not be removed: {0}', $e->getMessage()));
        }

        return $this->redirect($this->referer(['action' => 'index'], true));
    }

    /**
     * The values a column actually holds, for the filters to offer.
     *
     * Read from what is on file rather than from a list, so that a plugin which knows nothing
     * about the application's vocabulary can still offer it.
     *
     * @param string $field The column.
     * @return array<string, string>
     */
    private function distinct(string $field): array
    {
        $values = $this->fileLinks()->find()
            ->select([$field])
            ->distinct([$this->fileLinks()->aliasField($field)])
            ->orderBy([$this->fileLinks()->aliasField($field) => 'ASC'])
            ->all()
            ->extract($field)
            ->toList();

        return array_combine($values, $values);
    }

    /**
     * The link, with the content it points at.
     *
     * @param string|null $id File link id.
     * @return \Files\Model\Entity\FileLink
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    private function withItsFile(?string $id): FileLink
    {
        return $this->fileLinks()->get($id, contain: ['Files']);
    }

    /**
     * @return \Files\Model\Table\FileLinksTable
     */
    private function fileLinks(): FileLinksTable
    {
        /** @var \Files\Model\Table\FileLinksTable $links */
        $links = $this->fetchTable();

        return $links;
    }
}
