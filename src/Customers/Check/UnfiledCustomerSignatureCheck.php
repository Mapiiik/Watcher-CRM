<?php
declare(strict_types=1);

namespace App\Customers\Check;

use App\Proposals\LateProposals;
use App\Service\CustomerPrint\CustomerDocuments;
use Cake\ORM\Query\SelectQuery;
use Override;
use Settings\Utility\Settings;

/**
 * The customer signed and the signed copy has not been filed.
 *
 * The one of the three that matters most: a consent is the paper somebody may one day have to be
 * shown.
 */
class UnfiledCustomerSignatureCheck extends AbstractCustomerProposalCheck
{
    /**
     * How long after the signature the scan may be missing, if nothing says otherwise.
     */
    private const AFTER_DAYS = 7;

    /**
     * Where the settings say how long that is.
     */
    private const AFTER_DAYS_PATH = 'core.customers.documents.unfiled_after_days';

    /**
     * @return string
     */
    #[Override]
    public function id(): string
    {
        return 'unfiled_customer_signature';
    }

    /**
     * @return string
     */
    #[Override]
    public function title(): string
    {
        return __('Consent Nobody Filed');
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
     * Rounds whose signed copy never arrived.
     *
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    #[Override]
    public function find(): SelectQuery
    {
        $after = (int)Settings::get(self::AFTER_DAYS_PATH, self::AFTER_DAYS);

        $query = $this->candidates();

        LateProposals::unfiled($query, 'CustomerProposals', CustomerDocuments::MODEL, $after);

        return $this->scoped($query);
    }
}
