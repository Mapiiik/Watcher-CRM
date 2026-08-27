<?php
declare(strict_types=1);

namespace App\Customers\Check;

use Cake\ORM\Query\SelectQuery;
use Override;

/**
 * A customer nothing on file can tell apart from another of the same name.
 *
 * Neither an identity number nor a date of birth. Two people called Jan Novák are then two
 * rows that cannot be told apart, an invoice cannot be chased, and a contract cannot say who
 * signed it.
 *
 * A customer who will not give a date of birth has already been asked and answered, and the
 * label somebody put on to say so takes them out of this - which the label's own SQL never
 * did, so the count drops by about the number of those labels the day this is switched on.
 */
class IncompleteIdentityCheck extends AbstractCustomerCheck
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
        return 'incomplete_identity';
    }

    /**
     * @return string
     */
    #[Override]
    public function title(): string
    {
        return __('Nothing That Tells the Customer Apart');
    }

    /**
     * @return string
     */
    #[Override]
    public function emptyMessage(): string
    {
        return __('Every customer has something that tells them apart from another of the same name.');
    }

    /**
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    #[Override]
    public function find(): SelectQuery
    {
        $query = $this->candidates()->where([
            'Customers.identity_number IS' => null,
            'Customers.date_of_birth IS' => null,
            'Customers.id NOT IN' => $this->excusedBy('incomplete_identity_excused_by'),
        ]);

        return $this->scoped($query);
    }
}
