<?php
declare(strict_types=1);

namespace App\Customers\Check;

use Cake\ORM\Query\SelectQuery;
use Override;

/**
 * A customer with no e-mail address on file.
 *
 * Everything the application sends on its own goes by e-mail - the invoice, the notice of an
 * outage, the word that a contract is running out - so a customer without one only hears from
 * us when somebody picks up the telephone.
 *
 * Some of them have been asked and said no, and the label saying so takes them out of this.
 */
class MissingEmailCheck extends AbstractMissingContactCheck
{
    /**
     * @return string
     */
    #[Override]
    public function id(): string
    {
        return 'missing_email';
    }

    /**
     * @return string
     */
    #[Override]
    public function title(): string
    {
        return __('No E-mail Address');
    }

    /**
     * @return string
     */
    #[Override]
    public function emptyMessage(): string
    {
        return __('Every customer has an e-mail address on file.');
    }

    /**
     * @return string|null
     */
    #[Override]
    protected function excusingSetting(): ?string
    {
        return 'missing_email_excused_by';
    }

    /**
     * A row with nothing written in it is the same as no row at all.
     *
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    #[Override]
    protected function customersWhoHaveOne(): SelectQuery
    {
        return $this->customers->Emails
            ->find()
            ->select(['Emails.customer_id'], true)
            ->where(['Emails.email IS NOT' => null, 'Emails.email !=' => '']);
    }
}
