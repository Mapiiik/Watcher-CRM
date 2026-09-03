<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Entity;

use App\Model\Entity\Queue;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * App\Model\Entity\Queue Test Case
 *
 * The coefficient is passed in throughout, so these say what the entity does without
 * depending on the settings plugin being loaded. One case leaves it out to prove the
 * shipped default carries an unbooted application.
 */
#[CoversClass(Queue::class)]
class QueueTest extends TestCase
{
    /**
     * Test that a declared speed is returned as it stands.
     *
     * @return void
     * @link \App\Model\Entity\Queue::getSpeedDownCommon()
     */
    public function testDeclaredSpeedWinsOverTheCoefficient(): void
    {
        $queue = new Queue([
            'speed_down' => 100000,
            'speed_down_common' => 80000,
        ]);

        $this->assertSame(80000, $queue->getSpeedDownCommon(0.6));
    }

    /**
     * Test that an undeclared speed is taken as a share of the advertised one.
     *
     * @return void
     * @link \App\Model\Entity\Queue::getSpeedDownCommon()
     */
    public function testUndeclaredSpeedIsDerivedFromTheAdvertisedOne(): void
    {
        $queue = new Queue([
            'speed_down' => 100000,
        ]);

        $this->assertSame(60000, $queue->getSpeedDownCommon(0.6));
        $this->assertSame(30000, $queue->getSpeedDownMinimum(0.3));
    }

    /**
     * Test that a derived speed is rounded to whole kbps.
     *
     * @return void
     * @link \App\Model\Entity\Queue::getSpeedUpCommon()
     */
    public function testDerivedSpeedIsRounded(): void
    {
        $queue = new Queue([
            'speed_up' => 10001,
        ]);

        $this->assertSame(6001, $queue->getSpeedUpCommon(0.6));
    }

    /**
     * Test that nothing is derived when the tariff advertises no speed either.
     *
     * @return void
     * @link \App\Model\Entity\Queue::getSpeedUpCommon()
     */
    public function testNoAdvertisedSpeedDerivesNothing(): void
    {
        $queue = new Queue([]);

        $this->assertNull($queue->getSpeedUpCommon(0.6));
        $this->assertNull($queue->getSpeedUpMinimum(0.3));
        $this->assertNull($queue->getSpeedDown());
    }

    /**
     * Test that a declared speed of zero is an answer rather than an absence.
     *
     * @return void
     * @link \App\Model\Entity\Queue::getSpeedDownMinimum()
     */
    public function testDeclaredZeroIsNotTakenForUndeclared(): void
    {
        $queue = new Queue([
            'speed_down' => 100000,
            'speed_down_minimum' => 0,
        ]);

        $this->assertSame(0, $queue->getSpeedDownMinimum(0.3));
    }

    /**
     * Test that the advertised speed is what the maximum getters hand back.
     *
     * @return void
     * @link \App\Model\Entity\Queue::getSpeedDown()
     */
    public function testAdvertisedSpeedIsTheMaximumOne(): void
    {
        $queue = new Queue([
            'speed_down' => 100000,
            'speed_up' => 20000,
        ]);

        $this->assertSame(100000, $queue->getSpeedDown());
        $this->assertSame(20000, $queue->getSpeedUp());
    }

    /**
     * Test that omitting the coefficient still derives, on the shipped default.
     *
     * @return void
     * @link \App\Model\Entity\Queue::getSpeedDownCommon()
     */
    public function testOmittedCoefficientFallsBackToTheShippedDefault(): void
    {
        $queue = new Queue([
            'speed_down' => 100000,
        ]);

        $this->assertSame(60000, $queue->getSpeedDownCommon());
        $this->assertSame(30000, $queue->getSpeedDownMinimum());
    }
}
