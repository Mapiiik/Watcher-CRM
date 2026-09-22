<?php
declare(strict_types=1);

namespace App\Test\TestCase\RegulatoryReporting\Cz;

use App\Model\Enum\AccessTechnology;
use App\RegulatoryReporting\Cz\CtuTechnologyCategory;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * App\RegulatoryReporting\Cz\CtuTechnologyCategory Test Case
 *
 * Which ČTÚ file an address point lands in is decided here, so a technology in the wrong one is
 * reported under the wrong annex.
 */
#[CoversClass(CtuTechnologyCategory::class)]
class CtuTechnologyCategoryTest extends TestCase
{
    /**
     * Both kinds of FTTH are one category to ČTÚ, and so are both generations of cable.
     *
     * @return void
     * @link \App\RegulatoryReporting\Cz\CtuTechnologyCategory::fromTechnology()
     */
    public function testEachTechnologyLandsInItsCategory(): void
    {
        $this->assertSame(CtuTechnologyCategory::Wifi, CtuTechnologyCategory::fromTechnology(AccessTechnology::FwaUnlicensed));
        $this->assertSame(CtuTechnologyCategory::Fttb, CtuTechnologyCategory::fromTechnology(AccessTechnology::FttbEthernet));
        $this->assertSame(CtuTechnologyCategory::Ftth, CtuTechnologyCategory::fromTechnology(AccessTechnology::FtthP2pEthernet));
        $this->assertSame(CtuTechnologyCategory::Ftth, CtuTechnologyCategory::fromTechnology(AccessTechnology::FtthP2mpPon));
        $this->assertSame(CtuTechnologyCategory::Catv, CtuTechnologyCategory::fromTechnology(AccessTechnology::CatvDocsis31));
    }

    /**
     * A technology the network does not have a category for yet is left out, not guessed.
     *
     * @return void
     * @link \App\RegulatoryReporting\Cz\CtuTechnologyCategory::fromTechnology()
     */
    public function testATechnologyWithoutACategoryIsNotGuessed(): void
    {
        $this->assertNull(CtuTechnologyCategory::fromTechnology(AccessTechnology::FwaLicensed));
        $this->assertNull(CtuTechnologyCategory::fromTechnology(AccessTechnology::Xdsl));
        $this->assertNull(CtuTechnologyCategory::fromTechnology(AccessTechnology::Satellite));
    }

    /**
     * The way back lists exactly what the way there sends to the category.
     *
     * @return void
     * @link \App\RegulatoryReporting\Cz\CtuTechnologyCategory::technologies()
     */
    public function testTheWayBackListsWhatTheWayThereSends(): void
    {
        $this->assertSame(
            [AccessTechnology::FtthP2pEthernet, AccessTechnology::FtthP2mpPon],
            CtuTechnologyCategory::Ftth->technologies(),
        );
    }
}
