<?php
declare(strict_types=1);

namespace App\Dashboard\Card;

use Override;

/**
 * How many customers owe enough, and for long enough, to be cut off.
 */
class DebtorsCard extends AbstractDebtorCard
{
    /**
     * @return string
     */
    #[Override]
    public function id(): string
    {
        return 'debtors';
    }

    /**
     * @return string
     */
    #[Override]
    public function title(): string
    {
        return __('Debtors to Block');
    }

    /**
     * @return list<string>
     */
    #[Override]
    public function roles(): array
    {
        return ['bookkeeper', 'sales-representative', 'sales-manager', 'network-manager'];
    }

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function data(): array
    {
        $debtors = $this->processor()->findFilteredOverdueDebtorIds()->all();

        return [
            'total' => $debtors->count(),
            'overdue_debt' => $debtors->sumOf(fn(array $row): float => (float)$row['overdue_debt']),
        ];
    }
}
