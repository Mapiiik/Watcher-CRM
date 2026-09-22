<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\AvailableConnectionsTable;
use App\Test\Traits\TableTestTrait;
use Cake\I18n\Date;
use Cake\TestSuite\TestCase;
use Override;

/**
 * App\Model\Table\AvailableConnectionsTable Test Case
 */
class AvailableConnectionsTableTest extends TestCase
{
    use TableTestTrait;

    /**
     * @var \App\Model\Table\AvailableConnectionsTable
     */
    protected $AvailableConnections;

    /**
     * @var list<string>
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
        'app.AvailableConnections',
    ];

    /**
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('AvailableConnections')
            ? []
            : ['className' => AvailableConnectionsTable::class];
        $this->AvailableConnections = $this->getTableLocator()->get('AvailableConnections', $config);
    }

    /**
     * @return void
     */
    #[Override]
    protected function tearDown(): void
    {
        /** @phpstan-ignore unset.possiblyHookedProperty */
        unset($this->AvailableConnections);

        parent::tearDown();
    }

    /**
     * @return void
     * @link \App\Model\Table\AvailableConnectionsTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->assertEmptyRecordIsRefused($this->AvailableConnections);
    }

    /**
     * A second record of the same technology at the same address point is refused with a word the
     * operator understands, while another technology at it is welcome.
     *
     * @return void
     * @link \App\Model\Table\AvailableConnectionsTable::buildRules()
     */
    public function testOneRecordOfATechnologyAtAPoint(): void
    {
        $data = [
            'address_registry_source' => 'cz',
            'address_registry_reference' => '16936132',
            'access_technology' => 'ftth_p2mp_pon',
            'speed_down_max' => 1024000,
            'speed_up_max' => 1024000,
        ];

        $twin = $this->AvailableConnections->newEntity($data);
        $this->assertFalse($this->AvailableConnections->save($twin));
        $this->assertArrayHasKey('address_registry_reference', $twin->getErrors());

        $other = $this->AvailableConnections->newEntity(['access_technology' => 'fwa_unlicensed'] + $data);
        $this->assertNotFalse($this->AvailableConnections->save($other));
    }

    /**
     * A retired connection is not there to be had from its day on, but it was before.
     *
     * @return void
     * @link \App\Model\Table\AvailableConnectionsTable::findInService()
     */
    public function testARetiredConnectionIsGoneFromItsDayOn(): void
    {
        $record = $this->AvailableConnections->get('ac000000-0000-4000-8000-000000000001');
        $record->retired = new Date('2026-06-15');
        $this->AvailableConnections->saveOrFail($record);

        $this->assertSame(1, $this->AvailableConnections->find('inService', on: new Date('2026-06-14'))->count());
        $this->assertSame(0, $this->AvailableConnections->find('inService', on: new Date('2026-06-15'))->count());
    }
}
