<?php
declare(strict_types=1);

namespace App\Contracts\Check;

use App\Proposals\LateProposals;
use App\Service\ContractPrint\ContractDocuments;
use Cake\ORM\Query\SelectQuery;
use Override;

/**
 * The customer signed and the signed copy has not been filed.
 *
 * Said out loud because nothing else would ever say it: the proposal is signed, it applies,
 * the service runs, and the paper stays in somebody's inbox for good. Carried-over proposals are
 * reported too - that is where the papers are needed most.
 */
class UnfiledContractProposalCheck extends AbstractContractProposalCheck
{
    /**
     * How long after the signature the scan may be missing, if nothing says otherwise.
     */
    private const AFTER_DAYS = 7;

    /**
     * Where the settings say how long that is.
     */
    private const AFTER_DAYS_PATH = 'core.contracts.paperwork.unfiled.after_signature_days';

    /**
     * @return string
     */
    #[Override]
    public function id(): string
    {
        return 'unfiled_contract_proposal';
    }

    /**
     * @return string
     */
    #[Override]
    public function title(): string
    {
        return __('Contract Proposal Without Its Documents on File');
    }

    /**
     * @return string
     */
    #[Override]
    public function emptyMessage(): string
    {
        return __('Every signature recorded has its signed documents on file.');
    }

    /**
     * Proposals whose signed copy never arrived.
     *
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    #[Override]
    public function find(): SelectQuery
    {
        $after = $this->days(self::AFTER_DAYS_PATH, self::AFTER_DAYS);

        $query = $this->candidates()
            // Nothing of ours is signed for a contract whose service keeps no versions.
            ->innerJoinWith('Contracts.ServiceTypes')
            ->where(['ServiceTypes.have_contract_versions' => true]);

        LateProposals::unfiled(
            $query,
            'ContractProposals',
            ContractDocuments::MODEL,
            $after,
            'CustomerProposals',
        );

        $this->onlyWhatIsRunning($query);

        return $this->scoped($query);
    }
}
