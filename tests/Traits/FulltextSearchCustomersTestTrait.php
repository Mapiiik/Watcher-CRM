<?php
declare(strict_types=1);

namespace App\Test\Traits;

use App\Database\Expression\FulltextSearchCustomersExpression;

/**
 * Asks the advanced search what it finds, the way the listing asks it.
 *
 * The stored document is worth nothing on its own - what matters is whether the search reads back
 * what was written into it - so the tests go through the same expression the listing uses rather
 * than reading the `tsvector` and judging it themselves.
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
