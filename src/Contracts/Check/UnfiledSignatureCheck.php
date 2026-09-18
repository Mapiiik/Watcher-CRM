<?php
declare(strict_types=1);

namespace App\Contracts\Check;

use App\Model\Table\ContractProposalsTable;
use App\Proposals\LateProposals;
use App\Service\ContractPrint\ContractDocuments;
use Cake\ORM\Query\SelectQuery;
use Override;
use Settings\Utility\Settings;

/**
 * The customer signed and the signed copy has not been filed.
 *
 * Said out loud because nothing else would ever say it: the proposal is signed, it carries over,
 * the service runs, and the paper stays in somebody's inbox for good. Carried-over proposals are
 * reported too - that is where the papers are needed most.
 */
class UnfiledSignatureCheck extends AbstractContractCheck
{
    /**
     * How long after the signature the scan may be missing, if nothing says otherwise.
     */
    private const AFTER_DAYS = 7;

    /**
     * Where the settings say how long that is.
     */
    private const AFTER_DAYS_PATH = 'core.contracts.documents.unfiled_after_days';

    /**
     * @param \App\Model\Table\ContractProposalsTable $proposals Contract proposals table.
     * @param bool $ignore_inactive Whether to keep to the contracts that serve somebody.
     * @param string|null $contract_id The one contract being asked about, where there is one.
     * @param string|null $customer_id The one customer being asked about, where there is one.
     */
    public function __construct(
        private ContractProposalsTable $proposals,
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
        return 'ContractProposals.contract_id';
    }

    /**
     * @return string
     */
    #[Override]
    public function id(): string
    {
        return 'unfiled_signature';
    }

    /**
     * @return string
     */
    #[Override]
    public function title(): string
    {
        return __('Signature Nobody Filed');
    }

    /**
     * @return string
     */
    #[Override]
    public function emptyMessage(): string
    {
        return __('Every signature written down has the signed papers to go with it.');
    }

    /**
     * Proposals whose signed copy never arrived.
     *
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    #[Override]
    public function find(): SelectQuery
    {
        $after = (int)Settings::get(self::AFTER_DAYS_PATH, self::AFTER_DAYS);

        $query = $this->proposals->find()
            // Whether the papers went out and came back is the envelope's to say, and the rows
            // print it, so it is read as well as joined.
            ->contain(['Contracts' => ['Customers'], 'ContractVersions', 'CustomerProposals'])
            ->innerJoinWith('Contracts')
            ->innerJoinWith('CustomerProposals');

        LateProposals::unfiled(
            $query,
            'ContractProposals',
            ContractDocuments::MODEL,
            $after,
            'CustomerProposals',
        );

        if ($this->ignore_inactive) {
            $this->onlyRunningContracts($query);
        }

        return $this->scoped($query);
    }
}
