<?php
declare(strict_types=1);

namespace App\Customers\Check;

use Cake\ORM\Query\SelectQuery;
use Override;

/**
 * Shared ground for the checks about a customer nobody can reach.
 *
 * An e-mail address and a telephone number are on file the same way - a row per one, any
 * number of them - and are missing the same way: no row carrying anything. What differs is
 * which rows to look in and whether somebody may be let off, so that is all a check says.
 */
abstract class AbstractMissingContactCheck extends AbstractCustomerCheck
{
    /**
     * The customers who have at least one, as ids.
     *
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    abstract protected function customersWhoHaveOne(): SelectQuery;

    /**
     * The setting naming the labels that let a customer off, where anything does.
     *
     * @return string|null
     */
    protected function excusingSetting(): ?string
    {
        return null;
    }

    /**
     * Both report the same thing about the same kind of record, so they are listed by the
     * same template rather than by two copies of it.
     *
     * @return string
     */
    #[Override]
    public function template(): string
    {
        return 'customer';
    }

    /**
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    #[Override]
    public function find(): SelectQuery
    {
        $query = $this->candidates()->where(['Customers.id NOT IN' => $this->customersWhoHaveOne()]);

        $excusing = $this->excusingSetting();
        if ($excusing !== null) {
            $query->where(['Customers.id NOT IN' => $this->excusedBy($excusing)]);
        }

        return $this->scoped($query);
    }
}
