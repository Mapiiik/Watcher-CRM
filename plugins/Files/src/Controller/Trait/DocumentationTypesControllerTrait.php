<?php
declare(strict_types=1);

namespace Files\Controller\Trait;

use Cake\Http\Response;

/**
 * The actions a documentation type has, in both applications.
 *
 * A short list somebody edits rarely and reads often, so it is the plainest sort of agenda. What
 * the two applications disagree about is only what a type may ask the documentation under it to
 * be filed under, which is a column each of them adds for itself - so the views are the application's and
 * everything here is the same either side.
 *
 * @property \Cake\ORM\Table $DocumentationTypes
 * @method \Cake\Http\Response|null afterAddRedirect(array|string $url)
 * @method \Cake\Http\Response|null afterEditRedirect(array|string $url)
 * @method \Cake\Http\Response|null afterDeleteRedirect(array|string $url)
 * @method void flashValidationErrors(array $errors)
 * @psalm-require-extends \Cake\Controller\Controller
 */
trait DocumentationTypesControllerTrait
{
    /**
     * What a documentation type is read together with on its own page.
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

        $search = $this->getRequest()->getQuery('search');
        if (!empty($search)) {
            $conditions[] = [
                'OR' => [
                    'DocumentationTypes.name ILIKE' => '%' . trim((string)$search) . '%',
                    'DocumentationTypes.note ILIKE' => '%' . trim((string)$search) . '%',
                ],
            ];
        }

        // The order somebody put them in rather than the alphabet, because that order is the whole
        // point of the column.
        $this->paginate = [
            'order' => [
                'position' => 'ASC',
                'name' => 'ASC',
            ],
        ];

        $documentationTypes = $this->paginate($this->DocumentationTypes->find(
            'all',
            conditions: $conditions,
        ));

        $this->set(compact('documentationTypes'));
    }

    /**
     * View method
     *
     * @param string|null $id Documentation type id.
     * @return void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null): void
    {
        $documentationType = $this->DocumentationTypes->get($id, contain: $this->viewContain());

        $this->set(compact('documentationType'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add(): ?Response
    {
        $documentationType = $this->DocumentationTypes->newEmptyEntity();

        if ($this->getRequest()->is('post')) {
            $documentationType = $this->DocumentationTypes->patchEntity(
                $documentationType,
                $this->getRequest()->getData(),
            );

            if ($this->DocumentationTypes->save($documentationType)) {
                $this->Flash->success(__d('files', 'The documentation type has been saved.'));

                return $this->afterAddRedirect(['action' => 'view', $documentationType->id]);
            }

            $this->flashValidationErrors($documentationType->getErrors());
            $this->Flash->error(__d('files', 'The documentation type could not be saved. Please, try again.'));
        }

        $this->set(compact('documentationType'));

        return null;
    }

    /**
     * Edit method
     *
     * @param string|null $id Documentation type id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null): ?Response
    {
        $documentationType = $this->DocumentationTypes->get($id, contain: []);

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $documentationType = $this->DocumentationTypes->patchEntity(
                $documentationType,
                $this->getRequest()->getData(),
            );

            if ($this->DocumentationTypes->save($documentationType)) {
                $this->Flash->success(__d('files', 'The documentation type has been saved.'));

                return $this->afterEditRedirect(['action' => 'view', $documentationType->id]);
            }

            $this->flashValidationErrors($documentationType->getErrors());
            $this->Flash->error(__d('files', 'The documentation type could not be saved. Please, try again.'));
        }

        $this->set(compact('documentationType'));

        return null;
    }

    /**
     * Delete method
     *
     * A type with documentation under it will not go, and that is the table's doing rather than
     * this one's. Taking it off the list is how a type is retired.
     *
     * @param string|null $id Documentation type id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $this->getRequest()->allowMethod(['post', 'delete']);

        $documentationType = $this->DocumentationTypes->get($id);

        if ($this->DocumentationTypes->delete($documentationType)) {
            $this->Flash->success(__d('files', 'The documentation type has been deleted.'));
        } else {
            $this->flashValidationErrors($documentationType->getErrors());
            $this->Flash->error(__d('files', 'The documentation type could not be deleted. Please, try again.'));
        }

        return $this->afterDeleteRedirect(['action' => 'index']);
    }
}
