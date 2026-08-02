<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * HistoricalConnections Controller
 *
 * Read only on purpose. The history is written by the historical connections
 * update from what the sources report, and letting it be edited by hand would
 * cost it the one property that makes it worth keeping.
 *
 * @property \App\Model\Table\HistoricalConnectionsTable $HistoricalConnections
 */
class HistoricalConnectionsController extends AppController
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
            $conditions += ['HistoricalConnections.customer_id' => $this->customer_id];
        }
        if ($this->contract_id !== null) {
            $conditions += ['HistoricalConnections.contract_id' => $this->contract_id];
        }

        $request = $this->getRequest();

        // reachable from the monitoring of a single account
        $account = $request->getQuery('source_reference');
        if (!empty($account)) {
            $conditions += ['HistoricalConnections.source_reference' => $account];
        }

        // search across the identifiers an operator is likely to have at hand
        $search = $request->getQuery('search');
        if (!empty($search)) {
            $term = '%' . trim((string)$search) . '%';
            $conditions[] = [
                'OR' => [
                    'HistoricalConnections.source_reference ILIKE' => $term,
                    'HistoricalConnections.station_id ILIKE' => $term,
                    'HistoricalConnections.ip_address ILIKE' => $term,
                    'HistoricalConnections.ipv6_prefix ILIKE' => $term,
                    'HistoricalConnections.nas_ip_address ILIKE' => $term,
                    'HistoricalConnections.access_point_name ILIKE' => $term,
                    'HistoricalConnections.routeros_device_name ILIKE' => $term,
                ],
            ];
        }

        $query = $this->HistoricalConnections
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

        $historicalConnections = $this->paginate($query);

        $this->set(compact('historicalConnections'));
    }

    /**
     * View method
     *
     * @param string|null $id Historical Connection id.
     * @return void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null): void
    {
        $historicalConnection = $this->HistoricalConnections->get($id, contain: [
            'Customers',
            'Contracts',
            'Creators',
            'Modifiers',
        ]);

        // the same station turning up under another account is worth seeing
        $relatedStations = [];
        if ($historicalConnection->station_id !== null) {
            $relatedStations = $this->HistoricalConnections
                ->find('forStation', stationId: $historicalConnection->station_id)
                ->contain(['Customers', 'Contracts'])
                ->where(['HistoricalConnections.id !=' => $historicalConnection->id])
                ->limit(50)
                ->all();
        }

        $this->set(compact('historicalConnection', 'relatedStations'));
    }
}
