<?php
declare(strict_types=1);

namespace App\Test\Traits;

use App\Database\Expression\FulltextSearchCustomersExpression;

/**
 * Asks the advanced search what it finds, through the same expression the listing uses - what
 * matters is that the search reads back what was written, not what the `tsvector` looks like.
 */
trait FulltextSearchCustomersTestTrait
{
    /**
     * The ids of the customers the advanced search finds for the given term.
     *
     * @param string $search Term to search for.
     * @return array<string>
     */
    protected function customersFoundBy(string $search): array
    {
        $found = $this->getTableLocator()->get('Customers')
            ->find()
            ->where([new FulltextSearchCustomersExpression($search)])
            ->all()
            ->extract('id')
            ->toList();

        return array_values(array_filter($found, is_string(...)));
    }
}
