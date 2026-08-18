<?php
declare(strict_types=1);

namespace Maps\Test\TestCase;

use Cake\TestSuite\TestCase;
use Maps\Polyline;
use Maps\Position;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * Maps\Polyline Test Case
 */
#[UsesClass(Polyline::class)]
class PolylineTest extends TestCase
{
    /**
     * A line keeps its two ends the way round it was given them, which is what decides which way it
     * is drawn.
     *
     * @return void
     * @link \Maps\Polyline::__construct()
     */
    public function testALineKeepsItsEndsTheWayRoundItWasGivenThem(): void
    {
        $from = new Position(50.0875, 14.4212);
        $to = new Position(50.7663, 15.0543);

        $polyline = new Polyline($from, $to);

        $this->assertSame($from, $polyline->from);
        $this->assertSame($to, $polyline->to);
    }

    /**
     * A line drawn without options carries none, rather than whatever was left over from the last
     * one.
     *
     * @return void
     * @link \Maps\Polyline::__construct()
     */
    public function testALineDrawnWithoutOptionsCarriesNone(): void
    {
        $polyline = new Polyline(new Position(50.0875, 14.4212), new Position(50.7663, 15.0543));

        $this->assertSame([], $polyline->options);
    }

    /**
     * The options given are the ones carried.
     *
     * @return void
     * @link \Maps\Polyline::__construct()
     */
    public function testALineCarriesTheOptionsItWasGiven(): void
    {
        $polyline = new Polyline(
            new Position(50.0875, 14.4212),
            new Position(50.7663, 15.0543),
            ['strokeColor' => '#336699'],
        );

        $this->assertSame(['strokeColor' => '#336699'], $polyline->options);
    }
}
