<?php
declare(strict_types=1);

namespace App\View\Cell;

use App\Model\Entity\ServiceOverride;
use Cake\View\Cell;

/**
 * ServiceOverridesStatus cell
 *
 * Displays active and/or scheduled service overrides for given contracts.
 *
 * @extends \Cake\View\Cell<\App\View\AppView>
 */
class ServiceOverridesStatusCell extends Cell
{
    /**
     * List of valid options that can be passed into this
     * cell's constructor.
     *
     * @var array<string>
     */
    protected array $_validCellOptions = [
        'showContractNumber',
        'onlyActiveOverrides',
    ];

    /**
     * Initialization logic run at the end of object construction.
     *
     * @return void
     */
    public function initialize(): void
    {
    }

    /**
     * Default display method.
     *
     * @param array<string> $contractIds List of contract IDs to check.
     * @options bool $showContractNumber Whether to show contract number (default true).
     * @options bool $onlyActiveOverrides Whether to show only active overrides (default false).
     * @return void
     */
    public function display(array $contractIds): void
    {
        $showContractNumber = $this->showContractNumber ?? true;
        $onlyActiveOverrides = $this->onlyActiveOverrides ?? false;

        if (empty($contractIds)) {
            $this->set([
                'activeServiceOverrides' => [],
                'futureServiceOverrides' => [],
                'showContractNumber' => $showContractNumber,
            ]);

            return;
        }

        $serviceOverrides = $this->fetchTable('ServiceOverrides')
            ->find(
                'active',
                includeFuture: !$onlyActiveOverrides,
                includePast: false,
            )
            ->where([
                'ServiceOverrides.contract_id IN' => $contractIds,
            ])
            ->contain([
                'Contracts' => ['fields' => ['id', 'number']],
                'Services' => ['fields' => ['id', 'name']],
            ])
            ->orderBy([
                'ServiceOverrides.valid_from' => 'ASC',
            ]);

        if ($onlyActiveOverrides) {
            $activeServiceOverrides = $serviceOverrides;
            $futureServiceOverrides = [];
        } else {
            $collection = collection($serviceOverrides);

            $activeServiceOverrides = $collection->filter(
                fn(ServiceOverride $override) => $override->isActive(),
            );

            $futureServiceOverrides = $collection->filter(
                fn(ServiceOverride $override) => $override->isFuture(),
            );
        }

        $this->set(compact(
            'activeServiceOverrides',
            'futureServiceOverrides',
            'showContractNumber',
        ));
    }
}
