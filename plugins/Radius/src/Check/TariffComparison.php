<?php
declare(strict_types=1);

namespace Radius\Check;

use App\Check\CheckScope;
use App\Model\Table\ContractsTable;
use App\Model\Table\IpAddressesTable;
use App\Model\Table\IpNetworksTable;
use Cake\Database\Exception\MissingConnectionException;
use Cake\Log\Log;
use Cake\ORM\Locator\LocatorAwareTrait;
use Radius\Model\Entity\Account;
use Radius\Model\Entity\Radreply;
use Radius\Model\Enum\AddressAttribute;
use Radius\Model\Table\AccountsTable;
use Radius\Updater\ExpectedUserGroups;

/**
 * The active RADIUS accounts held against their contracts: the groups the tariffs name and the
 * addresses the contracts have.
 *
 * Read once and shared by the checks reporting on it, since each of them needs every active
 * account and the tariffs behind them. A RADIUS database that cannot be reached gives no
 * findings rather than an error, so the pages asking about it still draw, and says so.
 */
class TariffComparison
{
    use LocatorAwareTrait;

    private ExpectedUserGroups $expected_user_groups;

    /**
     * @var list<\Radius\Model\Entity\Account>|null
     */
    private ?array $accounts = null;

    /**
     * Whether the RADIUS database could not be reached.
     */
    private bool $unreachable = false;

    /**
     * @var array<string, string>|null
     */
    private ?array $expected = null;

    /**
     * @var list<\Radius\Check\AccountFinding>|null
     */
    private ?array $mismatched = null;

    /**
     * @var list<\Radius\Check\AccountFinding>|null
     */
    private ?array $without_tariff = null;

    /**
     * @var list<\Radius\Check\AccountFinding>|null
     */
    private ?array $outside_their_contract = null;

    /**
     * @var list<\Radius\Check\AccountFinding>|null
     */
    private ?array $with_competing_addresses = null;

    /**
     * @param \App\Check\CheckScope $scope What is being asked about.
     * @param \Radius\Updater\ExpectedUserGroups|null $expected_user_groups Where the tariffs are read.
     */
    public function __construct(
        private CheckScope $scope = new CheckScope(),
        ?ExpectedUserGroups $expected_user_groups = null,
    ) {
        $this->expected_user_groups = $expected_user_groups ?? new ExpectedUserGroups();
    }

    /**
     * Whether nothing could be held against the tariffs, as the RADIUS database did not answer.
     *
     * @return bool
     */
    public function unreachable(): bool
    {
        $this->accounts();

        return $this->unreachable;
    }

    /**
     * Accounts in other groups than the one their tariff names.
     *
     * @return list<\Radius\Check\AccountFinding>
     */
    public function mismatched(): array
    {
        if ($this->mismatched !== null) {
            return $this->mismatched;
        }

        $findings = [];
        foreach ($this->accounts() as $account) {
            $expected = $this->expected()[$account->contract_id] ?? null;
            if ($expected === null) {
                continue;
            }

            $finding = new AccountFinding($account, $expected);
            if ($finding->groupnames() !== [$expected]) {
                $findings[] = $finding;
            }
        }

        return $this->mismatched = $findings;
    }

    /**
     * Accounts whose contract has no tariff naming a group, now or within a month.
     *
     * @return list<\Radius\Check\AccountFinding>
     */
    public function withoutTariff(): array
    {
        if ($this->without_tariff !== null) {
            return $this->without_tariff;
        }

        $accounts = array_values(array_filter(
            $this->accounts(),
            fn(Account $account): bool => !isset($this->expected()[$account->contract_id]),
        ));

        $starts = $this->expected_user_groups->laterStarts(
            array_map(fn(Account $account): string => $account->contract_id, $accounts),
        );

        return $this->without_tariff = array_map(
            fn(Account $account): AccountFinding => new AccountFinding(
                $account,
                tariff_from: $starts[$account->contract_id] ?? null,
            ),
            $accounts,
        );
    }

    /**
     * Accounts handed an address their contract does not have.
     *
     * @return list<\Radius\Check\AccountFinding>
     */
    public function outsideTheirContract(): array
    {
        if ($this->outside_their_contract !== null) {
            return $this->outside_their_contract;
        }

        $accounts = $this->accounts();
        $own = $this->blocksOf(array_map(fn(Account $account): string => $account->contract_id, $accounts));

        $stray = [];
        foreach ($accounts as $account) {
            foreach ($this->handedOut($account) as $block) {
                if (!$this->holds($own[$account->contract_id] ?? [], $block)) {
                    $stray[$account->id][] = $block;
                }
            }
        }

        if ($stray === []) {
            return $this->outside_their_contract = [];
        }

        $holders = $this->holdersOf(array_merge(...array_values($stray)));

        $findings = [];
        foreach ($accounts as $account) {
            $addresses = [];
            foreach ($stray[$account->id] ?? [] as $block) {
                $addresses[(string)$block] = $holders[(string)$block] ?? null;
            }

            if ($addresses !== []) {
                $findings[] = new AccountFinding($account, stray_addresses: $addresses);
            }
        }

        return $this->outside_their_contract = $findings;
    }

    /**
     * Accounts handed more than one address of a family. FreeRADIUS sends them all and the router
     * takes one, so the customer may be given another address than the one meant.
     *
     * @return list<\Radius\Check\AccountFinding>
     */
    public function withCompetingAddresses(): array
    {
        if ($this->with_competing_addresses !== null) {
            return $this->with_competing_addresses;
        }

        $findings = [];
        foreach ($this->accounts() as $account) {
            // keyed by how the address reads, so one address written twice is not two
            $byAttribute = [];
            foreach ($account->radreply as $reply) {
                $attribute = AddressAttribute::tryFrom($reply->attribute);
                $block = AddressBlock::read($reply->value);
                if ($attribute !== null && $attribute->isSingleValued() && $block !== null) {
                    $byAttribute[$attribute->value][(string)$block] = true;
                }
            }

            $competing = [];
            foreach ($byAttribute as $addresses) {
                if (count($addresses) > 1) {
                    array_push($competing, ...array_map('strval', array_keys($addresses)));
                }
            }

            if ($competing !== []) {
                sort($competing, SORT_NATURAL);
                $findings[] = new AccountFinding($account, competing_addresses: $competing);
            }
        }

        return $this->with_competing_addresses = $findings;
    }

    /**
     * The addresses and networks an account is handed.
     *
     * @param \Radius\Model\Entity\Account $account The account.
     * @return list<\Radius\Check\AddressBlock>
     */
    private function handedOut(Account $account): array
    {
        $replies = array_filter(
            $account->radreply,
            fn(Radreply $reply): bool => AddressAttribute::tryFrom($reply->attribute) !== null,
        );

        return array_values(array_filter(array_map(
            fn(Radreply $reply): ?AddressBlock => AddressBlock::read($reply->value),
            $replies,
        )));
    }

    /**
     * Whether any of the blocks has the address or network inside it.
     *
     * @param list<\Radius\Check\AddressBlock> $blocks The blocks.
     * @param \Radius\Check\AddressBlock $block The address or network.
     * @return bool
     */
    private function holds(array $blocks, AddressBlock $block): bool
    {
        foreach ($blocks as $held) {
            if ($block->isWithin($held)) {
                return true;
            }
        }

        return false;
    }

    /**
     * The contract each of the addresses and networks is on now, keyed by how it reads.
     *
     * @param list<\Radius\Check\AddressBlock> $blocks The addresses and networks.
     * @return array<string, \App\Model\Entity\Contract>
     */
    private function holdersOf(array $blocks): array
    {
        $found = [];
        foreach ($this->blocksOf(null) as $contract_id => $held) {
            foreach ($blocks as $block) {
                if ($this->holds($held, $block)) {
                    $found[(string)$block] = $contract_id;
                }
            }
        }

        /** @var array<string, \App\Model\Entity\Contract> $contracts */
        $contracts = $this->fetchTable(ContractsTable::class)
            ->find()
            ->whereInList('Contracts.id', array_values($found), ['allowEmpty' => true])
            ->all()
            ->indexBy('id')
            ->toArray();

        return array_map(fn(string $contract_id) => $contracts[$contract_id], $found);
    }

    /**
     * The addresses and networks the contracts have, by contract id.
     *
     * @param list<string>|null $contract_ids The contracts, null for all of them.
     * @return array<string, list<\Radius\Check\AddressBlock>>
     */
    private function blocksOf(?array $contract_ids): array
    {
        $sources = [[IpAddressesTable::class, 'ip_address'], [IpNetworksTable::class, 'ip_network']];

        $blocks = [];
        foreach ($sources as [$table, $column]) {
            $query = $this->fetchTable($table)
                ->find()
                ->select(['contract_id', $column])
                ->where(['contract_id IS NOT' => null])
                ->disableHydration();

            if ($contract_ids !== null) {
                $query->whereInList('contract_id', $contract_ids, ['allowEmpty' => true]);
            }

            foreach ($query->all() as $row) {
                $block = AddressBlock::read((string)$row[$column]);
                if ($block !== null) {
                    $blocks[(string)$row['contract_id']][] = $block;
                }
            }
        }

        return $blocks;
    }

    /**
     * The groups the tariffs name, by contract id.
     *
     * @return array<string, string>
     */
    private function expected(): array
    {
        return $this->expected ??= $this->expected_user_groups->forContracts(
            array_map(fn(Account $account): string => $account->contract_id, $this->accounts()),
        );
    }

    /**
     * The active accounts, with their groups, narrowed to what is being asked about.
     *
     * @return list<\Radius\Model\Entity\Account>
     */
    private function accounts(): array
    {
        if ($this->accounts !== null) {
            return $this->accounts;
        }

        try {
            // the table reads its columns from the database, so even building this may fail
            $query = $this->fetchTable(AccountsTable::class)
                ->find()
                ->contain(['Radusergroup', 'Radreply'])
                ->where(['Accounts.active' => true])
                ->orderBy(['Accounts.username' => 'ASC']);

            if ($this->scope->contract_id !== null) {
                $query->where(['Accounts.contract_id' => $this->scope->contract_id]);
            }
            if ($this->scope->customer_id !== null) {
                $query->where(['Accounts.customer_id' => $this->scope->customer_id]);
            }

            /** @var list<\Radius\Model\Entity\Account> $accounts */
            $accounts = $query->all()->toList();
        } catch (MissingConnectionException $e) {
            Log::warning('The RADIUS accounts could not be held against the tariffs. (' . $e->getMessage() . ')');
            $accounts = [];
            $this->unreachable = true;
        }

        return $this->accounts = $accounts;
    }
}
