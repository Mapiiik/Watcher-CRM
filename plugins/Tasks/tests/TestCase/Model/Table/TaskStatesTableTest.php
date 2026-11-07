<?php
declare(strict_types=1);

namespace Tasks\Test\TestCase\Model\Table;

use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use Tasks\Model\Table\TaskStatesTable;

/**
 * Tasks\Model\Table\TaskStatesTable Test Case
 */
#[CoversClass(TaskStatesTable::class)]
class TaskStatesTableTest extends TestCase
{
    /**
     * The colour of a state ends up in a style attribute, so only a colour gets in.
     *
     * @param string $color What is being stored.
     * @param bool $accepted Whether it is a colour.
     * @return void
     * @link \Tasks\Model\Table\TaskStatesTable::validationDefault()
     */
    #[TestWith(['#c8e6c9', true])]
    #[TestWith(['#C8E6C9', true])]
    #[TestWith(['#fff', false])]
    #[TestWith(['"><img', false])]
    #[TestWith(['', false])]
    public function testOnlyAColorIsStored(string $color, bool $accepted): void
    {
        $state = $this->getTableLocator()->get('Tasks.TaskStates')->newEntity(['color' => $color]);

        $this->assertSame($accepted, $state->getError('color') === []);
    }
}
