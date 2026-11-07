<?php
declare(strict_types=1);

namespace WorkReports\Test\TestCase\Model\Table;

use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use WorkReports\Model\Table\WorkLabelsTable;

/**
 * WorkReports\Model\Table\WorkLabelsTable Test Case
 */
#[CoversClass(WorkLabelsTable::class)]
class WorkLabelsTableTest extends TestCase
{
    /**
     * The colour of a label ends up in a style attribute, so only a colour gets in.
     *
     * @return void
     * @link \WorkReports\Model\Table\WorkLabelsTable::validationDefault()
     */
    public function testOnlyAColorIsStored(): void
    {
        $labels = $this->getTableLocator()->get('WorkReports.WorkLabels');

        $this->assertSame([], $labels->newEntity(['color' => '#ffcdd2'])->getError('color'));
        $this->assertNotSame([], $labels->newEntity(['color' => '"><img'])->getError('color'));
    }
}
