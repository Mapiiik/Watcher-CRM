<?php
declare(strict_types=1);

namespace Files\Controller;

use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Files\Model\Table\FileLinksTable;
use Files\Service\FileStorage;
use Laminas\Diactoros\Stream;
use Throwable;

/**
 * Documents Controller
 *
 * What is filed against what, which is the question somebody comes here with.
 */
class DocumentsController extends AppController
{
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
     * What the browser may be asked to draw in place of handing it to the operator.
     *
     * A list of what is allowed rather than of what is not, because the content came from outside
     * and anything the browser would run instead of draw would run in our own origin. Kept here
     * rather than beside the application's own list of what may be uploaded: that one says what
     * is worth filing, this one says what is safe to open, and they answer to different things.
     *
     * @var array<string>
     */
    private const OPENS_SAFELY = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
    ];

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
        return $this->hand($id, false);
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
        return $this->hand($id, true);
    }

    /**
     * The content, either way round.
     *
     * @param string|null $id File link id.
     * @param bool $inline Whether to offer it for looking at rather than for keeping.
     * @return \Cake\Http\Response
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    private function hand(?string $id, bool $inline): Response
    {
        $link = $this->fileLinks()->get($id, contain: ['Files']);

        $storage = new FileStorage();

        if (!$storage->has($link->file)) {
            throw new NotFoundException(__d('files', 'The content of this document is not in the store.'));
        }

        $response = $this->getResponse()->withType($link->file->mime_type);

        // Anything the browser would run rather than draw is handed over to be kept, whatever was
        // asked for. The content came from outside, so opening it in our own origin would be
        // handing a stranger the session.
        $response = $inline && in_array($link->file->mime_type, self::OPENS_SAFELY, true)
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
     * Lets go of one use of some content. The bytes go with the last of them.
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
     * @return \Files\Model\Table\FileLinksTable
     */
    private function fileLinks(): FileLinksTable
    {
        /** @var \Files\Model\Table\FileLinksTable $links */
        $links = $this->fetchTable();

        return $links;
    }
}
