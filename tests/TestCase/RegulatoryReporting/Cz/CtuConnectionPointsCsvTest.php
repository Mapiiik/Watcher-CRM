<?php
declare(strict_types=1);

namespace App\Test\TestCase\RegulatoryReporting\Cz;

use App\Model\Entity\Billing;
use App\Model\Entity\ConnectionProfile;
use App\Model\Entity\Contract;
use App\Model\Entity\Customer;
use App\Model\Entity\Service;
use App\Model\Enum\AccessTechnology;
use App\RegulatoryReporting\ConnectionPoint;
use App\RegulatoryReporting\Cz\CtuConnectionPointRow;
use App\RegulatoryReporting\Cz\CtuConnectionPointsCsv;
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
        ], '16932421', 'Pekárenská 89, Semily');

        $csv = CtuConnectionPointsCsv::render(CtuTechnologyCategory::Ftth, [CtuConnectionPointRow::fromPoint($point)]);
        $lines = explode(PHP_EOL, (string)iconv('CP1250', 'UTF-8', $csv));

        $this->assertStringStartsWith('Adresní místo (kód RÚIAN);Technologická kategorie', $lines[0]);
        $this->assertStringEndsWith(';VHCN síť (třída);Adresa', $lines[0]);
        $this->assertSame('16932421;s2_ftth;2;1;ANO;1000;1000;1000;1000;1;Pekárenská 89, Semily', $lines[1]);
    }

    /**
     * Only a cable network is asked about DOCSIS.
     *
     * @return void
     * @link \App\RegulatoryReporting\Cz\CtuConnectionPointsCsv::render()
     */
    public function testOnlyCableIsAskedAboutDocsis(): void
    {
        $this->assertStringContainsString('DOCSIS', CtuConnectionPointsCsv::render(CtuTechnologyCategory::Catv, []));
        $this->assertStringNotContainsString('DOCSIS', CtuConnectionPointsCsv::render(CtuTechnologyCategory::Wifi, []));
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
