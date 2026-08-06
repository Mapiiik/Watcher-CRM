<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Database\FulltextSearchCustomersDocument;
use Cake\ORM\Table;
use Override;

/**
 * FulltextSearchCustomers Model
 *
 * Holds one text document per customer for the advanced search to be answered from. Nothing here
 * is entered by anyone - it is what the customer data already says, restated in a form an index
 * can be built over - so a document is never written to, only rebuilt from its source.
 *
 * Deliberately not an `AppTable`: there is no author to record and nothing worth auditing, and
 * an audit trail of a derived table would be noise over every save in the application.
 *
 * @extends \Cake\ORM\Table<array<string, \Cake\ORM\Behavior>>
 */
class FulltextSearchCustomersTable extends Table
{
    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    #[Override]
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('fulltext_search_customers');
        $this->setPrimaryKey('customer_id');
    }

    /**
     * Rebuilds the documents of the given customers.
     *
     * Costs about 0.15 ms per customer, so it is meant to be called from wherever anything the
     * document is built from has been saved - see `FulltextSearchCustomersBehavior`. Ids of customers that
     * no longer exist are not an error: they simply have no document to build, and the row they
     * had went with them.
     *
     * @param array<string> $customerIds Ids of the customers to rebuild.
     * @return int The number of documents written.
     */
    public function refresh(array $customerIds): int
    {
        $customerIds = array_values(array_unique($customerIds));

        if ($customerIds === []) {
            return 0;
        }

        return $this->getConnection()->execute(
            FulltextSearchCustomersDocument::forCustomers(count($customerIds)),
            [$this->customerSeries(), ...$customerIds],
            ['integer', ...array_fill(0, count($customerIds), 'string')],
        )->rowCount();
    }

    /**
     * Rebuilds the documents of every customer.
     *
     * Costs about 226 ms over 7500 customers. It is what puts the table right again after anything
     * that wrote to the database without going through the application, and after the customer
     * series has been changed - the customer number is part of the document, so every document
     * built before the change is answering to a number nobody has any more.
     *
     * @return int The number of documents written.
     */
    public function rebuild(): int
    {
        return $this->getConnection()->execute(
            FulltextSearchCustomersDocument::forEveryCustomer(),
            [$this->customerSeries()],
            ['integer'],
        )->rowCount();
    }

    /**
     * The offset added to the customer number before it is indexed, the same one the listing adds
     * before it shows it.
     *
     * @return int
     */
    protected function customerSeries(): int
    {
        return (int)env('CUSTOMER_SERIES', '0');
    }
}
