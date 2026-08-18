<?php
declare(strict_types=1);

namespace Maps\Test\TestCase;

use Cake\TestSuite\TestCase;
use Maps\Position;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * Maps\Position Test Case
 */
#[UsesClass(Position::class)]
class PositionTest extends TestCase
{
    /**
     * A position keeps the two coordinates apart, in the order the map libraries name them.
     *
     * @return void
     * @link \Maps\Position::toArray()
     */
    public function testToArrayNamesBothCoordinates(): void
    {
        $position = new Position(50.0875, 14.4212);

        $this->assertSame(['lat' => 50.0875, 'lng' => 14.4212], $position->toArray());
    }

    /**
     * A degree of latitude is the same length wherever it is measured, which is what makes it
     * worth checking against.
     *
     * @return void
     * @link \Maps\Position::distanceTo()
     */
    public function testADegreeOfLatitudeIsAKnownDistance(): void
    {
        $distance = (new Position(0.0, 0.0))->distanceTo(new Position(1.0, 0.0));

        $this->assertEqualsWithDelta(111194.93, $distance, 0.01);
    }

    /**
     * Two names for the same place are no distance apart.
     *
     * @return void
     * @link \Maps\Position::distanceTo()
     */
    public function testAPointIsNoDistanceFromItself(): void
    {
        $position = new Position(50.0875, 14.4212);

        $this->assertSame(0.0, $position->distanceTo(new Position(50.0875, 14.4212)));
    }
}
