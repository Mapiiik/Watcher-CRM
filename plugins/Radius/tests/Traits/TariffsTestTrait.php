<?php
declare(strict_types=1);

namespace Radius\Test\Traits;

use App\Model\Table\BillingsTable;
use App\Model\Table\ConnectionProfilesTable;
use App\Model\Table\ServiceOverridesTable;
use App\Model\Table\ServicesTable;
use Cake\Datasource\EntityInterface;
use Cake\I18n\Date;
use Cake\I18n\DateTime;
use Cake\ORM\TableRegistry;

/**
 * Tariffs naming a RADIUS group, and contracts billed or overridden with them.
 *
 * For tests of what group a contract's accounts belong in. Records are written past validation,
 * because what is under test is how they are read, not whether they could be entered.
 */
trait TariffsTestTrait
{
    /**
     * A service whose connection profile names the given group.
     *
     * @param string $group RADIUS group.
     * @return string Service id.
     */
    protected function tariff(string $group): string
    {
        $profile = $this->written(ConnectionProfilesTable::class, [
            'name' => $group,
            'radius_group' => $group,
        ]);

        return (string)$this->written(ServicesTable::class, [
            'name' => $group,
            'price' => 1,
            'connection_profile_id' => $profile->get('id'),
            'service_type_id' => '907cbc5c-af88-43b6-b535-959b4fa2ce3d',
        ])->get('id');
    }

    /**
     * A billing of the service on the contract.
     *
     * @param string $contract_id Contract id.
     * @param string $customer_id Customer holding the contract.
     * @param string $service_id Service id.
     * @param \Cake\I18n\Date $from First day billed.
     * @param \Cake\I18n\Date|null $until Last day billed, or null for open-ended.
     * @return void
     */
    protected function billed(
        string $contract_id,
        string $customer_id,
        string $service_id,
        Date $from,
        ?Date $until = null,
    ): void {
        $this->written(BillingsTable::class, [
            'contract_id' => $contract_id,
            'customer_id' => $customer_id,
            'service_id' => $service_id,
            'billing_from' => $from,
            'billing_until' => $until,
        ]);
    }

    /**
     * An override of the contract's service.
     *
     * @param string $contract_id Contract id.
     * @param string $service_id Service id.
     * @param \Cake\I18n\Date $from First day in force.
     * @param \Cake\I18n\Date $until Last day in force.
     * @param bool $revoked Whether it has been revoked.
     * @return void
     */
    protected function overridden(
        string $contract_id,
        string $service_id,
        Date $from,
        Date $until,
        bool $revoked = false,
    ): void {
        $this->written(ServiceOverridesTable::class, [
            'contract_id' => $contract_id,
            'service_id' => $service_id,
            'valid_from' => $from,
            'valid_until' => $until,
            'reason' => 'Test',
            'revoked' => $revoked ? DateTime::now() : null,
        ]);
    }

    /**
     * A record written as given.
     *
     * @param class-string<\Cake\ORM\Table> $table Table class.
     * @param array<string, mixed> $data Record.
     * @return \Cake\Datasource\EntityInterface
     */
    private function written(string $table, array $data): EntityInterface
    {
        $target = TableRegistry::getTableLocator()->get($table);

        return $target->saveOrFail(
            $target->newEntity($data, ['validate' => false, 'accessibleFields' => ['*' => true]]),
            ['checkRules' => false],
        );
    }
}
