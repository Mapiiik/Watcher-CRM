<?php
declare(strict_types=1);

namespace Files\Controller;

use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Files\Model\Table\FileLinksTable;
use Files\Service\FileStorage;
use Files\Service\Previews;
use Files\Service\Viewable;
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
     * What a picture is handed over as. One kind whatever it was made from, which is the point
     * of making it: a HEIC from a phone comes back as something a browser will show.
     *
     * @var string
     */
    private const SENT_AS = 'webp';

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
     * A small picture of what is filed, for saying which page this is.
     *
     * @param string|null $id File link id.
     * @return \Cake\Http\Response
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function thumbnail(?string $id = null): Response
    {
        return $this->generated($id, Previews::THUMBNAIL);
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
        return $this->generated($id, Previews::PREVIEW);
    }

    /**
     * A picture of the content, either size.
     *
     * By the link rather than by the content, so that whoever may look at a document is exactly
     * whoever may look at a picture of it. The picture itself is shared between the links, which
     * is the storage's business and not the door's.
     *
     * @param string|null $id File link id.
     * @param string $size Which size.
     * @return \Cake\Http\Response
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     * @throws \Cake\Http\Exception\NotFoundException When there is no picture to be had.
     */
    private function generated(?string $id, string $size): Response
    {
        $link = $this->fileLinks()->get($id, contain: ['Files']);

        $picture = (new Previews())->generate($link->file, $size);
        $handle = $picture === null ? false : fopen($picture, 'rb');

        if ($handle === false) {
            throw new NotFoundException(__d('files', 'There is no picture of this document.'));
        }

        // A picture answers for content that cannot change - the store is addressed by what is
        // in it - so it is worth keeping for as long as the browser will. Privately, though: it
        // is behind a login, and a shared cache has no business handing it to the next person.
        return $this->getResponse()
            ->withType(self::SENT_AS)
            ->withHeader('Cache-Control', 'private, max-age=31536000, immutable')
            ->withBody(new Stream($handle));
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
