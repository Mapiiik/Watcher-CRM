<?php
declare(strict_types=1);

namespace Radius\Check;

use App\Check\CheckScope;
use Cake\Database\Exception\MissingConnectionException;
use Cake\Log\Log;
use Cake\ORM\Locator\LocatorAwareTrait;
use Radius\Model\Entity\Account;
use Radius\Model\Table\AccountsTable;
use Radius\Updater\ExpectedUserGroups;

/**
 * The active RADIUS accounts held against the groups their contracts' tariffs name.
 *
 * Read once and shared by the checks reporting on it, since each of them needs every active
 * account and the tariffs behind them. A RADIUS database that cannot be reached gives no
 * findings rather than an error, so the pages asking about it still draw.
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

        $query = $this->fetchTable(AccountsTable::class)
            ->find()
            ->contain(['Radusergroup'])
            ->where(['Accounts.active' => true])
            ->orderBy(['Accounts.username' => 'ASC']);

        if ($this->scope->contract_id !== null) {
            $query->where(['Accounts.contract_id' => $this->scope->contract_id]);
        }
        if ($this->scope->customer_id !== null) {
            $query->where(['Accounts.customer_id' => $this->scope->customer_id]);
        }

        try {
            /** @var list<\Radius\Model\Entity\Account> $accounts */
            $accounts = $query->all()->toList();
        } catch (MissingConnectionException $e) {
            Log::warning('The RADIUS accounts could not be held against the tariffs. (' . $e->getMessage() . ')');
            $accounts = [];
        }

        return $this->accounts = $accounts;
    }
}
