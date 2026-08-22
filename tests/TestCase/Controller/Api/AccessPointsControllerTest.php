<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Api;

use App\Controller\Api\AccessPointsController;
use App\Test\Traits\ControllerTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Cake\Utility\Text;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Controller\Api\AccessPointsController Test Case
 *
 * The one question the other application asks before it lets a place of the network go. What is
 * checked is the answer itself rather than the page around it: a wrong count here does not look
 * wrong anywhere, it lets a mast be deleted out from under a contract.
 */
#[UsesClass(AccessPointsController::class)]
class AccessPointsControllerTest extends TestCase
{
    use ControllerTestTrait;
    use IntegrationTestTrait;

    /**
     * The place of the network the fixtures' contract names.
     *
     * @var string
     */
    private const ACCESS_POINT_ID = 'feedb343-cea8-423f-a409-de4331354217';

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
     * A place something stands on is answered with how much of it there is.
     *
     * @return void
     * @link \App\Controller\Api\AccessPointsController::references()
     */
    public function testAPlaceSomethingStandsOnIsCounted(): void
    {
        $this->taskAt(self::ACCESS_POINT_ID);

        $this->login();
        $this->get('/api/access-points/' . self::ACCESS_POINT_ID . '/references.json');

        $this->assertResponseOk();
        $this->assertSame(
            ['contracts' => 1, 'tasks' => 1],
            $this->viewVariable('references'),
        );
    }

    /**
     * A place nothing here names is answered with nought rather than with nothing, so that the
     * caller never has to read an absent field as a zero.
     *
     * @return void
     * @link \App\Controller\Api\AccessPointsController::references()
     */
    public function testAPlaceNothingNamesIsAnsweredWithNought(): void
    {
        $this->login();
        $this->get('/api/access-points/' . Text::uuid() . '/references.json');

        $this->assertResponseOk();
        $this->assertSame(['contracts' => 0, 'tasks' => 0], $this->viewVariable('references'));
    }

    /**
     * Something that is not the number of a place at all is turned away, rather than answered
     * with a nought that would read as permission to delete.
     *
     * @return void
     * @link \App\Controller\Api\AccessPointsController::references()
     */
    public function testSomethingThatIsNotANumberIsTurnedAway(): void
    {
        $this->login();
        $this->get('/api/access-points/not-a-number/references.json');

        $this->assertResponseError();
    }

    /**
     * A task filed at a place, of whatever type and state the fixtures happen to carry.
     *
     * @param string $accessPointId The place it is to be done at.
     * @return void
     */
    private function taskAt(string $accessPointId): void
    {
        $tasks = $this->getTableLocator()->get('Tasks');
        $written = $tasks->find()->firstOrFail();
        $contract = $this->getTableLocator()->get('Contracts')->find()->firstOrFail();

        // the fixtures write the identity column with the values they carry, which leaves the
        // identity itself where it started
        $this->advanceIdentity('Tasks', 'nid');

        $tasks->saveOrFail($tasks->newEntity([
            'task_state_id' => $written->get('task_state_id'),
            'task_type_id' => $written->get('task_type_id'),
            // the type the fixtures carry insists on both of these
            'customer_id' => $contract->get('customer_id'),
            'contract_id' => $contract->get('id'),
            'access_point_id' => $accessPointId,
            'subject' => 'Written by the test',
            'priority' => 1,
        ]));
    }
}
