<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Entity;

use App\Model\Entity\ConnectionProfile;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * App\Model\Entity\ConnectionProfile Test Case
 *
 * The coefficient is passed in throughout, so these say what the entity does without
 * depending on the settings plugin being loaded. One case leaves it out to prove the
 * shipped default carries an unbooted application.
 */
#[CoversClass(ConnectionProfile::class)]
class ConnectionProfileTest extends TestCase
{
    /**
     * Test that a declared speed is returned as it stands.
     *
     * @return void
     * @link \App\Model\Entity\ConnectionProfile::getSpeedDownCommon()
     */
    public function testDeclaredSpeedWinsOverTheCoefficient(): void
    {
        $connectionProfile = new ConnectionProfile([
            'speed_down' => 100000,
            'speed_down_common' => 80000,
        ]);

        $this->assertSame(80000, $connectionProfile->getSpeedDownCommon(0.6));
    }

    /**
     * Test that an undeclared speed is taken as a share of the advertised one.
     *
     * @return void
     * @link \App\Model\Entity\ConnectionProfile::getSpeedDownCommon()
     */
    public function testUndeclaredSpeedIsDerivedFromTheAdvertisedOne(): void
    {
        $connectionProfile = new ConnectionProfile([
            'speed_down' => 100000,
        ]);

        $this->assertSame(60000, $connectionProfile->getSpeedDownCommon(0.6));
        $this->assertSame(30000, $connectionProfile->getSpeedDownMinimum(0.3));
    }

    /**
     * Test that a derived speed is rounded to whole kbps.
     *
     * @return void
     * @link \App\Model\Entity\ConnectionProfile::getSpeedUpCommon()
     */
    public function testDerivedSpeedIsRounded(): void
    {
        $connectionProfile = new ConnectionProfile([
            'speed_up' => 10001,
        ]);

        $this->assertSame(6001, $connectionProfile->getSpeedUpCommon(0.6));
    }

    /**
     * Test that nothing is derived when the tariff advertises no speed either.
     *
     * @return void
     * @link \App\Model\Entity\ConnectionProfile::getSpeedUpCommon()
     */
    public function testNoAdvertisedSpeedDerivesNothing(): void
    {
        $connectionProfile = new ConnectionProfile([]);

        $this->assertNull($connectionProfile->getSpeedUpCommon(0.6));
        $this->assertNull($connectionProfile->getSpeedUpMinimum(0.3));
        $this->assertNull($connectionProfile->getSpeedDown());
    }

    /**
     * Test that a declared speed of zero is an answer rather than an absence.
     *
     * @return void
     * @link \App\Model\Entity\ConnectionProfile::getSpeedDownMinimum()
     */
    public function testDeclaredZeroIsNotTakenForUndeclared(): void
    {
        $connectionProfile = new ConnectionProfile([
            'speed_down' => 100000,
            'speed_down_minimum' => 0,
        ]);

        $this->assertSame(0, $connectionProfile->getSpeedDownMinimum(0.3));
    }

    /**
     * Test that the advertised speed is what the maximum getters hand back.
     *
     * @return void
     * @link \App\Model\Entity\ConnectionProfile::getSpeedDown()
     */
    public function testAdvertisedSpeedIsTheMaximumOne(): void
    {
        $connectionProfile = new ConnectionProfile([
            'speed_down' => 100000,
            'speed_up' => 20000,
        ]);

        $this->assertSame(100000, $connectionProfile->getSpeedDown());
        $this->assertSame(20000, $connectionProfile->getSpeedUp());
    }

    /**
     * Test that omitting the coefficient still derives, on the shipped default.
     *
     * @return void
     * @link \App\Model\Entity\ConnectionProfile::getSpeedDownCommon()
     */
    public function testOmittedCoefficientFallsBackToTheShippedDefault(): void
    {
        $connectionProfile = new ConnectionProfile([
            'speed_down' => 100000,
        ]);

        $this->assertSame(60000, $connectionProfile->getSpeedDownCommon());
        $this->assertSame(30000, $connectionProfile->getSpeedDownMinimum());
    }
}
