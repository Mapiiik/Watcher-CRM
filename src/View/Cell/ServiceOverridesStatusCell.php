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
     * Show contract number in output
     *
     * @var bool
     */
    protected bool $showContractNumber = true;

    /**
     * Show only active overrides (skip future)
     *
     * @var bool
     */
    protected bool $onlyActiveOverrides = false;

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
        $this->set('showContractNumber', $this->showContractNumber);

        if (empty($contractIds)) {
            $this->set([
                'activeServiceOverrides' => collection([]),
                'futureServiceOverrides' => collection([]),
                'showContractNumber' => $this->showContractNumber,
            ]);

            return;
        }

        $serviceOverrides = $this->fetchTable('ServiceOverrides')
            ->find(
                'active',
                includeFuture: !$this->onlyActiveOverrides,
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

        if ($this->onlyActiveOverrides) {
            $activeServiceOverrides = $serviceOverrides;
            $futureServiceOverrides = collection([]);
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
        ));
    }
}
