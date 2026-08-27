<?php
declare(strict_types=1);

namespace App\Contracts\Check;

use App\Model\Table\ContractVersionsTable;
use Cake\ORM\Query\SelectQuery;
use Override;
use Settings\Utility\Settings;

/**
 * A version of a contract with no paper behind it, or with paper too old to be its own.
 *
 * Either nothing says when it was concluded, or what says so is from long before the version
 * took effect - which is what carrying the previous version's date onto the new one looks
 * like. In both cases what is on file cannot show what the customer actually agreed to.
 *
 * A thousand of these are on record, so it stays off until it is asked for: switched on it
 * would bury every other finding, and catching up on paperwork is its own afternoon rather
 * than something done while looking at one contract.
 */
class UnsignedContractCheck extends AbstractContractCheck
{
    /**
     * Where the settings say how old the paper may be.
     */
    private const SETTINGS_PATH = 'core.contracts.checks.signature_expected_within_months';

    /**
     * How long before a version takes effect it may have been concluded, if nothing says
     * otherwise.
     */
    private const MONTHS = 3;

    /**
     * @param \App\Model\Table\ContractVersionsTable $versions Contract versions table.
     * @param bool $ignore_inactive Whether to count only the contracts that are running.
     * @param string|null $contract_id The one contract being asked about, where there is one.
     * @param string|null $customer_id The one customer being asked about, where there is one.
     */
    public function __construct(
        private ContractVersionsTable $versions,
        bool $ignore_inactive = true,
        ?string $contract_id = null,
        ?string $customer_id = null,
    ) {
        parent::__construct($ignore_inactive, $contract_id, $customer_id);
    }

    /**
     * @return string|null
     */
    #[Override]
    protected function contractField(): ?string
    {
        return 'ContractVersions.contract_id';
    }

    /**
     * @return string
     */
    #[Override]
    public function id(): string
    {
        return 'unsigned_contract';
    }

    /**
     * @return string
     */
    #[Override]
    public function title(): string
    {
        return __('Contract Version With No Paper Behind It');
    }

    /**
     * @return string
     */
    #[Override]
    public function emptyMessage(): string
    {
        return __('Every contract version says when it was concluded.');
    }

    /**
     * There are a thousand of these, so the overview asks for it rather than being handed it.
     *
     * @return bool
     */
    #[Override]
    public function onDashboard(): bool
    {
        return false;
    }

    /**
     * @return bool
     */
    #[Override]
    public function optional(): bool
    {
        return true;
    }

    /**
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    #[Override]
    public function find(): SelectQuery
    {
        $months = (int)Settings::get(self::SETTINGS_PATH, self::MONTHS);

        $query = $this->versions->find();

        $query
            ->contain(['Contracts' => ['Customers']])
            ->where([
                'OR' => [
                    // No paper at all is a finding whatever the version says about itself -
                    // including the thousand an import left with a start nobody knows, which
                    // is why this limb is not held to a start that means anything.
                    'ContractVersions.conclusion_date IS' => null,
                    [
                        $this->knownDate($query, 'ContractVersions.valid_from'),
                        // paper from long before the version it is meant to be behind, which
                        // is what the previous version's date carried onto a new one looks like
                        $query->expr()->lt(
                            'ContractVersions.conclusion_date',
                            $query->expr(sprintf(
                                "ContractVersions.valid_from - INTERVAL '%d months'",
                                $months,
                            )),
                        ),
                    ],
                ],
            ])
            ->orderBy(['ContractVersions.valid_from' => 'DESC']);

        if ($this->ignore_inactive) {
            $this->onlyRunningContracts($query);
        }

        return $this->scoped($query);
    }
}
