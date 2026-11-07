<?php
declare(strict_types=1);

namespace WorkReports\Test\TestCase\Model\Table;

use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * A number the column cannot hold is told beside its field instead of ending on an error page.
 *
 * @link \WorkReports\Model\Table\WorkReportWorkersTable::validationDefault()
 * @link \WorkReports\Model\Table\WorkReportsTable::validationDefault()
 * @link \WorkReports\Model\Table\WorkReportItemsTable::validationDefault()
 * @link \WorkReports\Model\Table\WorkReportOnCallsTable::validationDefault()
 */
class NumbersWithinTheirColumnsTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function numberProvider(): array
    {
        return [
            'workload of a worker' => ['WorkReports.WorkReportWorkers', 'workload'],
            'workload of a month' => ['WorkReports.WorkReports', 'workload'],
            'hours to invoice' => ['WorkReports.WorkReportItems', 'invoice_hours'],
            'rate multiplier' => ['WorkReports.WorkReportItems', 'rate_multiplier'],
            'hours on call' => ['WorkReports.WorkReportOnCalls', 'hours'],
        ];
    }

    /**
     * The limit is read from the column, so that the two cannot part. The last value also covers
     * what only overflows once the database has rounded it.
     *
     * @param string $alias Table alias.
     * @param string $field Column.
     * @return void
     */
    #[DataProvider('numberProvider')]
    public function testANumberBeyondItsColumnIsRefused(string $alias, string $field): void
    {
        $table = $this->getTableLocator()->get($alias);
        $column = (array)$table->getSchema()->getColumn($field);
        $places = (int)$column['precision'];
        $whole = (int)$column['length'] - $places;

        $largest = str_repeat('9', $whole) . '.' . str_repeat('9', $places);
        $validator = $table->getValidator();

        $this->assertArrayNotHasKey($field, $validator->validate([$field => $largest]), $largest);

        foreach (['1' . str_repeat('0', $whole), $largest . '5'] as $beyond) {
            $this->assertArrayHasKey($field, $validator->validate([$field => $beyond]), $beyond);
        }
    }
}
