<?php
declare(strict_types=1);

namespace Files\Controller\Trait;

use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Response;
use Files\Model\Entity\FileLink;
use Files\Model\Table\FileLinksTable;
use Files\Service\Documentations;
use Files\Service\FiledPages;
use Throwable;

/**
 * The whole agenda of documentation, in both applications.
 *
 * What a folder hangs on is a column each application adds for itself, and the route it is
 * reached under names the very same thing - so what is listed follows from where the caller is
 * standing, and nothing has to be mapped from one to the other. Without any nesting the listing
 * is of everything there is.
 *
 * That is also what keeps a folder from being hung on somebody else's record: it is filed under
 * what the route named, and the route is what the permission was asked about.
 *
 * @property \Cake\ORM\Table $Documentations
 * @method \Cake\Http\Response|null afterAddRedirect(array|string $url)
 * @method \Cake\Http\Response|null afterEditRedirect(array|string $url)
 * @method \Cake\Http\Response|null afterDeleteRedirect(array|string $url)
 * @method void flashValidationErrors(array $errors)
 * @method array<mixed> dataWithNesting(\Cake\ORM\Table $table, array<mixed> $data)
 * @psalm-require-extends \Cake\Controller\Controller
 */
trait DocumentationsControllerTrait
{
    /**
     * Which records the route is standing under, by the column that names each of them.
     *
     * Only what the route actually carried. A listing under a customer is of everything filed
     * against them, their connections included, because a folder filed under a connection carries
     * the customer as well.
     *
     * @return array<string, string>
     */
    abstract protected function filedUnder(): array;

    /**
     * What a folder is read together with.
     *
     * @return array<mixed>
     */
    abstract protected function viewContain(): array;

    /**
     * Index method
     *
     * @return void Renders view
     */
    public function index(): void
    {
        $conditions = [];

        foreach ($this->filedUnder() as $column => $id) {
            $conditions[$this->Documentations->aliasField($column)] = $id;
        }

        $kind = $this->getRequest()->getQuery('documentation_type_id');
        if (!empty($kind)) {
            $conditions[$this->Documentations->aliasField('documentation_type_id')] = $kind;
        }

        $search = $this->getRequest()->getQuery('search');
        if (!empty($search)) {
            $conditions[] = [
                'OR' => [
                    'Documentations.name ILIKE' => '%' . trim((string)$search) . '%',
                    'Documentations.note ILIKE' => '%' . trim((string)$search) . '%',
                ],
            ];
        }

        // The standing folders first and the rest newest downwards, which is what the finder does
        // and what the index on the table is shaped for.
        $documentations = $this->paginate(
            $this->Documentations->find('inReadingOrder')
                ->contain($this->viewContain())
                ->where($conditions),
            ['order' => []],
        );

        $this->set(compact('documentations'));
        $this->set('kinds', $this->kindsOnOffer());
    }

    /**
     * View method
     *
     * @param string|null $id Documentation id.
     * @return void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null): void
    {
        $documentation = $this->Documentations->get($id, contain: $this->viewContain());

        $this->set(compact('documentation'));
    }

    /**
     * Add method
     *
     * What it hangs on comes from the route rather than from the form, so a folder cannot be
     * filed against a record the caller was not standing under.
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add(): ?Response
    {
        $documentation = $this->Documentations->newEmptyEntity();

        if ($this->getRequest()->is('post')) {
            $documentation = $this->Documentations->patchEntity(
                $documentation,
                $this->dataWithNesting($this->Documentations, $this->getRequest()->getData()),
            );

            if ($this->Documentations->save($documentation)) {
                $this->Flash->success(__d('files', 'The documentation has been saved.'));

                return $this->afterAddRedirect(['action' => 'view', $documentation->id]);
            }

            $this->flashValidationErrors($documentation->getErrors());
            $this->Flash->error(__d('files', 'The documentation could not be saved. Please, try again.'));
        }

        $this->set(compact('documentation'));
        $this->set('kinds', $this->kindsOnOffer());

        return null;
    }

    /**
     * Edit method
     *
     * What it hangs on is not among what may be changed. A folder belongs where it was filed, and
     * a route naming somewhere else must not quietly move it there.
     *
     * @param string|null $id Documentation id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null): ?Response
    {
        $documentation = $this->Documentations->get($id, contain: []);

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $documentation = $this->Documentations->patchEntity($documentation, $this->getRequest()->getData());

            if ($this->Documentations->save($documentation)) {
                $this->Flash->success(__d('files', 'The documentation has been saved.'));

                return $this->afterEditRedirect(['action' => 'view', $documentation->id]);
            }

            $this->flashValidationErrors($documentation->getErrors());
            $this->Flash->error(__d('files', 'The documentation could not be saved. Please, try again.'));
        }

        $this->set(compact('documentation'));
        $this->set('kinds', $this->kindsOnOffer((string)$documentation->documentation_type_id));

        return null;
    }

    /**
     * Delete method
     *
     * What the folder holds goes with it, and the bytes go with the last thing that wanted them.
     * That is the table's doing rather than this one's, so a folder taken away with the record it
     * hung on behaves the same.
     *
     * @param string|null $id Documentation id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $this->getRequest()->allowMethod(['post', 'delete']);

        $documentation = $this->Documentations->get($id);

        if ($this->Documentations->delete($documentation)) {
            $this->Flash->success(__d('files', 'The documentation has been deleted.'));
        } else {
            $this->flashValidationErrors($documentation->getErrors());
            $this->Flash->error(__d('files', 'The documentation could not be deleted. Please, try again.'));
        }

        return $this->afterDeleteRedirect(['action' => 'index']);
    }

    /**
     * Files what was uploaded into the folder.
     *
     * @param string|null $id Documentation id.
     * @return \Cake\Http\Response|null Redirects when filed, renders the form otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function addFiles(?string $id = null): ?Response
    {
        /** @var \Files\Model\Entity\Documentation $documentation */
        $documentation = $this->Documentations->get($id, contain: $this->viewContain());

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $files = $this->getRequest()->getUploadedFiles()['files'] ?? [];

            try {
                $filed = is_array($files)
                    ? (new Documentations())->take($documentation, array_values($files))
                    : 0;

                // Said whether or not anything was filed: the ones that did arrive are filed and
                // the rest were never here, so the person is the only one who can tell.
                if (FiledPages::cutShort($this->getRequest()->getUploadedFiles())) {
                    $this->Flash->warning(FiledPages::shortfall());
                }

                if ($filed > 0) {
                    $this->Flash->success(__dn(
                        'files',
                        '{0} file has been filed.',
                        '{0} files have been filed.',
                        $filed,
                        $filed,
                    ));

                    return $this->redirect(['action' => 'view', $id]);
                }

                $this->Flash->error(__d('files', 'Nothing was chosen to file.'));
            } catch (Throwable $e) {
                $this->Flash->error($e->getMessage());
            }
        }

        $this->set(compact('documentation'));

        return null;
    }

    /**
     * Lets go of one of the folder's contents.
     *
     * @param string|null $id Documentation id.
     * @param string|null $link_id Which of them.
     * @return \Cake\Http\Response|null Redirects back to the folder.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function dropFile(?string $id = null, ?string $link_id = null): ?Response
    {
        $this->getRequest()->allowMethod(['post', 'delete']);

        try {
            (new Documentations())->drop($this->theFile($id, $link_id));
            $this->Flash->success(__d('files', 'The file has been removed from the documentation.'));
        } catch (Throwable $e) {
            $this->Flash->error(__d('files', 'The file could not be removed: {0}', $e->getMessage()));
        }

        return $this->redirect(['action' => 'view', $id]);
    }

    /**
     * Moves one of the folder's contents past the one beside it.
     *
     * @param string|null $id Documentation id.
     * @param string|null $link_id Which of them.
     * @param string|null $direction Which way - `up` or anything else for down.
     * @return \Cake\Http\Response|null Redirects back to the folder.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function moveFile(?string $id = null, ?string $link_id = null, ?string $direction = null): ?Response
    {
        $this->getRequest()->allowMethod(['post', 'put']);

        try {
            (new Documentations())->move($this->theFile($id, $link_id), $direction === 'up');
        } catch (Throwable $e) {
            $this->Flash->error(__d('files', 'The files could not be reordered: {0}', $e->getMessage()));
        }

        return $this->redirect(['action' => 'view', $id]);
    }

    /**
     * One of this folder's contents.
     *
     * The folder is checked as well as the file, so that an identifier from somewhere else cannot
     * reach a file through this door.
     *
     * @param string|null $id Documentation id.
     * @param string|null $link_id Which of them.
     * @return \Files\Model\Entity\FileLink
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When it is not this folder's.
     */
    private function theFile(?string $id, ?string $link_id): FileLink
    {
        /** @var \Files\Model\Table\FileLinksTable $links */
        $links = $this->fetchTable(FileLinksTable::class);

        /** @var \Files\Model\Entity\FileLink $link */
        $link = $links->get($link_id);

        if ($link->model !== Documentations::MODEL || $link->foreign_key !== $id) {
            throw new RecordNotFoundException(__d('files', 'That file belongs to something else.'));
        }

        return $link;
    }

    /**
     * The documentation types somebody may choose from.
     *
     * @param string|null $including The type a documentation already carries, whatever it says
     *   about itself.
     * @return array<string, string>
     */
    private function kindsOnOffer(?string $including = null): array
    {
        /** @var \Cake\ORM\Table $types */
        $types = $this->Documentations->getAssociation('DocumentationTypes')->getTarget();

        /** @var array<string, string> $offered */
        $offered = $types->find('offered', including: $including)->find('list')->toArray();

        return $offered;
    }
}
