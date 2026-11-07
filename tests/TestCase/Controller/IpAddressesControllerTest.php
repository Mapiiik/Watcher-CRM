<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\IpAddressesController;
use App\Model\Enum\IpAddressTypeOfUse;
use App\Test\Traits\ConfigureTestTrait;
use App\Test\Traits\ControllerTestTrait;
use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\Http\TestSuite\HttpClientTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Controller\IpAddressesController Test Case
 *
 * Smoke tests: every action is requested once and has to answer. They are deliberately shallow -
 * their job is to notice an action that stopped answering at all, which is how the query building
 * bugs in this application have shown up.
 */
#[UsesClass(IpAddressesController::class)]
class IpAddressesControllerTest extends TestCase
{
    use ConfigureTestTrait;
    use ControllerTestTrait;
    use HttpClientTrait;
    use IntegrationTestTrait;

    /**
     * Customer the nested routes hang off.
     *
     * @var string
     */
    private const CUSTOMER_ID = '403bab0e-52cd-4a8e-83f8-43c2457d0481';

    /**
     * Contract the nested routes hang off.
     *
     * @var string
     */
    private const CONTRACT_ID = '7f76dc3f-a11b-4109-958b-4b0382545a66';

    /**
     * Things of the NMS's, which has a database of its own - these stand for what it would answer.
     *
     * @var string
     */
    private const RANGE_ID = '2a1b6c4d-0e5f-4a3b-9c8d-7e6f5a4b3c2d';

    /**
     * @var string
     */
    private const ACCESS_POINT_ID = '3b2c7d5e-1f6a-4b4c-8d9e-6f5a4b3c2d1e';

    /**
     * @var string
     */
    private const ROUTEROS_DEVICE_ID = '4c3d8e6f-2a7b-4c5d-9e8f-5a4b3c2d1e0f';

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Users',
        'app.AccountingProfiles',
        'app.Customers',
        'app.Countries',
        'app.Addresses',
        'app.Commissions',
        'app.ContractStates',
        'app.ServiceTypes',
        'app.Contracts',
        'app.IpAddresses',
        'app.RemovedIpAddresses',
    ];

    /**
     * tearDown method
     *
     * @return void
     */
    #[Override]
    public function tearDown(): void
    {
        $this->restoreConfigure();

        parent::tearDown();
    }

    /**
     * The listing renders.
     *
     * @return void
     * @link \App\Controller\IpAddressesController::index()
     */
    public function testIndex(): void
    {
        $this->login();
        $this->get('/ip-addresses');

        $this->assertResponseOk();
    }

    /**
     * The listing renders with the search filled in, which builds a different query than the plain
     * listing does and is therefore worth requesting on its own.
     *
     * @return void
     * @link \App\Controller\IpAddressesController::index()
     */
    public function testIndexWithSearch(): void
    {
        $this->login();
        $this->get('/ip-addresses?search=Lorem');

        $this->assertResponseOk();
    }

    /**
     * The detail of a record renders.
     *
     * @return void
     * @link \App\Controller\IpAddressesController::view()
     */
    public function testView(): void
    {
        $this->login();
        $this->get('/ip-addresses/view/' . $this->firstId('IpAddresses'));

        $this->assertResponseOk();
    }

    /**
     * The form for a new record renders.
     *
     * @return void
     * @link \App\Controller\IpAddressesController::add()
     */
    public function testAdd(): void
    {
        $this->login();
        $this->get('/ip-addresses/add');

        $this->assertResponseOk();
    }

    /**
     * The form of an existing record renders.
     *
     * @return void
     * @link \App\Controller\IpAddressesController::edit()
     */
    public function testEdit(): void
    {
        $this->login();
        $this->get('/ip-addresses/edit/' . $this->firstId('IpAddresses'));

        $this->assertResponseOk();
    }

    /**
     * An address goes and leaves behind who held it, which is what is asked for when somebody wants
     * to know who used it on a given day.
     *
     * @return void
     * @link \App\Controller\IpAddressesController::delete()
     */
    public function testDelete(): void
    {
        $id = $this->firstId('IpAddresses');
        $table = $this->fetchTable('IpAddresses');
        $address = $table->get($id);
        $removed = $this->fetchTable('RemovedIpAddresses');
        $before = $removed->find()->count();

        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/ip-addresses/delete/' . $id);

        $this->assertRedirect();
        $this->assertFalse($table->exists(['id' => $id]));
        $this->assertSame($before + 1, $removed->find()->count());

        $history = $removed->find()->where(['ip_address' => $address->get('ip_address')])->firstOrFail();
        $this->assertSame($address->get('contract_id'), $history->get('contract_id'));
        $this->assertSame($address->get('customer_id'), $history->get('customer_id'));
        $this->assertNotNull($history->get('removed'));
    }

    /**
     * Added under its customer and the contract, the record is filed under them without the form saying so.
     *
     * The form under a customer and the contract leaves those fields out - the route already says which record it is,
     * and the controller fills them in. Posting them in the body instead, as a test reaching the
     * flat route does, asks a different question and leaves this one unasked.
     *
     * @return void
     * @link \App\Controller\IpAddressesController::add()
     */
    public function testAddUnderTheRouteFilesItUnderTheRoute(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $before = $this->idsIn('IpAddresses');
        $this->post('/customers/' . self::CUSTOMER_ID . '/contracts/' . self::CONTRACT_ID . '/ip-addresses/add', [
            'ip_address' => '10.99.0.1',
            'type_of_use' => 0,
        ]);

        $this->assertRedirect();
        $added = $this->addedRecord('IpAddresses', $before);
        $this->assertSame(self::CUSTOMER_ID, $added->get('customer_id'));
        $this->assertSame(self::CONTRACT_ID, $added->get('contract_id'));
    }

    /**
     * A change made on the form reaches the record.
     *
     * @return void
     * @link \App\Controller\IpAddressesController::edit()
     */
    public function testEditStoresTheChange(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $ipAddressId = $this->firstId('IpAddresses');
        $this->post('/ip-addresses/edit/' . $ipAddressId, ['note' => 'Reserved for the router.']);

        $this->assertRedirect();
        $this->assertSame(
            'Reserved for the router.',
            $this->getTableLocator()->get('IpAddresses')->get($ipAddressId)->note,
        );
    }

    /**
     * What the NMS knows about the address is named and led back to, rather than only described.
     *
     * The range, the point it hangs off and the device serving it are three separate links built
     * from three separate identifiers, and the page draws them through shared elements - so one
     * request over a seeded answer covers every page that shows the same thing.
     *
     * @return void
     * @link \App\Controller\IpAddressesController::view()
     */
    public function testViewLeadsBackToWhatTheNmsKnows(): void
    {
        // Said here rather than read from the environment, which the CI has none of. Both halves
        // of it, because a kept reading is only handed over where there is a Watcher NMS it could
        // have come from.
        $nmsUrl = Configure::read('Nms.url');
        $nmsKey = Configure::read('Nms.key');
        Configure::write('Nms.url', 'https://nms.example.com');
        Configure::write('Nms.key', 'secret');

        Cache::write(
            'ip_address_ranges_for_ip_192-168-11-11',
            [[
                'id' => self::RANGE_ID,
                'name' => 'Hilltop customers',
                'access_point' => ['id' => self::ACCESS_POINT_ID, 'name' => 'Hilltop'],
            ]],
            'api_client',
        );
        Cache::write(
            'routeros_devices_for_ip_192-168-11-11',
            [[
                'id' => self::ROUTEROS_DEVICE_ID,
                'system_description' => 'RB5009 at Hilltop',
            ]],
            'api_client',
        );

        try {
            $this->login();
            $this->get('/ip-addresses/view/' . $this->firstId('IpAddresses'));

            $this->assertResponseOk();
            $this->assertResponseContains('https://nms.example.com/ip-address-ranges/view/' . self::RANGE_ID);
            $this->assertResponseContains('https://nms.example.com/access-points/' . self::ACCESS_POINT_ID);
            $this->assertResponseContains(
                'https://nms.example.com/routeros-devices/view/' . self::ROUTEROS_DEVICE_ID,
            );
            $this->assertResponseContains('Hilltop customers');
            $this->assertResponseContains('RB5009 at Hilltop');
        } finally {
            Cache::delete('ip_address_ranges_for_ip_192-168-11-11', 'api_client');
            Cache::delete('routeros_devices_for_ip_192-168-11-11', 'api_client');
            Configure::write('Nms.url', $nmsUrl);
            Configure::write('Nms.key', $nmsKey);
        }
    }

    /**
     * A page the NMS did not answer for says so, rather than showing the rows empty as though the
     * address sat in no range and behind no device.
     *
     * @return void
     * @link \App\Controller\IpAddressesController::view()
     */
    public function testViewSaysWhenTheNmsDidNotAnswer(): void
    {
        $nmsUrl = Configure::read('Nms.url');
        $nmsKey = Configure::read('Nms.key');
        Configure::write('Nms.url', 'https://nms.example.com');
        Configure::write('Nms.key', 'secret');

        $this->mockClientGet('https://nms.example.com/*', $this->newClientResponse(500));

        try {
            $this->login();
            $this->get('/ip-addresses/view/' . $this->firstId('IpAddresses'));

            $this->assertResponseOk();
            $this->assertResponseContains('warning-text');
        } finally {
            Configure::write('Nms.url', $nmsUrl);
            Configure::write('Nms.key', $nmsKey);
        }
    }

    /**
     * An installation with no NMS shows the same page without a word about it - the rows are empty
     * because there is nothing to put in them, and that is not worth remarking on.
     *
     * @return void
     * @link \App\Controller\IpAddressesController::view()
     */
    public function testViewWithoutAnNmsSaysNothingAboutIt(): void
    {
        $nmsUrl = Configure::read('Nms.url');
        Configure::write('Nms.url', '');

        try {
            $this->login();
            $this->get('/ip-addresses/view/' . $this->firstId('IpAddresses'));

            $this->assertResponseOk();
            $this->assertResponseNotContains('warning-text');
        } finally {
            Configure::write('Nms.url', $nmsUrl);
        }
    }

    /**
     * Moving an address is one act: the old one joins the removed ones, the contract keeps an
     * address, and the audit log has all of it under one entry.
     *
     * @return void
     * @link \App\Controller\IpAddressesController::bulkReassignment()
     */
    public function testBulkReassignmentMovesTheAddress(): void
    {
        $addresses = $this->getTableLocator()->get('IpAddresses');
        $removed = $this->getTableLocator()->get('RemovedIpAddresses')->find()->count();
        $addresses->getConnection()->execute('DELETE FROM audit_logs');

        $contractId = $this->reassignTheAddressInto('10.0.0.0/29');

        $this->assertResponseOk();
        $this->assertResponseContains('Processing completed.');

        $now = $addresses->find()->where(['contract_id' => $contractId])->all()->toList();
        $this->assertCount(1, $now);
        $this->assertStringStartsWith('10.0.0.', (string)$now[0]->get('ip_address'));
        $this->assertSame($removed + 1, $this->getTableLocator()->get('RemovedIpAddresses')->find()->count());

        $entries = $addresses->getConnection()
            ->execute('SELECT transaction_key, type FROM audit_logs ORDER BY type')
            ->fetchAll('assoc');

        $this->assertSame(['create', 'create', 'delete'], array_column($entries, 'type'));
        $this->assertCount(1, array_unique(array_column($entries, 'transaction_key')));
    }

    /**
     * A new address that cannot be written leaves the old one where it was, so the contract is not
     * left without any, and the page does not claim what did not happen.
     *
     * @return void
     * @link \App\Controller\IpAddressesController::bulkReassignment()
     */
    public function testBulkReassignmentKeepsTheAddressThatCouldNotBeMoved(): void
    {
        $addresses = $this->getTableLocator()->get('IpAddresses');
        $removed = $this->getTableLocator()->get('RemovedIpAddresses')->find()->count();

        $addresses->getEventManager()->on('Model.beforeSave', function (EventInterface $event): void {
            $event->stopPropagation();
            $event->setResult(false);
        });

        $contractId = $this->reassignTheAddressInto('10.0.0.0/29');

        $this->assertResponseOk();
        $this->assertResponseContains('The IP address could not be saved.');
        $this->assertResponseNotContains('The IP address has been deleted.');
        $this->assertResponseNotContains('The removed IP address has been saved.');
        $this->assertResponseNotContains('Processing completed.');

        $this->assertSame(
            '192.168.11.11',
            (string)$addresses->find()->where(['contract_id' => $contractId])->firstOrFail()->get('ip_address'),
        );
        $this->assertSame($removed, $this->getTableLocator()->get('RemovedIpAddresses')->find()->count());
    }

    /**
     * A range with nothing free moves nothing, which is not the same as being done.
     *
     * @return void
     * @link \App\Controller\IpAddressesController::bulkReassignment()
     */
    public function testBulkReassignmentDoesNotSayItIsDoneWithNoAddressLeft(): void
    {
        $this->reassignTheAddressInto('10.0.0.0/31');

        $this->assertResponseOk();
        $this->assertResponseContains('No available IP addresses for reassignment.');
        $this->assertResponseNotContains('Processing completed.');
    }

    /**
     * Sends the form asking to move the address of the fixture into a range of Watcher NMS.
     *
     * @param string $network The network of the range.
     * @return string The contract the address is on.
     */
    private function reassignTheAddressInto(string $network): string
    {
        $this->withConfigure(['Nms.url' => 'https://nms.example.com', 'Nms.key' => 'secret']);
        $this->mockClientGet('https://nms.example.com/*', $this->newClientResponse(
            200,
            ['Content-Type: application/json'],
            (string)json_encode(['ipAddressRanges' => [[
                'id' => self::RANGE_ID,
                'name' => 'Hilltop customers',
                'ip_network' => $network,
                'ip_gateway' => '10.0.0.1',
            ]]]),
        ));

        $ipAddress = $this->getTableLocator()->get('IpAddresses')->find()->contain(['Contracts'])->firstOrFail();
        /** @var \App\Model\Entity\Contract $contract */
        $contract = $ipAddress->get('contract');

        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/ip-addresses/bulk-reassignment', [
            'access_point_id' => $contract->access_point_id,
            'type_of_use' => IpAddressTypeOfUse::CustomerManually->value,
            'ip_address_range' => self::RANGE_ID,
            'reassing_ip_address' => [$ipAddress->get('id') => $ipAddress->get('id')],
        ]);

        return $contract->id;
    }
}
