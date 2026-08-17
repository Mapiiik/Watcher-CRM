<?php
declare(strict_types=1);

namespace App\Addresses\Check;

use App\Model\Entity\Customer;
use App\Model\Enum\AddressType;
use App\Model\Enum\BillingAddressProblem;
use App\Model\Table\CustomersTable;
use Cake\Collection\CollectionInterface;
use Cake\Database\Expression\IdentifierExpression;
use Cake\ORM\Association;
use Cake\ORM\Query\SelectQuery;
use Override;

/**
 * Customers whose invoice address cannot be told from the addresses on record.
 *
 * A customer is not asked to keep a billing address, so
 * {@see \App\Model\Entity\Customer::_getBillingAddress()} falls through
 * {@see \App\Model\Enum\AddressType::billingFallback()} until a type has one. That is only
 * sound while the winning type has exactly one address. Where it has several, the getter
 * keeps the last it walked past - which comes out as the lowest id, because `CustomersTable`
 * sorts addresses `id DESC` - and the invoice carries whichever address that happens to be.
 * Where no type has any, the invoice carries no address at all.
 *
 * Asking instead for one address of type Billing would report four customers in five: most
 * keep only the address the service was installed at, and the fallback is what makes that
 * work.
 */
class UnclearBillingAddressCheck extends AbstractAddressCheck
{
    /**
     * @param \App\Model\Table\CustomersTable $customers Customers table.
     * @param bool $ignore_inactive Whether to pass over customers with nothing running.
     */
    public function __construct(private CustomersTable $customers, private bool $ignore_inactive = true)
    {
    }

    /**
     * @return string
     */
    #[Override]
    public function id(): string
    {
        return 'unclear_billing_address';
    }

    /**
     * @return string
     */
    #[Override]
    public function title(): string
    {
        return __('Unclear Billing Address');
    }

    /**
     * @return string
     */
    #[Override]
    public function emptyMessage(): string
    {
        return __('Every customer has one address an invoice can be sent to.');
    }

    /**
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    #[Override]
    public function find(): SelectQuery
    {
        $query = $this->customers
            ->find()
            // So that a listing can show which address the fallback is currently landing on.
            // Fetched by its own select rather than by the default subquery: that strategy
            // joins this query back in as a derived table, which drops the counts the order
            // below is built from and leaves the fetch asking for an ungrouped column.
            ->contain(['Addresses' => ['strategy' => Association::STRATEGY_SELECT]])
            ->leftJoin(
                ['AddressCounts' => $this->counts()],
                ['AddressCounts.customer_id' => new IdentifierExpression('Customers.id')],
            );

        $totals = [];
        foreach (AddressType::billingFallback() as $type) {
            $totals[$this->alias($type)] = $query->func()->coalesce(
                [new IdentifierExpression('AddressCounts.' . $this->alias($type)), 0],
                ['integer'],
            );
        }

        $query->select($this->customers)->select($totals);

        // Walked in the fallback order. A type only decides the invoice address while every
        // type ahead of it has nothing, so each reason carries those as zero. Once the loop
        // has been all the way round, $exhausted says the fallback found nothing at all.
        $reasons = [];
        $exhausted = [];

        foreach ($totals as $total) {
            $reasons[] = $query->expr()->and(
                array_merge($exhausted, [$query->expr()->gt($total, 1, 'integer')]),
            );

            $exhausted[] = $query->expr()->eq($total, 0, 'integer');
        }

        $missing = $query->expr()->and($exhausted);
        $reasons[] = $missing;

        if ($this->ignore_inactive) {
            $query->where(['Customers.id IN' => $this->activeCustomerIds()]);
        }

        return $query
            ->where($query->expr()->or($reasons))
            // an invoice with no address at all is worse than one addressed arbitrarily
            ->orderBy([
                $query->expr()->case()->when($missing)->then(0)->else(1),
                'Customers.nid' => 'ASC',
            ])
            ->formatResults(fn(CollectionInterface $customers): CollectionInterface => $customers->each(
                function (Customer $customer): void {
                    $customer->billing_address_problem = BillingAddressProblem::fromCounts(
                        $this->countsOf($customer),
                    );
                },
            ));
    }

    /**
     * How many addresses of each of the fallback's types every customer has.
     *
     * Customers with no address at all have no row here, which is why the outer query joins
     * this rather than counting inside it, and reads the missing row as zero.
     *
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    private function counts(): SelectQuery
    {
        $counts = $this->customers->Addresses->find();

        $fields = ['customer_id' => 'Addresses.customer_id'];
        foreach (AddressType::billingFallback() as $type) {
            $fields[$this->alias($type)] = $counts->func()->count(
                $counts->expr()->case()->when(['Addresses.type' => $type])->then(1),
            );
        }

        return $counts->select($fields, true)->groupBy('Addresses.customer_id');
    }

    /**
     * @param \App\Model\Enum\AddressType $type The type being counted.
     * @return string
     */
    private function alias(AddressType $type): string
    {
        return strtolower($type->name) . '_total';
    }

    /**
     * The counts back off a fetched customer, keyed the way
     * {@see \App\Model\Enum\BillingAddressProblem::fromCounts()} reads them.
     *
     * @param \App\Model\Entity\Customer $customer The fetched customer.
     * @return array<string, int>
     */
    private function countsOf(Customer $customer): array
    {
        $counts = [];
        foreach (AddressType::billingFallback() as $type) {
            $counts[$type->name] = (int)$customer->get($this->alias($type));
        }

        return $counts;
    }
}
