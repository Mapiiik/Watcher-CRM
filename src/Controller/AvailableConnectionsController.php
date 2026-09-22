<?php
declare(strict_types=1);

namespace App\Controller;

use App\Addresses\Resolver as AddressesResolver;
use App\Controller\Traits\CommonViewVarListsTrait;
use App\Model\Entity\AvailableConnection;
use App\Model\Enum\AccessTechnology;
use App\Model\Enum\AvailableConnectionOrigin;
use Cake\Http\Response;
use Cake\I18n\Date;
use RuntimeException;

/**
 * AvailableConnections Controller
 *
 * The address points a customer could be connected at. The synchronisation fills in every one a
 * contract ever had, and the operator adds the rest - a building wired and nobody in it yet.
 *
 * @property \App\Model\Table\AvailableConnectionsTable $AvailableConnections
 */
class AvailableConnectionsController extends AppController
{
    use CommonViewVarListsTrait;

    /**
     * What a record is about. Changing any of them by hand takes the record over from the
     * synchronisation, which would otherwise put its own figures back.
     */
    private const TAKEN_OVER_BY = [
        'address_registry_source',
        'address_registry_reference',
        'access_technology',
        'speed_down_max',
        'speed_up_max',
    ];

    /**
     * Index method
     *
     * @return void Renders view
     */
    public function index(): void
    {
        $query = $this->AvailableConnections->find();

        $search = trim((string)$this->getRequest()->getQuery('search'));
        if ($search !== '') {
            $query->where([
                'OR' => [
                    'AvailableConnections.address_label ILIKE' => '%' . $search . '%',
                    'AvailableConnections.address_registry_reference' => $search,
                ],
            ]);
        }

        $technology = AccessTechnology::tryFrom((string)$this->getRequest()->getQuery('access_technology'));
        if ($technology !== null) {
            $query->where(['AvailableConnections.access_technology' => $technology]);
        }

        $origin = AvailableConnectionOrigin::tryFrom((string)$this->getRequest()->getQuery('origin'));
        if ($origin !== null) {
            $query->where(['AvailableConnections.origin' => $origin]);
        }

        if (!filter_var($this->getRequest()->getQuery('show_retired'), FILTER_VALIDATE_BOOLEAN)) {
            $query->find('inService');
        }

        $availableConnections = $this->paginate($query, [
            'order' => ['AvailableConnections.address_label' => 'ASC'],
            'sortableFields' => [
                'address_label',
                'address_registry_reference',
                'access_technology',
                'speed_down_max',
                'speed_up_max',
                'origin',
                'retired',
            ],
        ]);

        $this->set(compact('availableConnections'));
        $this->set('origins', AvailableConnectionOrigin::options());
        $this->setAccessTechnologiesViewVarList();
    }

    /**
     * View method
     *
     * @param string|null $id Available Connection id.
     * @return void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null): void
    {
        $availableConnection = $this->AvailableConnections->get($id, contain: [
            'Contracts',
            'Creators',
            'Modifiers',
        ]);

        $this->set(compact('availableConnection'));
        $this->setAccessPointsViewVarList();
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add(): ?Response
    {
        $availableConnection = $this->AvailableConnections->newEmptyEntity();
        $availableConnection->origin = AvailableConnectionOrigin::Manual;

        return $this->form($availableConnection);
    }

    /**
     * Edit method
     *
     * @param string|null $id Available Connection id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null): ?Response
    {
        return $this->form($this->AvailableConnections->get($id));
    }

    /**
     * Retire method
     *
     * The connection is gone from today on. The record stays, so that the synchronisation does not
     * bring it back and what was reported before can still be told.
     *
     * @param string|null $id Available Connection id.
     * @return \Cake\Http\Response|null Redirects back.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function retire(?string $id = null): ?Response
    {
        $this->getRequest()->allowMethod(['post']);
        $availableConnection = $this->AvailableConnections->get($id);
        $availableConnection->retired = Date::now();

        if ($this->AvailableConnections->save($availableConnection)) {
            $this->Flash->success(__('The available connection has been retired.'));
        } else {
            $this->Flash->error(__('The available connection could not be retired. Please, try again.'));
        }

        return $this->redirect(['action' => 'view', $availableConnection->id]);
    }

    /**
     * Delete method
     *
     * For a record that should never have been. One the synchronisation made comes back while its
     * contract is there, which retiring it prevents.
     *
     * @param string|null $id Available Connection id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $this->getRequest()->allowMethod(['post', 'delete']);
        $availableConnection = $this->AvailableConnections->get($id);

        if ($this->AvailableConnections->delete($availableConnection)) {
            $this->Flash->success(__('The available connection has been deleted.'));
        } else {
            $this->Flash->error(__('The available connection could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * The form both add and edit are.
     *
     * Picking an address in the search sends the form back to be filled in from the registry
     * rather than saved, the way the customers' addresses are entered.
     *
     * @param \App\Model\Entity\AvailableConnection $availableConnection The record.
     * @return \Cake\Http\Response|null
     */
    private function form(AvailableConnection $availableConnection): ?Response
    {
        $isNew = $availableConnection->isNew();

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $refresh = $this->getRequest()->getData('refresh') === 'refresh';

            $availableConnection = $this->AvailableConnections->patchEntity(
                $availableConnection,
                $this->getRequest()->getData(),
                $refresh ? ['validate' => false] : [],
            );

            $picked = $this->getRequest()->getData('address_registry_search');
            if (is_string($picked) && $picked !== '') {
                $this->fillFromRegistry($availableConnection, $picked);
            } elseif ($refresh && $availableConnection->isDirty('address_registry_source')) {
                // another country's registry, where the point picked before means nothing
                $availableConnection->patch([
                    'address_registry_reference' => null,
                    'address_label' => null,
                    'gps_y' => null,
                    'gps_x' => null,
                ]);
            }

            if (!$refresh) {
                if (
                    $availableConnection->origin === AvailableConnectionOrigin::Contract
                    && array_intersect(self::TAKEN_OVER_BY, $availableConnection->getDirty()) !== []
                ) {
                    $availableConnection->origin = AvailableConnectionOrigin::Manual;
                }

                if ($this->AvailableConnections->save($availableConnection)) {
                    $this->Flash->success(__('The available connection has been saved.'));

                    return $isNew
                        ? $this->afterAddRedirect(['action' => 'view', $availableConnection->id])
                        : $this->afterEditRedirect(['action' => 'view', $availableConnection->id]);
                }

                $this->Flash->error(__('The available connection could not be saved. Please, try again.'));
            }
        }

        $this->set(compact('availableConnection'));
        $this->set('registrySources', $this->registrySources());
        $this->set('searchCountryCode', $availableConnection->address_registry_source
            ?? (string)array_key_first($this->registrySources()));
        $this->setAccessPointsViewVarList(onlyActive: true);

        return null;
    }

    /**
     * Takes the address point from the registry, key and all.
     *
     * @param \App\Model\Entity\AvailableConnection $availableConnection The record.
     * @param string $key What the search picked, "source|reference".
     * @return void
     */
    private function fillFromRegistry(AvailableConnection $availableConnection, string $key): void
    {
        try {
            $address = AddressesResolver::byKey($key);
        } catch (RuntimeException $e) {
            $this->Flash->error(__('Could not retrieve address from national address registry: {0}', $e->getMessage()));

            return;
        }

        $availableConnection->patch([
            'address_registry_source' => $address->source,
            'address_registry_reference' => $address->registryReference,
            'address_label' => $address->formattedAddress,
            'gps_y' => $address->latitude,
            'gps_x' => $address->longitude,
        ]);
    }

    /**
     * The registries an address can be looked up in, lower case as they are stored.
     *
     * @return array<string, string>
     */
    private function registrySources(): array
    {
        try {
            $countries = AddressesResolver::supportedCountries() ?? [];
        } catch (RuntimeException) {
            $countries = [];
        }

        $sources = [];
        foreach ($countries as $country) {
            $sources[strtolower($country)] = $country;
        }

        return $sources;
    }
}
