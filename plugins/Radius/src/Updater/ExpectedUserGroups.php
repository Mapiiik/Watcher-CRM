<?php
declare(strict_types=1);

namespace Radius\Updater;

use App\Model\Table\BillingsTable;
use App\Model\Table\ServiceOverridesTable;
use Cake\I18n\Date;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Query\SelectQuery;

/**
 * The RADIUS user group a contract's accounts belong in, as its tariff says.
 *
 * A service override in force wins, otherwise the earliest billing running now or starting
 * within a month. The updater writes what this says and the checks compare against it, so the
 * two cannot disagree about what is right.
 */
class ExpectedUserGroups
{
    use LocatorAwareTrait;

    /**
     * How many contracts one query asks about.
     */
    private const BATCH_SIZE = 1000;

    private Date $today;

    /**
     * @param \Cake\I18n\Date|null $today The day to ask about, today if not given.
     */
    public function __construct(?Date $today = null)
    {
        $this->today = $today ?? Date::today();
    }

    /**
     * The group for one contract, or null where its tariff names none.
     *
     * @param string $contract_id Contract id.
     * @return string|null
     */
    public function forContract(string $contract_id): ?string
    {
        return $this->forContracts([$contract_id])[$contract_id] ?? null;
    }

    /**
     * The groups for the given contracts, leaving out those whose tariff names none.
     *
     * @param list<string> $contract_ids Contract ids.
     * @return array<string, string> Groups by contract id.
     */
    public function forContracts(array $contract_ids): array
    {
        $groups = [];

        foreach ($this->batches($contract_ids) as $batch) {
            // an override is read first, so that a billing does not take its place
            $groups += $this->firstGroups($this->overrides($batch));
            $groups += $this->firstGroups($this->billings($batch)->where([
                'Billings.billing_from <=' => $this->today->addMonths(1),
            ]));
        }

        return $groups;
    }

    /**
     * The day a tariff naming a group starts, for contracts with none yet.
     *
     * @param list<string> $contract_ids Contract ids.
     * @return array<string, \Cake\I18n\Date> Days by contract id, for contracts with a later tariff.
     */
    public function laterStarts(array $contract_ids): array
    {
        $starts = [];

        foreach ($this->batches($contract_ids) as $batch) {
            $billings = $this->billings($batch)
                ->select(['contract_id' => 'Billings.contract_id', 'billing_from' => 'Billings.billing_from'])
                ->where(['Billings.billing_from >' => $this->today->addMonths(1)]);

            foreach ($billings as $billing) {
                $starts[(string)$billing['contract_id']] ??= $billing['billing_from'];
            }
        }

        return $starts;
    }

    /**
     * Overrides in force on the given contracts whose service names a group, newest first.
     *
     * @param list<string> $contract_ids Contract ids.
     * @return \Cake\ORM\Query\SelectQuery<array<string, mixed>>
     */
    private function overrides(array $contract_ids): SelectQuery
    {
        return $this->fetchTable(ServiceOverridesTable::class)
            ->find()
            ->select([
                'contract_id' => 'ServiceOverrides.contract_id',
                'radius_group' => 'ConnectionProfiles.radius_group',
            ])
            ->innerJoinWith('Services.ConnectionProfiles')
            ->where([
                'ServiceOverrides.contract_id IN' => $contract_ids,
                'ConnectionProfiles.radius_group IS NOT' => null,
                'ServiceOverrides.valid_from <=' => $this->today,
                'ServiceOverrides.valid_until >=' => $this->today,
                'ServiceOverrides.revoked IS' => null,
            ])
            ->orderBy(['ServiceOverrides.created' => 'DESC'])
            ->disableHydration();
    }

    /**
     * Billings on the given contracts that have not ended and whose service names a group,
     * earliest first.
     *
     * @param list<string> $contract_ids Contract ids.
     * @return \Cake\ORM\Query\SelectQuery<array<string, mixed>>
     */
    private function billings(array $contract_ids): SelectQuery
    {
        return $this->fetchTable(BillingsTable::class)
            ->find()
            ->select([
                'contract_id' => 'Billings.contract_id',
                'radius_group' => 'ConnectionProfiles.radius_group',
            ])
            ->innerJoinWith('Services.ConnectionProfiles')
            ->where([
                'Billings.contract_id IN' => $contract_ids,
                'ConnectionProfiles.radius_group IS NOT' => null,
                'OR' => [
                    'Billings.billing_until IS' => null,
                    'Billings.billing_until >=' => $this->today,
                ],
            ])
            ->orderBy(['Billings.billing_from' => 'ASC'])
            ->disableHydration();
    }

    /**
     * The first group the query names for each contract.
     *
     * @param \Cake\ORM\Query\SelectQuery<array<string, mixed>> $query Contract ids and groups, best first.
     * @return array<string, string>
     */
    private function firstGroups(SelectQuery $query): array
    {
        $groups = [];
        foreach ($query as $row) {
            $groups[(string)$row['contract_id']] ??= (string)$row['radius_group'];
        }

        return $groups;
    }

    /**
     * The contract ids split into batches small enough for one query.
     *
     * @param list<string> $contract_ids Contract ids.
     * @return list<list<string>>
     */
    private function batches(array $contract_ids): array
    {
        $contract_ids = array_values(array_unique($contract_ids));

        return $contract_ids === [] ? [] : array_chunk($contract_ids, self::BATCH_SIZE);
    }
}
