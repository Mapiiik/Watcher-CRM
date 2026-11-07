<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\TestWith;

/**
 * What the network columns of the database refuse is told beside the field, not by an error page.
 */
class NetworkColumnsTest extends TestCase
{
    use LocatorAwareTrait;

    /**
     * @param string $table The table.
     * @param string $column The column.
     * @param string $wrong What the database would refuse.
     * @param string $right What it takes.
     * @return void
     */
    #[TestWith(['IpNetworks', 'ip_network', '88.1.2.3/29', '88.1.2.0/29'])]
    #[TestWith(['RemovedIpNetworks', 'ip_network', '88.1.2.3/29', '88.1.2.0/29'])]
    public function testAColumnTakesWhatTheDatabaseTakes(string $table, string $column, string $wrong, string $right): void
    {
        $columns = $this->fetchTable($table);

        $this->assertNotEmpty($columns->newEntity([$column => $wrong])->getError($column));
        $this->assertEmpty($columns->newEntity([$column => $right])->getError($column));
    }
}
