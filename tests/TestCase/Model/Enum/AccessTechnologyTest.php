<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Enum;

use App\Model\Enum\AccessMedium;
use App\Model\Enum\AccessTechnology;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Model\Enum\AccessTechnology Test Case
 *
 * The operator picks the technology by its label alone, and every regulator's report is worked out
 * from what was picked.
 */
#[CoversClass(AccessTechnology::class)]
#[UsesClass(AccessMedium::class)]
class AccessTechnologyTest extends TestCase
{
    /**
     * No two technologies read the same, or the operator could not tell which one they chose.
     *
     * @return void
     * @link \App\Model\Enum\AccessTechnology::label()
     */
    public function testEveryTechnologyReadsDifferently(): void
    {
        $labels = array_map(fn(AccessTechnology $technology): string => $technology->label(), AccessTechnology::cases());

        $this->assertSame($labels, array_values(array_unique($labels)));
        $this->assertNotContains('', $labels);
    }

    /**
     * Only fibre at least to the building, and cable that can match it, is a very high capacity
     * network.
     *
     * @return void
     * @link \App\Model\Enum\AccessTechnology::isVhcn()
     */
    public function testOnlyFibreAndTheNewestCableAreVhcn(): void
    {
        $vhcn = array_values(array_filter(
            AccessTechnology::cases(),
            fn(AccessTechnology $technology): bool => $technology->isVhcn(),
        ));

        $this->assertSame([
            AccessTechnology::FttbEthernet,
            AccessTechnology::FtthP2pEthernet,
            AccessTechnology::FtthP2mpPon,
            AccessTechnology::CatvDocsis31,
        ], $vhcn);
    }

    /**
     * The choice is offered grouped by medium, and nothing falls out of the groups.
     *
     * @return void
     * @link \App\Model\Enum\AccessTechnology::groupedOptions()
     */
    public function testTheOptionsAreGroupedByMediumWithNothingLeftOut(): void
    {
        $groups = AccessTechnology::groupedOptions();

        $this->assertSame(
            array_map(fn(AccessMedium $medium): string => $medium->label(), AccessMedium::cases()),
            array_keys($groups),
        );
        $this->assertSame(
            AccessTechnology::options(),
            array_merge(...array_values($groups)),
        );
        $this->assertArrayHasKey('ftth_p2mp_pon', $groups[AccessMedium::Fibre->label()]);
        $this->assertArrayHasKey('fwa_unlicensed', $groups[AccessMedium::Wireless->label()]);
    }
}
