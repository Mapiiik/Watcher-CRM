<?php
declare(strict_types=1);

namespace App\Customers\Check;

use Cake\ORM\Query\SelectQuery;
use Override;

/**
 * A customer whose consent to their details being kept is not recorded either way.
 *
 * Not the same as having refused: a refusal is put on by hand as a label, and that takes the
 * customer out of this. What is left is the ones nobody has asked, which is the list to work
 * through rather than a fault to correct.
 */
class MissingGdprConsentCheck extends AbstractCustomerCheck
{
    /**
     * The finding is the customer, which the others have in common too, so they are listed by
     * the same template rather than by a copy of it each.
     *
     * @return string
     */
    #[Override]
    public function template(): string
    {
        return 'customer';
    }

    /**
     * @return string
     */
    #[Override]
    public function id(): string
    {
        return 'missing_gdpr_consent';
    }

    /**
     * @return string
     */
    #[Override]
    public function title(): string
    {
        return __('Consent Neither Given Nor Refused');
    }

    /**
     * @return string
     */
    #[Override]
    public function emptyMessage(): string
    {
        return __('Every customer has been asked for their consent.');
    }

    /**
     * Nobody has asked yet is what this is about, so it keeps to the overview rather than
     * calling for attention on the dashboard.
     *
     * @return bool
     */
    #[Override]
    public function onDashboard(): bool
    {
        return false;
    }

    /**
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    #[Override]
    public function find(): SelectQuery
    {
        $query = $this->candidates()->where([
            'Customers.agree_gdpr IS NOT' => true,
            'Customers.id NOT IN' => $this->excusedBy('missing_gdpr_consent_excused_by'),
        ]);

        return $this->scoped($query);
    }
}
