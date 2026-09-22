<?php
declare(strict_types=1);

namespace App\Test\TestCase\RegulatoryReporting\Cz;

use App\Model\Entity\AvailableConnection;
use App\Model\Entity\Billing;
use App\Model\Entity\ConnectionProfile;
use App\Model\Entity\Contract;
use App\Model\Entity\Customer;
use App\Model\Entity\Service;
use App\Model\Enum\AccessTechnology;
use App\RegulatoryReporting\ConnectionPoint;
use App\RegulatoryReporting\Cz\CtuConnectionPointRow;
use App\RegulatoryReporting\Cz\CtuConnectionPointsCsv;
use App\RegulatoryReporting\Cz\CtuSpeedInterval;
use App\RegulatoryReporting\Cz\CtuTechnologyCategory;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * The file ČTÚ is sent, worked out from the connections at an address point.
 */
#[CoversClass(CtuConnectionPointsCsv::class)]
#[CoversClass(CtuConnectionPointRow::class)]
#[UsesClass(ConnectionPoint::class)]
class CtuConnectionPointsCsvTest extends TestCase
{
    /**
     * A household and a business on fibre at one address make one line: two connections, one of
     * them a household's, reported at a gigabit and as a very high capacity network.
     *
     * @return void
     * @link \App\RegulatoryReporting\Cz\CtuConnectionPointsCsv::render()
     */
    public function testTwoConnectionsAtOneAddressMakeOneLine(): void
    {
        $point = new ConnectionPoint('s2_ftth', 'cz', '16932421', [
            $this->connection(null, 51200),
            $this->connection('12345678', 102400),
        ], [], '16932421', 'Pekárenská 89, Semily');

        $csv = CtuConnectionPointsCsv::render(CtuTechnologyCategory::Ftth, [CtuConnectionPointRow::fromPoint($point)]);
        $lines = explode("\r\n", $csv);

        $this->assertStringStartsWith("\u{FEFF}Adresní místo (kód RÚIAN);Technologická kategorie", $lines[0]);
        $this->assertStringEndsWith(';VHCN síť (třída);Adresa', $lines[0]);
        $this->assertSame('16932421;s2_ftth;2;1;ANO;1000;1000;1000;1000;1;Pekárenská 89, Semily', $lines[1]);
        $this->assertSame('', $lines[2]);
    }

    /**
     * An address point with nobody on it is reported covered, with no connections, at the speeds
     * the line carries.
     *
     * @return void
     * @link \App\RegulatoryReporting\Cz\CtuConnectionPointRow::fromPoint()
     */
    public function testAPointWithNobodyOnItIsReportedCovered(): void
    {
        $point = new ConnectionPoint('s2_wifi', 'cz', '15033554', [], [
            new AvailableConnection([
                'access_technology' => AccessTechnology::FwaUnlicensed,
                'speed_down_max' => 153600,
                'speed_up_max' => 51200,
            ]),
        ], '15033554', 'č.p. 12, Benecko');

        $row = CtuConnectionPointRow::fromPoint($point);

        $this->assertSame(0, $row->activeConnections);
        $this->assertTrue($row->covered);
        $this->assertSame(CtuSpeedInterval::From100To300, $row->maximalDownload);
        $this->assertSame(CtuSpeedInterval::From30To100, $row->maximalUpload);
        $this->assertStringContainsString(
            "15033554;s2_wifi;0;0;ANO;30_100;30_100;100_300;30_100;0;č.p. 12, Benecko\r\n",
            CtuConnectionPointsCsv::render(CtuTechnologyCategory::Wifi, [$row]),
        );
    }

    /**
     * A field that would break the line is put in quotes, and nothing else is.
     *
     * @return void
     * @link \App\RegulatoryReporting\Cz\CtuConnectionPointsCsv::render()
     */
    public function testOnlyAFieldThatWouldBreakTheLineIsQuoted(): void
    {
        $point = new ConnectionPoint(
            's2_ftth',
            'cz',
            '1',
            [$this->connection(null, 51200)],
            [],
            '1',
            'Dům "U Lípy"; Semily',
        );

        $csv = CtuConnectionPointsCsv::render(CtuTechnologyCategory::Ftth, [CtuConnectionPointRow::fromPoint($point)]);

        $this->assertStringContainsString('1;s2_ftth;1;1;ANO;', $csv);
        $this->assertStringContainsString(";\"Dům \"\"U Lípy\"\"; Semily\"\r\n", $csv);
    }

    /**
     * Only a cable network is asked about DOCSIS, and a network of 3.1 says so.
     *
     * @return void
     * @link \App\RegulatoryReporting\Cz\CtuConnectionPointsCsv::render()
     */
    public function testOnlyCableIsAskedAboutDocsis(): void
    {
        $this->assertStringContainsString('DOCSIS', CtuConnectionPointsCsv::render(CtuTechnologyCategory::Catv, []));
        $this->assertStringNotContainsString('DOCSIS', CtuConnectionPointsCsv::render(CtuTechnologyCategory::Wifi, []));

        $point = new ConnectionPoint('s2_catv', 'cz', '1', [], [
            new AvailableConnection([
                'access_technology' => AccessTechnology::CatvDocsis31,
                'speed_down_max' => 1024000,
                'speed_up_max' => 102400,
            ]),
        ], '1', 'Semily');

        $this->assertStringContainsString(
            ';1;ANO;Semily',
            CtuConnectionPointsCsv::render(CtuTechnologyCategory::Catv, [CtuConnectionPointRow::fromPoint($point)]),
        );
    }

    /**
     * @param string|null $identityNumber The customer's, null for a household.
     * @param int $speedDown The advertised download speed.
     * @return \App\Model\Entity\Billing
     */
    private function connection(?string $identityNumber, int $speedDown): Billing
    {
        return new Billing([
            'customer' => new Customer(['identity_number' => $identityNumber]),
            'contract' => new Contract(['number' => '1']),
            'service' => new Service([
                'connection_profile' => new ConnectionProfile([
                    'speed_down' => $speedDown,
                    'speed_up' => $speedDown,
                    'access_technology' => AccessTechnology::FtthP2mpPon,
                ]),
            ]),
        ], ['markClean' => true]);
    }
}
