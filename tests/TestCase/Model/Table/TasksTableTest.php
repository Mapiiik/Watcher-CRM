<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\TasksTable;
use App\Test\Traits\TableTestTrait;
use Cake\Datasource\EntityInterface;
use Cake\TestSuite\TestCase;
use Override;

/**
 * App\Model\Table\TasksTable Test Case
 */
class TasksTableTest extends TestCase
{
    use TableTestTrait;

    /**
     * Test subject
     *
     * @var \App\Model\Table\TasksTable
     */
    protected $Tasks;

    /**
     * The customer the fixtures carry.
     *
     * @var string
     */
    private const CUSTOMER_ID = '403bab0e-52cd-4a8e-83f8-43c2457d0481';

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.AppUsers',
        'app.AccountingProfiles',
        'app.Customers',
        'app.Countries',
        'app.Addresses',
        'app.Commissions',
        'app.ContractStates',
        'app.ServiceTypes',
        'app.Contracts',
        'app.TaskStates',
        'app.TaskTypes',
        'app.Tasks',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('Tasks') ? [] : ['className' => TasksTable::class];
        $this->Tasks = $this->getTableLocator()->get('Tasks', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    #[Override]
    protected function tearDown(): void
    {
        /** @phpstan-ignore unset.possiblyHookedProperty */
        unset($this->Tasks);

        parent::tearDown();
    }

    /**
     * A new record with nothing filled in is refused - see the trait for why that is the question
     * worth asking here.
     *
     * @return void
     * @link \App\Model\Table\TasksTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->assertEmptyRecordIsRefused($this->Tasks);
    }

    /**
     * The rules refuse a record whose references point nowhere - see the trait for why that is
     * the question worth asking here.
     *
     * @return void
     * @link \App\Model\Table\TasksTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $this->assertDanglingReferencesAreRefused($this->Tasks);
    }

    /**
     * A type that insists on a customer refuses a task that names none, and takes the same task
     * once it does.
     *
     * @return void
     * @link \Tasks\Model\Rule\RequiredLinkRule::__invoke()
     */
    public function testATypeDemandingACustomerRefusesATaskWithoutOne(): void
    {
        $type = $this->taskType(['customer_required' => true, 'contract_required' => false]);

        $refused = $this->Tasks->newEntity($this->task($type) + ['customer_id' => null]);
        $this->assertFalse((bool)$this->Tasks->save($refused));
        $this->assertArrayHasKey('isRequiredCustomerFilled', $refused->getError('customer_id'));

        $taken = $this->Tasks->newEntity($this->task($type) + ['customer_id' => self::CUSTOMER_ID]);
        $this->assertNotFalse($this->Tasks->save($taken), 'The link it asked for is there.');
    }

    /**
     * The same for a contract, which is the second pair this application names.
     *
     * @return void
     * @link \Tasks\Model\Rule\RequiredLinkRule::__invoke()
     */
    public function testATypeDemandingAContractRefusesATaskWithoutOne(): void
    {
        $type = $this->taskType(['customer_required' => false, 'contract_required' => true]);

        $refused = $this->Tasks->newEntity($this->task($type) + ['contract_id' => null]);
        $this->assertFalse((bool)$this->Tasks->save($refused));
        $this->assertArrayHasKey('isRequiredContractFilled', $refused->getError('contract_id'));
    }

    /**
     * A type that insists on nothing takes a task that names nothing, which is the branch that
     * would quietly refuse everything if the flag were read the wrong way round.
     *
     * @return void
     * @link \Tasks\Model\Rule\RequiredLinkRule::__invoke()
     */
    public function testATypeDemandingNothingTakesATaskWithNoLinks(): void
    {
        $type = $this->taskType(['customer_required' => false, 'contract_required' => false]);

        $task = $this->Tasks->newEntity($this->task($type));
        $this->assertNotFalse($this->Tasks->save($task));
    }

    /**
     * A task type asking for exactly what it is given.
     *
     * @param array<string, mixed> $flags What this type insists on.
     */
    private function taskType(array $flags): EntityInterface
    {
        $types = $this->getTableLocator()->get('TaskTypes');

        return $types->saveOrFail($types->newEntity($flags + ['name' => 'Written by the test']));
    }

    /**
     * The least a task needs to be saved at all, so that what refuses it is the rule under test.
     *
     * @param \Cake\Datasource\EntityInterface $type The type it is filed under.
     * @return array<string, mixed>
     */
    private function task(EntityInterface $type): array
    {
        $states = $this->getTableLocator()->get('TaskStates');

        return [
            'task_type_id' => $type->get('id'),
            'task_state_id' => $states->find()->firstOrFail()->get('id'),
            'subject' => 'Written by the test',
            'priority' => 1,
        ];
    }
}
