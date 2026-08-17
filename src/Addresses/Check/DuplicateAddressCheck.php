<?php
declare(strict_types=1);

namespace App\Addresses\Check;

use App\Model\Table\AddressesTable;
use Cake\Database\Expression\IdentifierExpression;
use Cake\Database\ExpressionInterface;
use Cake\ORM\Query\SelectQuery;
use Override;

/**
 * The same place written down twice for one customer.
 *
 * Two rows of the same type at the same street, number and city are one address recorded
 * twice, whatever else differs between them. They are worth clearing on their own, and they
 * are part of what makes {@see \App\Addresses\Check\UnclearBillingAddressCheck} report a
 * customer: two identical installation addresses and no billing address leave the invoice
 * pointing at one of two rows that say the same thing.
 *
 * Rows are compared with the case and the surrounding spaces taken off, because that is how
 * they get typed twice in the first place.
 */
class DuplicateAddressCheck extends AbstractAddressCheck
{
    /**
     * @param \App\Model\Table\AddressesTable $addresses Addresses table.
     */
    public function __construct(private AddressesTable $addresses)
    {
    }

    /**
     * @return string
     */
    #[Override]
    public function id(): string
    {
        return 'duplicate_address';
    }

    /**
     * @return string
     */
    #[Override]
    public function title(): string
    {
        return __('The Same Address More Than Once');
    }

    /**
     * @return string
     */
    #[Override]
    public function emptyMessage(): string
    {
        return __('No customer has the same address on record more than once.');
    }

    /**
     * One row per group of duplicates, not one per address.
     *
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    #[Override]
    public function find(): SelectQuery
    {
        $query = $this->addresses->find();

        $parts = ['street', 'number', 'city'];

        $keys = [
            'customer_id' => 'Addresses.customer_id',
            'type' => 'Addresses.type',
        ];

        // Grouped by the flattened spelling, but shown as one of the spellings that were
        // flattened - the listing is there to be recognised, and a lower-cased address is
        // not what anybody typed. The type has to be said: `min()` returns a float unless
        // told otherwise, and a street name read as a number comes back as zero.
        $shown = [];
        foreach ($parts as $part) {
            $shown[$part] = $query->func()->min('Addresses.' . $part, ['string']);
        }

        return $query
            ->select($keys + $shown + ['total' => $query->func()->count('*')], true)
            // Naming the fields switches the automatic ones off, and the contained customer
            // goes with them - so the listing had a customer to link to and nothing to put
            // in the link. Asked for by table rather than one column at a time, so that the
            // customer arrives as an entity and can be shown the way it is elsewhere.
            ->select($this->addresses->Customers)
            ->contain(['Customers'])
            // Grouped by the customer's own key rather than the column pointing at it, so
            // that the contained customer's other columns come along - Postgres allows the
            // rest of a table wherever its primary key is grouped by.
            ->groupBy(array_merge(
                [new IdentifierExpression('Customers.id')],
                array_values($keys),
                array_map(fn(string $part): ExpressionInterface => $this->normalised($query, $part), $parts),
            ))
            ->having([$query->expr()->gt($query->func()->count('*'), 1, 'integer')])
            ->orderBy(['Addresses.customer_id' => 'ASC']);
    }

    /**
     * A field with the case and the surrounding spaces taken off, and a null read as empty
     * so that two rows that both leave it out still group together.
     *
     * Written out rather than built with `func()`, and deliberately: the builder binds the
     * empty string as a parameter, and the select and the group by would each get a
     * placeholder of their own. Postgres compares the two expressions by their text, sees
     * `:param0` against `:param3`, and refuses the column as ungrouped. With the literal in
     * the SQL both read the same.
     *
     * @param \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface> $query The query
     *   the expression belongs to.
     * @param string $field Column on Addresses.
     * @return \Cake\Database\ExpressionInterface
     */
    private function normalised(SelectQuery $query, string $field): ExpressionInterface
    {
        return $query->expr(sprintf("LOWER(TRIM(COALESCE(Addresses.%s, '')))", $field));
    }
}
