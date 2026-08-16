<?php
declare(strict_types=1);

namespace App\Controller;

use App\Application;
use App\Model\Entity\AppUser;
use Cake\Console\CommandRunner;
use Cake\Http\Response;

/**
 * Labels Controller
 *
 * @property \App\Model\Table\LabelsTable $Labels
 */
class LabelsController extends AppController
{
    /**
     * Index method
     *
     * @return void Renders view
     */
    public function index(): void
    {
        // filter
        $conditions = [];

        // search
        $search = $this->getRequest()->getQuery('search');
        if (!empty($search)) {
            $conditions[] = [
                'OR' => [
                    'Labels.name ILIKE' => '%' . trim((string)$search) . '%',
                    'Labels.caption ILIKE' => '%' . trim((string)$search) . '%',
                    'Labels.dynamic_sql ILIKE' => '%' . trim((string)$search) . '%',
                ],
            ];
        }

        $this->paginate = [
            'order' => [
                'name' => 'ASC',
            ],
        ];
        $labels = $this->paginate($this->Labels->find(
            'all',
            contain: [
                'CustomerLabels',
            ],
            conditions: $conditions,
        ));

        $this->set(compact('labels'));
    }

    /**
     * View method
     *
     * @param string|null $id Label id.
     * @return void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null): void
    {
        $label = $this->Labels->get($id, contain: [
            'CustomerLabels' => [
                'Customers',
                'Contracts',
            ],
            'Creators',
            'Modifiers',
        ]);

        $this->set(compact('label'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add(): ?Response
    {
        $label = $this->Labels->newEmptyEntity();
        if ($this->getRequest()->is('post')) {
            $label = $this->Labels->patchEntity($label, $this->getRequest()->getData());
            // check if not bad SQL
            if ($this->isBadSQL($this->getRequest()->getData('dynamic_sql'))) {
                // bad SQL - set error
                $label->setError(
                    'dynamic_sql',
                    __('An expression for data modification was detected in the SQL query, which is forbidden.'),
                );
            } elseif ($this->Labels->save($label)) {
                // good SQL - proceed
                $this->Flash->success(__('The label has been saved.'));

                return $this->afterAddRedirect(['action' => 'view', $label->id]);
            }
            $this->Flash->error(__('The label could not be saved. Please, try again.'));
        }
        $roles = (new AppUser())->getRoleOptions();
        $this->set(compact('label', 'roles'));

        return null;
    }

    /**
     * Edit method
     *
     * @param string|null $id Label id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null): ?Response
    {
        $label = $this->Labels->get($id, contain: []);
        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $label = $this->Labels->patchEntity($label, $this->getRequest()->getData());
            // check if not bad SQL
            if ($this->isBadSQL($this->getRequest()->getData('dynamic_sql'))) {
                // bad SQL - set error
                $label->setError(
                    'dynamic_sql',
                    __('An expression for data modification was detected in the SQL query, which is forbidden.'),
                );
            } elseif ($this->Labels->save($label)) {
                // good SQL - proceed
                $this->Flash->success(__('The label has been saved.'));

                return $this->afterEditRedirect(['action' => 'view', $label->id]);
            }
            $this->Flash->error(__('The label could not be saved. Please, try again.'));
        }
        $roles = (new AppUser())->getRoleOptions();
        $this->set(compact('label', 'roles'));

        return null;
    }

    /**
     * Delete method
     *
     * @param string|null $id Label id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $this->getRequest()->allowMethod(['post', 'delete']);
        $label = $this->Labels->get($id);
        if ($this->Labels->delete($label)) {
            $this->Flash->success(__('The label has been deleted.'));
        } else {
            $this->flashValidationErrors($label->getErrors());
            $this->Flash->error(__('The label could not be deleted. Please, try again.'));
        }

        return $this->afterDeleteRedirect(['action' => 'index']);
    }

    /**
     * Is bad SQL method
     *
     * Checks if SQL does not contain expressions for data modification
     *
     * @param string|null $sql SQL string to be checked.
     * @return bool Return false if SQL OK, true if SQL contains expressions for data modification.
     */
    public function isBadSQL(?string $sql): bool
    {
        if ($sql === null || $sql === '') {
            return false;
        }

        $not_modifications = (
            stripos($sql, 'insert') === false
            && stripos($sql, 'update') === false
            && stripos($sql, 'delete') === false
            && stripos($sql, 'alter') === false
            && stripos($sql, 'drop') === false
            && stripos($sql, 'truncate') === false
            && stripos($sql, 'set') === false
        );

        return !$not_modifications;
    }

    /**
     * Update related customer labels method
     *
     * @param string|null $id Label id.
     * @return \Cake\Http\Response|null Redirects to view.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function updateRelatedCustomerLabels(?string $id = null): ?Response
    {
        $this->getRequest()->allowMethod(['post']);

        if (is_null($id)) {
            $param = null;
        } else {
            $label = $this->Labels->get($id);
            $param = strval($label->id);
        }

        $runner = new CommandRunner(new Application(dirname(__DIR__, 2) . '/config'), 'cake');
        if ($runner->run(['cake', 'update_customer_labels', $param]) === 0) {
            $this->Flash->success(__('The related customer labels has been updated.'));
        } else {
            $this->Flash->error(__('The related customer labels could not be updated. Please, try again.'));
        }

        return $this->redirect($this->referer(['action' => 'index']));
    }
}
