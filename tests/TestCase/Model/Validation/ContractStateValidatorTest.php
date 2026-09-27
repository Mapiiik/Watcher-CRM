<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Validation;

use App\Model\Entity\Contract;
use App\Model\Entity\ServiceType;
use App\Model\Table\ContractsTable;
use App\Model\Validation\ContractStateValidator;
use App\Test\Traits\WatcherNmsAnswersTrait;
use Cake\I18n\Date;
use Cake\TestSuite\TestCase;
use Override;

/**
 * App\Model\Validation\ContractStateValidator Test Case
 *
 * These cover the flags that ask a contract's end dates to agree with how far its records reach,
 * and the open task a state waits for.
 * The validator is asked directly rather than through a save, because a save answers with one
 * `false` for every reason at once, and the question here is which reason.
 */
class ContractStateValidatorTest extends TestCase
{
    use WatcherNmsAnswersTrait;

    /**
     * The contract the fixtures hang their billings, versions and equipment on.
     *
     * @var string
     */
    private const CONTRACT_ID = '7f76dc3f-a11b-4109-958b-4b0382545a66';

    /**
     * The date the contract ends on, both its termination and its uninstallation.
     *
     * @var string
     */
    private const END_DATE = '2022-12-11';

    /**
     * A day past the end, for the records that are meant to reach too far.
     *
     * @var string
     */
    private const PAST_THE_END = '2022-12-12';

    /**
     * The type of the one task the fixtures carry, filed under the contract's customer.
     *
     * @var string
     */
    private const TASK_TYPE_ID = 'dbf92ff5-8d55-449e-8295-952bf52d6ef5';

    /**
     * A task state that is not completed.
     *
     * @var string
     */
    private const OPEN_TASK_STATE_ID = 'f1e5d0c2-0000-4000-8000-000000000001';

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
        'app.ConnectionProfiles',
        'app.Services',
        'app.Billings',
        'app.EquipmentTypes',
        'app.BorrowedEquipments',
        'app.ContractVersions',
        'app.TaskTypes',
        'app.TaskStates',
        'app.Tasks',
    ];

    /**
     * Test subject
     *
     * @var \App\Model\Table\ContractsTable
     */
    private ContractsTable $Contracts;

    /**
     * setUp method
     *
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->Contracts = $this->getTableLocator()->get('Contracts');

        $this->withWatcherNms();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    #[Override]
    protected function tearDown(): void
    {
        unset($this->Contracts);

        $this->withoutWatcherNms();

        parent::tearDown();
    }

    /**
     * The contract, on its way into a state that asks for the named flags and nothing else.
     *
     * The flags hang on a state change, so the entity says one is under way.
     *
     * @param array<string, bool|string> $flags Flags to raise on the target state.
     * @return \App\Model\Entity\Contract
     */
    private function contractChangingIntoAStateWith(array $flags): Contract
    {
        $contract = $this->Contracts->get(self::CONTRACT_ID, contain: ['ContractStates']);
        $contract->contract_state->patch($flags);
        $contract->setDirty('contract_state_id', true);

        return $contract;
    }

    /**
     * Leaves every one of the contract's records in the table ending on the given date.
     *
     * @param string $table Table holding the records.
     * @param string $column Column carrying the end date.
     * @param string|null $end Date to end them on, or null to leave them open.
     * @return void
     */
    private function endThemAllOn(string $table, string $column, ?string $end): void
    {
        $this->getTableLocator()->get($table)
            ->updateAll([$column => $end], ['contract_id' => self::CONTRACT_ID]);
    }

    /**
     * Moves a single one of the contract's records to a different end date.
     *
     * @param string $table Table holding the records.
     * @param string $column Column carrying the end date.
     * @param string|null $end Date to move it to.
     * @return void
     */
    private function endOneOn(string $table, string $column, ?string $end): void
    {
        $records = $this->getTableLocator()->get($table);
        $one = $records->find()->where(['contract_id' => self::CONTRACT_ID])->firstOrFail();

        $records->updateAll([$column => $end], ['id' => $one->get('id')]);
    }

    /**
     * Billings that all end with the services are what the flag asks for. The contract carries
     * two of them, so this is also the case of several sharing the last date.
     *
     * @return void
     * @link \App\Model\Validation\ContractStateValidator::validateTerminationDateMatchingBillings()
     */
    public function testBillingsEndingWithTheServicesAreTaken(): void
    {
        $this->endThemAllOn('Billings', 'billing_until', self::END_DATE);

        $errors = (new ContractStateValidator())->validate(
            $this->contractChangingIntoAStateWith(['requires_billings_matching_termination' => true]),
        );

        $this->assertSame([], $errors);
    }

    /**
     * One billing of the two running on past the end is the whole point of the flag - the
     * customer keeps being charged for a service that is over.
     *
     * @return void
     * @link \App\Model\Validation\ContractStateValidator::validateTerminationDateMatchingBillings()
     */
    public function testABillingReachingPastTheTerminationOfServicesIsRefused(): void
    {
        $this->endThemAllOn('Billings', 'billing_until', self::END_DATE);
        $this->endOneOn('Billings', 'billing_until', self::PAST_THE_END);

        $errors = (new ContractStateValidator())->validate(
            $this->contractChangingIntoAStateWith(['requires_billings_matching_termination' => true]),
        );

        $this->assertArrayHasKey('termination_date', $errors);
    }

    /**
     * The last billing stopping early is refused as well - the flag asks for agreement, not
     * merely for nothing to overshoot.
     *
     * @return void
     * @link \App\Model\Validation\ContractStateValidator::validateTerminationDateMatchingBillings()
     */
    public function testABillingEndingBeforeTheTerminationOfServicesIsRefused(): void
    {
        $this->endThemAllOn('Billings', 'billing_until', '2022-11-30');

        $errors = (new ContractStateValidator())->validate(
            $this->contractChangingIntoAStateWith(['requires_billings_matching_termination' => true]),
        );

        $this->assertArrayHasKey('termination_date', $errors);
    }

    /**
     * An open billing has no end to compare, and the last date would pass straight over it. It
     * is the one that reaches furthest of all, so it is asked about separately.
     *
     * @return void
     * @link \App\Model\Validation\ContractStateValidator::validateTerminationDateMatchingBillings()
     */
    public function testABillingWithNoEndDateIsRefused(): void
    {
        $this->endThemAllOn('Billings', 'billing_until', self::END_DATE);
        $this->endOneOn('Billings', 'billing_until', null);

        $errors = (new ContractStateValidator())->validate(
            $this->contractChangingIntoAStateWith(['requires_billings_matching_termination' => true]),
        );

        $this->assertArrayHasKey('termination_date', $errors);
    }

    /**
     * A contract nobody ever billed has nothing to disagree with. Whether billings ought to be
     * there at all is what the flags about their presence are for.
     *
     * @return void
     * @link \App\Model\Validation\ContractStateValidator::validateTerminationDateMatchingBillings()
     */
    public function testAContractWithNoBillingsAtAllIsTaken(): void
    {
        $this->getTableLocator()->get('Billings')->deleteAll(['contract_id' => self::CONTRACT_ID]);

        $errors = (new ContractStateValidator())->validate(
            $this->contractChangingIntoAStateWith(['requires_billings_matching_termination' => true]),
        );

        $this->assertSame([], $errors);
    }

    /**
     * Without a termination date there is nothing to compare against, and saying so is the job
     * of `requires_termination_date`. Two complaints about one missing date help nobody.
     *
     * @return void
     * @link \App\Model\Validation\ContractStateValidator::validateTerminationDateMatchingBillings()
     */
    public function testBillingsAreNotLookedAtWithoutATerminationDate(): void
    {
        $this->endThemAllOn('Billings', 'billing_until', null);

        $contract = $this->contractChangingIntoAStateWith(['requires_billings_matching_termination' => true]);
        $contract->set('termination_date', null);

        $this->assertSame([], (new ContractStateValidator())->validate($contract));
    }

    /**
     * A state that does not ask for the agreement does not get it looked at.
     *
     * @return void
     * @link \App\Model\Validation\ContractStateValidator::validate()
     */
    public function testBillingsAreNotLookedAtWithoutTheFlag(): void
    {
        $this->endThemAllOn('Billings', 'billing_until', self::PAST_THE_END);

        $errors = (new ContractStateValidator())->validate($this->contractChangingIntoAStateWith([]));

        $this->assertSame([], $errors);
    }

    /**
     * A contract already sitting in the state, edited for something else entirely, is left
     * alone. Records kept from before are not to stand in the way of an unrelated change.
     *
     * @return void
     * @link \App\Model\Validation\ContractStateValidator::validate()
     */
    public function testBillingsAreNotLookedAtWhileTheDatesStandStill(): void
    {
        $this->endThemAllOn('Billings', 'billing_until', self::PAST_THE_END);

        $contract = $this->contractChangingIntoAStateWith(['requires_billings_matching_termination' => true]);
        $contract->setDirty('contract_state_id', false);
        $contract->set('note', 'Edited for another reason');

        $this->assertSame([], (new ContractStateValidator())->validate($contract));
    }

    /**
     * Moving the termination date itself is looked at even without a state change, which is the
     * other half of the same idea - the date may not be written past what the records say.
     *
     * @return void
     * @link \App\Model\Validation\ContractStateValidator::validate()
     */
    public function testMovingTheTerminationDateAloneIsLookedAt(): void
    {
        $this->endThemAllOn('Billings', 'billing_until', self::END_DATE);

        $contract = $this->contractChangingIntoAStateWith(['requires_billings_matching_termination' => true]);
        $contract->setDirty('contract_state_id', false);
        $contract->set('termination_date', new Date(self::PAST_THE_END));

        $this->assertArrayHasKey('termination_date', (new ContractStateValidator())->validate($contract));
    }

    /**
     * The same question of the contract versions, which is where a service that is over can go
     * on being one the customer is bound to.
     *
     * @return void
     * @link \App\Model\Validation\ContractStateValidator::validateTerminationDateMatchingContractVersions()
     */
    public function testAContractVersionReachingPastTheTerminationOfServicesIsRefused(): void
    {
        $this->endThemAllOn('ContractVersions', 'valid_until', self::PAST_THE_END);

        $errors = (new ContractStateValidator())->validate(
            $this->contractChangingIntoAStateWith(['requires_versions_matching_termination' => true]),
        );

        $this->assertArrayHasKey('termination_date', $errors);
    }

    /**
     * The version ending with the services is what is asked for.
     *
     * @return void
     * @link \App\Model\Validation\ContractStateValidator::validateTerminationDateMatchingContractVersions()
     */
    public function testAContractVersionEndingWithTheServicesIsTaken(): void
    {
        $this->endThemAllOn('ContractVersions', 'valid_until', self::END_DATE);

        $errors = (new ContractStateValidator())->validate(
            $this->contractChangingIntoAStateWith(['requires_versions_matching_termination' => true]),
        );

        $this->assertSame([], $errors);
    }

    /**
     * A version left open runs on without an end, the same as an open billing does.
     *
     * @return void
     * @link \App\Model\Validation\ContractStateValidator::validateTerminationDateMatchingContractVersions()
     */
    public function testAContractVersionWithNoEndDateIsRefused(): void
    {
        $this->endThemAllOn('ContractVersions', 'valid_until', null);

        $errors = (new ContractStateValidator())->validate(
            $this->contractChangingIntoAStateWith(['requires_versions_matching_termination' => true]),
        );

        $this->assertArrayHasKey('termination_date', $errors);
    }

    /**
     * A service type that keeps no contract versions cannot be asked to have them agree, the
     * same way the flags about their presence are skipped for it.
     *
     * @return void
     * @link \App\Model\Validation\ContractStateValidator::validate()
     */
    public function testContractVersionsAreNotLookedAtForAServiceTypeWithoutThem(): void
    {
        $this->endThemAllOn('ContractVersions', 'valid_until', self::PAST_THE_END);

        $contract = $this->contractChangingIntoAStateWith(['requires_versions_matching_termination' => true]);
        $contract->set('service_type', new ServiceType(['have_contract_versions' => false]));

        $this->assertSame([], (new ContractStateValidator())->validate($contract));
    }

    /**
     * Equipment lent on past the uninstallation is hardware nobody is going to come back for.
     *
     * @return void
     * @link \App\Model\Validation\ContractStateValidator::validateUninstallationDateMatchingBorrowedEquipments()
     */
    public function testBorrowedEquipmentLentPastTheUninstallationIsRefused(): void
    {
        $this->endThemAllOn('BorrowedEquipments', 'borrowed_until', self::PAST_THE_END);

        $errors = (new ContractStateValidator())->validate(
            $this->contractChangingIntoAStateWith(['requires_equipments_matching_uninstallation' => true]),
        );

        $this->assertArrayHasKey('uninstallation_date', $errors);
    }

    /**
     * Equipment handed back on the day of the uninstallation is what is asked for.
     *
     * @return void
     * @link \App\Model\Validation\ContractStateValidator::validateUninstallationDateMatchingBorrowedEquipments()
     */
    public function testBorrowedEquipmentReturnedOnTheUninstallationIsTaken(): void
    {
        $this->endThemAllOn('BorrowedEquipments', 'borrowed_until', self::END_DATE);

        $errors = (new ContractStateValidator())->validate(
            $this->contractChangingIntoAStateWith(['requires_equipments_matching_uninstallation' => true]),
        );

        $this->assertSame([], $errors);
    }

    /**
     * Equipment still out has no return date at all, and the complaint lands on the
     * uninstallation date rather than on the termination one.
     *
     * @return void
     * @link \App\Model\Validation\ContractStateValidator::validateUninstallationDateMatchingBorrowedEquipments()
     */
    public function testBorrowedEquipmentWithNoReturnDateIsRefused(): void
    {
        $this->endThemAllOn('BorrowedEquipments', 'borrowed_until', null);

        $errors = (new ContractStateValidator())->validate(
            $this->contractChangingIntoAStateWith(['requires_equipments_matching_uninstallation' => true]),
        );

        $this->assertArrayHasKey('uninstallation_date', $errors);
        $this->assertArrayNotHasKey('termination_date', $errors);
    }

    /**
     * Leaves the customer's task open, or files it under the contract instead.
     *
     * @param array<string, mixed> $fields Fields to set on the task.
     * @return void
     */
    private function changeTheTask(array $fields): void
    {
        $this->getTableLocator()->get('Tasks')->updateAll($fields, ['task_type_id' => self::TASK_TYPE_ID]);
    }

    /**
     * An open task on the customer is enough for the flag asking for one there, without any on
     * the contract.
     *
     * @return void
     * @link \App\Model\Validation\ContractStateValidator::validateRequiresOpenCustomerTaskType()
     */
    public function testAnOpenTaskOnTheCustomerIsTaken(): void
    {
        $this->changeTheTask(['task_state_id' => self::OPEN_TASK_STATE_ID]);

        $errors = (new ContractStateValidator())->validate(
            $this->contractChangingIntoAStateWith(['requires_open_customer_task_type_id' => self::TASK_TYPE_ID]),
        );

        $this->assertSame([], $errors);
    }

    /**
     * The same task does not do for the flag asking for one on the contract.
     *
     * @return void
     * @link \App\Model\Validation\ContractStateValidator::validateRequiresOpenTaskType()
     */
    public function testAnOpenTaskOnTheCustomerDoesNotDoForTheContract(): void
    {
        $this->changeTheTask(['task_state_id' => self::OPEN_TASK_STATE_ID]);

        $errors = (new ContractStateValidator())->validate(
            $this->contractChangingIntoAStateWith(['requires_open_task_type_id' => self::TASK_TYPE_ID]),
        );

        $this->assertArrayHasKey('contract_state_id', $errors);
    }

    /**
     * A task on the contract without the customer filled in counts for the customer as well.
     *
     * @return void
     * @link \App\Model\Validation\ContractStateValidator::validateRequiresOpenCustomerTaskType()
     */
    public function testAnOpenTaskOnTheContractIsTakenForTheCustomer(): void
    {
        $this->changeTheTask([
            'task_state_id' => self::OPEN_TASK_STATE_ID,
            'customer_id' => null,
            'contract_id' => self::CONTRACT_ID,
        ]);

        $errors = (new ContractStateValidator())->validate(
            $this->contractChangingIntoAStateWith(['requires_open_customer_task_type_id' => self::TASK_TYPE_ID]),
        );

        $this->assertSame([], $errors);
    }

    /**
     * A completed task on the customer is not the open one the state waits for.
     *
     * @return void
     * @link \App\Model\Validation\ContractStateValidator::validateRequiresOpenCustomerTaskType()
     */
    public function testACompletedTaskOnTheCustomerIsRefused(): void
    {
        $errors = (new ContractStateValidator())->validate(
            $this->contractChangingIntoAStateWith(['requires_open_customer_task_type_id' => self::TASK_TYPE_ID]),
        );

        $this->assertArrayHasKey('contract_state_id', $errors);
    }

    /**
     * And the whole way through a save, which is where the flag is read from the state in the
     * database rather than from an entity set up by hand.
     *
     * @return void
     * @link \App\Model\Table\ContractsTable::beforeSave()
     */
    public function testASaveIsStoppedByTheFlagOnTheStateInTheDatabase(): void
    {
        $this->answerWithTheOneAccessPoint();

        $this->endThemAllOn('Billings', 'billing_until', self::PAST_THE_END);
        $this->getTableLocator()->get('ContractStates')->updateAll(
            ['requires_billings_matching_termination' => true],
            ['id IS NOT' => null],
        );

        $contract = $this->Contracts->get(self::CONTRACT_ID);
        $contract->set('access_point_id', self::ACCESS_POINT_ID);
        $contract->setDirty('contract_state_id', true);

        $this->assertFalse((bool)$this->Contracts->save($contract));
        $this->assertNotEmpty($contract->getError('termination_date'));
    }
}
