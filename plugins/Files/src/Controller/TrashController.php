<?php
declare(strict_types=1);

namespace Files\Controller;

use Cake\Http\Response;
use Files\Controller\Trait\HandsOverContentTrait;
use Files\Model\Entity\RemovedFileLink;
use Files\Model\Table\RemovedFileLinksTable;
use Files\Service\Previews;
use Files\Service\Trash;
use Throwable;

/**
 * Trash Controller
 *
 * What somebody removed: who, when and from what, a look at it, and the way back.
 */
class TrashController extends AppController
{
    use HandsOverContentTrait;

    /**
     * The table this reads.
     *
     * Said outright because it cannot be worked out: the controller is named for the question it
     * answers rather than for the table it asks.
     *
     * @var string|null
     */
    protected ?string $defaultTable = 'Files.RemovedFileLinks';

    /**
     * Index method
     *
     * @return void Renders view
     */
    public function index(): void
    {
        $removed = $this->removedFileLinks();
        $conditions = [];

        $model = $this->getRequest()->getQuery('model');
        if (is_string($model) && $model !== '') {
            $conditions[$removed->aliasField('model')] = $model;
        }

        $search = $this->getRequest()->getQuery('search');
        if (is_string($search) && trim($search) !== '') {
            $conditions[$removed->aliasField('name') . ' ILIKE'] = '%' . trim($search) . '%';
        }

        $this->paginate = [
            'order' => [
                'removed' => 'DESC',
            ],
        ];

        $documents = $this->paginate($removed->find(
            'all',
            contain: ['Files', 'Removers'],
            conditions: $conditions,
        ));

        // Asked here so that the page can say why instead of offering a button that would refuse.
        $trash = new Trash();
        $stops = [];
        foreach ($documents as $document) {
            $stops[(string)$document->id] = $trash->whatStopsIt($document);
        }

        $models = $removed->find()
            ->select(['model'])
            ->distinct([$removed->aliasField('model')])
            ->orderBy([$removed->aliasField('model') => 'ASC'])
            ->all()
            ->extract('model')
            ->toList();

        $this->set(compact('documents', 'stops'));
        $this->set('models', array_combine($models, $models));
    }

    /**
     * Hands the content over under the name it arrived with.
     *
     * @param string|null $id Removed file link id.
     * @return \Cake\Http\Response
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function download(?string $id = null): Response
    {
        return $this->handOver($this->withItsFile($id), false);
    }

    /**
     * Hands the content over to be looked at, to tell whether it is the one to bring back.
     *
     * @param string|null $id Removed file link id.
     * @return \Cake\Http\Response
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function open(?string $id = null): Response
    {
        return $this->handOver($this->withItsFile($id), true);
    }

    /**
     * A small picture of what was removed.
     *
     * @param string|null $id Removed file link id.
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
     * @param string|null $id Removed file link id.
     * @return \Cake\Http\Response
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function preview(?string $id = null): Response
    {
        return $this->pictureOf($this->withItsFile($id), Previews::PREVIEW);
    }

    /**
     * Puts it back, along with whatever went in the same act.
     *
     * @param string|null $id Removed file link id.
     * @return \Cake\Http\Response|null Redirects back to the trash.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function restore(?string $id = null): ?Response
    {
        $this->getRequest()->allowMethod(['post']);

        $link = $this->removedFileLinks()->get($id);

        try {
            $restored = (new Trash())->restore($link);
            $this->Flash->success(__dn(
                'files',
                'The file has been restored.',
                '{0} files have been restored.',
                $restored,
                $restored,
            ));
        } catch (Throwable $e) {
            $this->Flash->error(__d('files', 'The file could not be restored: {0}', $e->getMessage()));
        }

        return $this->redirect($this->referer(['action' => 'index'], true));
    }

    /**
     * Throws it away for good.
     *
     * @param string|null $id Removed file link id.
     * @return \Cake\Http\Response|null Redirects back to the trash.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $this->getRequest()->allowMethod(['post', 'delete']);

        $link = $this->removedFileLinks()->get($id);

        try {
            if ((new Trash())->delete($link)) {
                $this->Flash->success(__d('files', 'The file has been deleted for good.'));
            } else {
                $this->Flash->warning(__d(
                    'files',
                    'Removed from the trash. The content stays, as another record still uses it.',
                ));
            }
        } catch (Throwable $e) {
            $this->Flash->error(__d('files', 'The file could not be deleted: {0}', $e->getMessage()));
        }

        return $this->redirect($this->referer(['action' => 'index'], true));
    }

    /**
     * What was removed, with the content it points at.
     *
     * @param string|null $id Removed file link id.
     * @return \Files\Model\Entity\RemovedFileLink
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    private function withItsFile(?string $id): RemovedFileLink
    {
        return $this->removedFileLinks()->get($id, contain: ['Files']);
    }

    /**
     * @return \Files\Model\Table\RemovedFileLinksTable
     */
    private function removedFileLinks(): RemovedFileLinksTable
    {
        /** @var \Files\Model\Table\RemovedFileLinksTable $removed */
        $removed = $this->fetchTable();

        return $removed;
    }
}
