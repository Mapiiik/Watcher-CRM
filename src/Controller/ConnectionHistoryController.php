<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * ConnectionHistory Controller
 *
 * Read only on purpose. The history is written by the connection history
 * update from what the sources report, and letting it be edited by hand would
 * cost it the one property that makes it worth keeping.
 *
 * @property \App\Model\Table\ConnectionHistoryTable $ConnectionHistory
 */
class ConnectionHistoryController extends AppController
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
            $conditions += ['ConnectionHistory.customer_id' => $this->customer_id];
        }
        if ($this->contract_id !== null) {
            $conditions += ['ConnectionHistory.contract_id' => $this->contract_id];
        }

        $request = $this->getRequest();

        // reachable from the monitoring of a single account
        $account = $request->getQuery('source_reference');
        if (!empty($account)) {
            $conditions += ['ConnectionHistory.source_reference' => $account];
        }

        // search across the identifiers an operator is likely to have at hand
        $search = $request->getQuery('search');
        if (!empty($search)) {
            $term = '%' . trim((string)$search) . '%';
            $conditions[] = [
                'OR' => [
                    'ConnectionHistory.source_reference ILIKE' => $term,
                    'ConnectionHistory.station_id ILIKE' => $term,
                    'ConnectionHistory.ip_address ILIKE' => $term,
                    'ConnectionHistory.ipv6_prefix ILIKE' => $term,
                    'ConnectionHistory.nas_ip_address ILIKE' => $term,
                    'ConnectionHistory.access_point_name ILIKE' => $term,
                    'ConnectionHistory.routeros_device_name ILIKE' => $term,
                ],
            ];
        }

        $query = $this->ConnectionHistory
            ->find()
            ->contain([
                'Customers',
                'Contracts',
            ])
            ->where($conditions);

        $this->paginate = [
            'order' => [
                'first_seen' => 'DESC',
            ],
            // the listing offers the customer and the contract as columns of
            // its own whenever it is not already inside one of their cards
            'sortableFields' => [
                'first_seen',
                'last_seen',
                'access_point_name',
                'routeros_device_name',
                'nas_ip_address',
                'nas_port_id',
                'station_id',
                'ip_address',
                'ipv6_prefix',
                'source_reference',
                'Customers.company',
                'Contracts.number',
            ],
        ];

        $connectionHistory = $this->paginate($query);

        $this->set(compact('connectionHistory'));
    }

    /**
     * View method
     *
     * @param string|null $id Connection History id.
     * @return void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null): void
    {
        $connectionHistory = $this->ConnectionHistory->get($id, contain: [
            'Customers',
            'Contracts',
            'Creators',
            'Modifiers',
        ]);

        // the same station turning up under another account is worth seeing
        $relatedStations = [];
        if ($connectionHistory->station_id !== null) {
            $relatedStations = $this->ConnectionHistory
                ->find('forStation', stationId: $connectionHistory->station_id)
                ->contain(['Customers', 'Contracts'])
                ->where(['ConnectionHistory.id !=' => $connectionHistory->id])
                ->limit(50)
                ->all();
        }

        $this->set(compact('connectionHistory', 'relatedStations'));
    }
}
