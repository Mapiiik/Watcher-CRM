<?php
declare(strict_types=1);

namespace App\Contracts\Check;

use App\Model\Table\ContractVersionProposalsTable;
use Cake\I18n\Date;
use Cake\ORM\Query\SelectQuery;
use Override;
use Settings\Utility\Settings;

/**
 * Papers that went out to the customer and have not come back signed.
 *
 * The other side of the same coin as {@see \App\Contracts\Check\UnsignedContractCheck}, which reads
 * the version and so only ever sees a new contract that nobody signed. A proposal is where the
 * papers come from whatever they are for, so this catches what the version cannot say anything
 * about: an amendment or an agreement to end a contract, both of them out at a customer who has
 * agreed to nothing yet, on a version that is itself perfectly well signed.
 *
 * Nobody is cut off over this. An unsigned amendment leaves the service running on the version
 * behind it, which is why the finding is the office's business rather than the automation's.
 */
class UnsignedProposalCheck extends AbstractContractCheck
{
    /**
     * How long the papers may be out before it is worth raising, if nothing says otherwise.
     */
    private const AFTER_DAYS = 14;

    /**
     * Where the settings say how long that is. Beside the other waits for a signature rather
     * than among the checks: it is the same question the reminders ask, measured from the day
     * the papers went out.
     */
    private const AFTER_DAYS_PATH = 'core.contracts.unsigned.proposals.unanswered_after_days';

    /**
     * @param \App\Model\Table\ContractVersionProposalsTable $proposals Contract version proposals table.
     * @param bool $ignore_inactive Whether to keep to the contracts that serve somebody.
     * @param string|null $contract_id The one contract being asked about, where there is one.
     * @param string|null $customer_id The one customer being asked about, where there is one.
     */
    public function __construct(
        private ContractVersionProposalsTable $proposals,
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
        return 'ContractVersionProposals.contract_id';
    }

    /**
     * @return string
     */
    #[Override]
    public function id(): string
    {
        return 'unsigned_proposal';
    }

    /**
     * @return string
     */
    #[Override]
    public function title(): string
    {
        return __('Proposal Waiting for a Signature');
    }

    /**
     * @return string
     */
    #[Override]
    public function emptyMessage(): string
    {
        return __('Every proposal that went out has come back signed.');
    }

    /**
     * Proposals the customer has been sent and has not signed.
     *
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    #[Override]
    public function find(): SelectQuery
    {
        $after = (int)Settings::get(self::AFTER_DAYS_PATH, self::AFTER_DAYS);

        $query = $this->proposals->find('open');

        $query
            ->contain(['Contracts', 'ContractVersions'])
            ->innerJoinWith('Contracts')
            ->where([
                'ContractVersionProposals.sent_date IS NOT' => null,
                'ContractVersionProposals.conclusion_date IS' => null,
                // The wait holds whichever question is being asked. Papers posted this week are
                // not a fault anywhere, a contract's own card included - what the wider reading
                // adds is the contracts that serve nobody, not the post that is still in transit.
                'ContractVersionProposals.sent_date <=' => Date::today()->subDays(max(0, $after)),
            ])
            // Longest out first: that is the one somebody should be ringing about.
            ->orderBy(['ContractVersionProposals.sent_date' => 'ASC']);

        if ($this->ignore_inactive) {
            $this->onlyRunningContracts($query);
        }

        return $this->scoped($query);
    }
}
