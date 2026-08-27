<?php
declare(strict_types=1);

namespace App\Customers\Check;

use Cake\ORM\Query\SelectQuery;
use Override;

/**
 * A customer with no telephone number on file.
 *
 * The number is what a technician standing at the door has, and what an outage that cannot
 * wait for e-mail is announced on. Nothing lets a customer off this one: a household without
 * a telephone is not something that happens any more.
 */
class MissingPhoneCheck extends AbstractMissingContactCheck
{
    /**
     * @return string
     */
    #[Override]
    public function id(): string
    {
        return 'missing_phone';
    }

    /**
     * @return string
     */
    #[Override]
    public function title(): string
    {
        return __('No Telephone Number');
    }

    /**
     * @return string
     */
    #[Override]
    public function emptyMessage(): string
    {
        return __('Every customer has a telephone number on file.');
    }

    /**
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    #[Override]
    protected function customersWhoHaveOne(): SelectQuery
    {
        return $this->customers->Phones
            ->find()
            ->select(['Phones.customer_id'], true)
            ->where(['Phones.phone IS NOT' => null, 'Phones.phone !=' => '']);
    }
}
