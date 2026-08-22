<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\ContractsTable;
use App\Test\Traits\TableTestTrait;
use App\Test\Traits\WatcherNmsAnswersTrait;
use Cake\TestSuite\TestCase;
use Cake\Utility\Text;
use Override;

/**
 * App\Model\Table\ContractsTable Test Case
 */
class ContractsTableTest extends TestCase
{
    use TableTestTrait;
    use WatcherNmsAnswersTrait;

    /**
     * Test subject
     *
     * @var \App\Model\Table\ContractsTable
     */
    protected $Contracts;

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
        'app.Queues',
        'app.Services',
        'app.Billings',
        'app.EquipmentTypes',
        'app.BorrowedEquipments',
        'app.ContractVersions',
        'app.IpAddresses',
        'app.RemovedIpAddresses',
        'app.IpNetworks',
        'app.RemovedIpNetworks',
        'app.SoldEquipments',
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
        $config = $this->getTableLocator()->exists('Contracts') ? [] : ['className' => ContractsTable::class];
        $this->Contracts = $this->getTableLocator()->get('Contracts', $config);

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
        /** @phpstan-ignore unset.possiblyHookedProperty */
        unset($this->Contracts);

        $this->withoutWatcherNms();

        parent::tearDown();
    }

    /**
     * A new record with nothing filled in is refused - see the trait for why that is the question
     * worth asking here.
     *
     * @return void
     * @link \App\Model\Table\ContractsTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->assertEmptyRecordIsRefused($this->Contracts);
    }

    /**
     * The rules refuse a record whose references point nowhere - see the trait for why that is
     * the question worth asking here.
     *
     * @return void
     * @link \App\Model\Table\ContractsTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $this->assertDanglingReferencesAreRefused($this->Contracts);
    }

    /**
     * A place the network does not keep is refused, the same way `existsIn` refuses a reference of
     * ours - the place of a contract is written by hand and reaches the map, the printed contract
     * and the listings.
     *
     * @return void
     * @link \App\Model\Rule\ExistingAccessPointRule::__invoke()
     */
    public function testAContractNamingAPlaceTheNetworkDoesNotKeepIsRefused(): void
    {
        $this->answerWithTheOneAccessPoint();

        $contract = $this->Contracts->find()->firstOrFail();
        $contract->set('access_point_id', Text::uuid());

        $this->assertFalse((bool)$this->Contracts->save($contract));
        $this->assertArrayHasKey('accessPointIsThere', $contract->getError('access_point_id'));
    }

    /**
     * The same contract with a place the network does keep goes through, which is the branch that
     * would refuse everything if the answer were read the wrong way round.
     *
     * @return void
     * @link \App\Model\Rule\ExistingAccessPointRule::__invoke()
     */
    public function testAContractNamingAPlaceTheNetworkKeepsIsTaken(): void
    {
        $this->answerWithTheOneAccessPoint();

        $contract = $this->Contracts->find()->firstOrFail();
        $contract->set('access_point_id', self::ACCESS_POINT_ID);

        $this->assertNotFalse($this->Contracts->save($contract));
    }

    /**
     * A place nobody could look up is taken on trust. A contract is edited for a dozen reasons that
     * have nothing to do with the network, and none of them are to wait for Watcher NMS.
     *
     * @return void
     * @link \App\Model\Rule\ExistingAccessPointRule::__invoke()
     */
    public function testAPlaceIsTakenOnTrustWhileWatcherNmsSaysNothing(): void
    {
        $this->answerWithAFailure();

        $contract = $this->Contracts->find()->firstOrFail();
        $contract->set('access_point_id', Text::uuid());

        $this->assertNotFalse($this->Contracts->save($contract));
    }
}
