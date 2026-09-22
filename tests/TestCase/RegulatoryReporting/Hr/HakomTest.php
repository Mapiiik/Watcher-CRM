<?php
declare(strict_types=1);

namespace App\Test\TestCase\RegulatoryReporting\Hr;

use App\Addresses\Dto\Address;
use App\Model\Entity\Billing;
use App\Model\Entity\ConnectionProfile;
use App\Model\Entity\Customer;
use App\Model\Entity\Service;
use App\Model\Enum\AccessTechnology;
use App\Model\Enum\BusinessCustomerDetection;
use App\RegulatoryReporting\ConnectionPoint;
use App\RegulatoryReporting\Hr\HakomAddressSpeedBand;
use App\RegulatoryReporting\Hr\HakomConnectionPointsCsv;
use App\RegulatoryReporting\Hr\HakomQuarterlyReport;
use App\RegulatoryReporting\Hr\HakomSpeedBand;
use App\RegulatoryReporting\Hr\HakomTechnology;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Settings\Utility\Settings;

/**
 * Where a connection lands in what HAKOM is sent.
 */
#[CoversClass(HakomSpeedBand::class)]
#[CoversClass(HakomAddressSpeedBand::class)]
#[CoversClass(HakomTechnology::class)]
#[CoversClass(HakomConnectionPointsCsv::class)]
#[CoversClass(HakomQuarterlyReport::class)]
class HakomTest extends TestCase
{
    /**
     * The Croatian way of telling a business is written to the settings, which are put back.
     *
     * @var list<string>
     */
    protected array $fixtures = [
        'app.AppUsers',
        'plugin.Settings.Settings',
    ];

    /**
     * The lower edge belongs to the band, as the form writes it, and below 2 Mbit/s there is none.
     *
     * @return void
     * @link \App\RegulatoryReporting\Hr\HakomSpeedBand::fromKbps()
     */
    public function testTheLowerEdgeBelongsToTheBand(): void
    {
        $this->assertNull(HakomSpeedBand::fromKbps(null));
        $this->assertNull(HakomSpeedBand::fromKbps(2047));
        $this->assertSame(HakomSpeedBand::From2To10, HakomSpeedBand::fromKbps(7168));
        $this->assertSame(HakomSpeedBand::From10To30, HakomSpeedBand::fromKbps(10240));
        $this->assertSame(HakomSpeedBand::From30To100, HakomSpeedBand::fromKbps(30720));
        $this->assertSame(HakomSpeedBand::From100To300, HakomSpeedBand::fromKbps(102400));
        $this->assertSame(HakomSpeedBand::From2000, HakomSpeedBand::fromKbps(2048000));

        $this->assertSame(HakomAddressSpeedBand::Unknown, HakomAddressSpeedBand::fromKbps(null));
        $this->assertSame(HakomAddressSpeedBand::From4, HakomAddressSpeedBand::fromKbps(7168));
        $this->assertSame(HakomAddressSpeedBand::From1000, HakomAddressSpeedBand::fromKbps(1024000));
    }

    /**
     * Wireless in an unlicensed band is the form's 4.2, both its traffic and its revenue go where
     * the old returns put them.
     *
     * @return void
     * @link \App\RegulatoryReporting\Hr\HakomTechnology
     */
    public function testWirelessLandsWhereTheReturnsPutIt(): void
    {
        $this->assertSame('4.2', HakomTechnology::connections(AccessTechnology::FwaUnlicensed)[0] ?? null);
        $this->assertSame('2.1.9', HakomTechnology::traffic(AccessTechnology::FwaUnlicensed)[0]);
        $this->assertSame('1.5', HakomTechnology::revenue(AccessTechnology::FwaUnlicensed)[0]);
        $this->assertSame('FWA-WiFi', HakomTechnology::infrastructureType(AccessTechnology::FwaLicensed));
        $this->assertNull(HakomTechnology::connections(AccessTechnology::Xdsl));
    }

    /**
     * A quarter runs from the first day of its first month to the last of its third.
     *
     * @return void
     * @link \App\RegulatoryReporting\Hr\HakomQuarterlyReport::forQuarter()
     */
    public function testAQuarterIsThreeMonths(): void
    {
        $report = HakomQuarterlyReport::forQuarter(2026, 2);

        $this->assertSame('2026-04-01', $report->from->format('Y-m-d'));
        $this->assertSame('2026-06-30', $report->until->format('Y-m-d'));
    }

    /**
     * A household and a business at one address are counted apart, and an address nobody is on
     * yet is listed with nothing on it.
     *
     * @return void
     * @link \App\RegulatoryReporting\Hr\HakomConnectionPointsCsv::render()
     */
    public function testTheListingCountsHouseholdsAndBusinessesApart(): void
    {
        Settings::set(BusinessCustomerDetection::SETTINGS_PATH, BusinessCustomerDetection::Company->value);

        $address = new Address(
            source: 'hr',
            registryReference: 'HR.DGU.RPJ:KB.0000606874',
            street: 'Potok',
            houseNumber: '23',
            city: 'Makarska',
        );
        $point = new ConnectionPoint('FWA-WiFi', 'hr', 'HR.DGU.RPJ:KB.0000606874', [
            $this->connection(null, 7168),
            $this->connection('Multi d.o.o.', 20480),
        ], [], 'HR.DGU.RPJ:KB.0000606874', 'Potok 23, 21300 Makarska', $address);
        $empty = new ConnectionPoint('FWA-WiFi', 'hr', 'HR.DGU.RPJ:KB.1', [], [], 'HR.DGU.RPJ:KB.1');

        $lines = explode("\r\n", HakomConnectionPointsCsv::render([$point, $empty], 'MULTI KOMUNIKACIJE d.o.o.'));

        $this->assertStringStartsWith("\u{FEFF}kb_id;na_ime;ul_ime;kb;infrastructure_owner;infrastructure_type;private_0M;", $lines[0]);
        $this->assertSame(
            'HR.DGU.RPJ:KB.0000606874;Makarska;Potok;23;MULTI KOMUNIKACIJE d.o.o.;FWA-WiFi;'
            . '0;0;1;0;0;0;0;0;0;0;0;0;0;'
            . '0;0;0;0;1;0;0;0;0;0;0;0;0',
            $lines[1],
        );
        $this->assertStringEndsWith(';FWA-WiFi;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0;0', $lines[2]);
    }

    /**
     * @param string|null $company The customer's company, null for a household.
     * @param int $speedDown The contracted download speed.
     * @return \App\Model\Entity\Billing
     */
    private function connection(?string $company, int $speedDown): Billing
    {
        return new Billing([
            'customer' => new Customer(['company' => $company, 'identity_number' => '12345678903']),
            'service' => new Service([
                'connection_profile' => new ConnectionProfile([
                    'speed_down' => $speedDown,
                    'access_technology' => AccessTechnology::FwaUnlicensed,
                ]),
            ]),
        ], ['markClean' => true]);
    }
}
