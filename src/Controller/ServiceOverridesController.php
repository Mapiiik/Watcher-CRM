<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Response;
use Cake\I18n\DateTime;

/**
 * ServiceOverrides Controller
 *
 * @property \App\Model\Table\ServiceOverridesTable $ServiceOverrides
 */
class ServiceOverridesController extends AppController
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
        if ($this->customer_id !== null) {
            $conditions += ['Contracts.customer_id' => $this->customer_id];
        }
        if ($this->contract_id !== null) {
            $conditions += ['ServiceOverrides.contract_id' => $this->contract_id];
        }

        // finder options
        $request = $this->getRequest();

        $includeRevoked = (bool)$request->getQuery('include_revoked');
        $includeFuture = (bool)($request->getQuery('include_future') ?? true);
        $includePast = (bool)$request->getQuery('include_past');

        $this->set(compact(
            'includeRevoked',
            'includeFuture',
            'includePast',
        ));

        // search
        $search = $request->getQuery('search');
        if (!empty($search)) {
            $conditions[] = [
                'OR' => [
                    'ServiceOverrides.reason ILIKE' => '%' . trim((string)$search) . '%',
                    'Contracts.number ILIKE' => '%' . trim((string)$search) . '%',
                ],
            ];
        }

        $query = $this->ServiceOverrides
            ->find(
                'active',
                includeRevoked: $includeRevoked,
                includeFuture: $includeFuture,
                includePast: $includePast,
            )
            ->contain([
                'Contracts',
                'Services',
            ])
            ->where($conditions);

        $this->paginate = [
            'order' => [
                'valid_from' => 'DESC',
            ],
        ];

        $serviceOverrides = $this->paginate($query);

        $this->set(compact('serviceOverrides'));
    }

    /**
     * View method
     *
     * @param string|null $id Service Override id.
     * @return void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null): void
    {
        $serviceOverride = $this->ServiceOverrides->get($id, contain: [
            'Contracts' => [
                'Customers',
                'InstallationAddresses',
                'ServiceTypes',
            ],
            'Services',
            'Creators',
            'Modifiers',
            'Revokers',
        ]);

        $this->set(compact('serviceOverride'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add(): ?Response
    {
        $serviceOverride = $this->ServiceOverrides->newEmptyEntity();

        if ($this->contract_id !== null) {
            $serviceOverride->contract_id = $this->contract_id;
        }

        if ($this->request->is('post')) {
            $serviceOverride = $this->ServiceOverrides->patchEntity(
                $serviceOverride,
                $this->dataWithAdditionalParameters($this->ServiceOverrides, $this->request->getData()),
            );

            if ($this->getRequest()->getData('refresh') == 'refresh') {
                // only refresh
            } else {
                if ($this->ServiceOverrides->save($serviceOverride)) {
                    $this->Flash->success(__('The service override has been saved.'));

                    return $this->afterAddRedirect(['action' => 'view', $serviceOverride->id]);
                }
                $this->Flash->error(__('The service override could not be saved. Please, try again.'));
            }
        }
        $contracts = $this->ServiceOverrides->Contracts->find(
            'list',
            contain: [
                'InstallationAddresses',
                'ServiceTypes',
            ],
            order: [
                'Contracts.number',
            ],
        );
        $services = $this->ServiceOverrides->Services->find('list', order: [
            'name',
        ]);

        if ($this->customer_id !== null) {
            $contracts->where(['Contracts.customer_id' => $this->customer_id]);
        }
        if ($this->contract_id !== null || isset($serviceOverride->contract_id)) {
            $contractId = $this->contract_id ?? $serviceOverride->contract_id;
            $contracts->where(['Contracts.id' => $contractId]);
            $services->where(['OR' => [
                'service_type_id' => $this->ServiceOverrides->Contracts->get($contractId)->service_type_id,
                'service_type_id IS NULL',
            ]]);
        }

        $this->set(compact('serviceOverride', 'contracts', 'services'));

        return null;
    }

    /**
     * Edit method
     *
     * @param string|null $id Service Override id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null): ?Response
    {
        $serviceOverride = $this->ServiceOverrides->get($id, contain: []);

        $isFuture = $serviceOverride->isFuture();
        $isRevoked = $serviceOverride->revoked !== null;

        $this->set(compact('isFuture', 'isRevoked'));

        if ($this->request->is(['patch', 'post', 'put'])) {
            /** @var array $data Request data */
            $data = $this->request->getData();

            if ($isRevoked) {
                // disable editing of everything except the reason
                unset(
                    $data['contract_id'],
                    $data['service_id'],
                    $data['valid_from'],
                    $data['valid_until'],
                );
            } elseif (!$isFuture) {
                // disable editing of everything except the reason and valid_until
                unset(
                    $data['contract_id'],
                    $data['service_id'],
                    $data['valid_from'],
                );
            }

            $serviceOverride = $this->ServiceOverrides->patchEntity($serviceOverride, $data);

            if ($this->getRequest()->getData('refresh') == 'refresh') {
                // only refresh
            } else {
                if ($this->ServiceOverrides->save($serviceOverride)) {
                    $this->Flash->success(__('The service override has been saved.'));

                    return $this->afterEditRedirect(['action' => 'view', $serviceOverride->id]);
                }
                $this->Flash->error(__('The service override could not be saved. Please, try again.'));
            }
        }
        $contracts = $this->ServiceOverrides->Contracts->find(
            'list',
            contain: [
                'InstallationAddresses',
                'ServiceTypes',
            ],
            order: [
                'Contracts.number',
            ],
        );
        $services = $this->ServiceOverrides->Services->find('list', order: [
            'name',
        ]);

        if ($this->customer_id !== null) {
            $contracts->where(['Contracts.customer_id' => $this->customer_id]);
        }
        if ($this->contract_id !== null || isset($serviceOverride->contract_id)) {
            $contractId = $this->contract_id ?? $serviceOverride->contract_id;
            $contracts->where(['Contracts.id' => $contractId]);
            $services->where(['OR' => [
                'service_type_id' => $this->ServiceOverrides->Contracts->get($contractId)->service_type_id,
                'service_type_id IS NULL',
            ]]);
        }

        $this->set(compact('serviceOverride', 'contracts', 'services'));

        return null;
    }

    /**
     * Delete method
     *
     * @param string|null $id Service Override id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $serviceOverride = $this->ServiceOverrides->get($id);
        if ($this->ServiceOverrides->delete($serviceOverride)) {
            $this->Flash->success(__('The service override has been deleted.'));
        } else {
            $this->flashValidationErrors($serviceOverride->getErrors());
            $this->Flash->error(__('The service override could not be deleted. Please, try again.'));
        }

        return $this->afterDeleteRedirect(['action' => 'index']);
    }

    /**
     * Revoke a service override.
     *
     * Marks the override as revoked and stores revocation metadata.
     *
     * @param string|null $id Service Override id.
     * @return \Cake\Http\Response|null
     */
    public function revoke(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post']);

        $serviceOverride = $this->ServiceOverrides->get($id);

        if ($serviceOverride->revoked !== null) {
            $this->Flash->warning(__('This service override is already revoked.'));

            return $this->afterDeleteRedirect(['action' => 'index']);
        }

        $serviceOverride->revoked = DateTime::now();
        $serviceOverride->revoked_by = $this->getRequest()->getAttribute('identity')['id'] ?? null;

        if ($this->ServiceOverrides->save($serviceOverride, ['checkRules' => false])) {
            $this->Flash->success(__('The service override has been revoked.'));
        } else {
            $this->flashValidationErrors($serviceOverride->getErrors());
            $this->Flash->error(__('The service override could not be revoked. Please try again.'));
        }

        return $this->afterDeleteRedirect(['action' => 'index']);
    }
}
