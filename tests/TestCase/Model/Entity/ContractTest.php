<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Entity;

use App\Model\Entity\Address;
use App\Model\Entity\Contract;
use App\Model\Entity\ContractVersion;
use App\Model\Entity\ServiceType;
use Cake\I18n\Date;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;

/**
 * App\Model\Entity\Contract Test Case
 */
#[CoversClass(Contract::class)]
class ContractTest extends TestCase
{
    /**
     * Builds a contract with the given obligation dates as contract versions.
     *
     * @param array<string|null> $obligationDates Obligation dates, null for a version without an obligation.
     * @return \App\Model\Entity\Contract
     */
    private function contractWithObligations(array $obligationDates): Contract
    {
        $contractVersions = [];
        foreach ($obligationDates as $obligationDate) {
            $contractVersions[] = new ContractVersion([
                'obligation_until' => $obligationDate !== null ? new Date($obligationDate) : null,
            ]);
        }

        return new Contract([
            'contract_versions' => $contractVersions,
        ]);
    }

    /**
     * Test that the name is built from the number, the service and the address.
     *
     * @return void
     * @link \App\Model\Entity\Contract::_getName()
     */
    public function testNameJoinsAllParts(): void
    {
        $contract = new Contract([
            'number' => 'A458',
            'service_type' => new ServiceType([
                'name' => 'Internet 100',
            ]),
            'installation_address' => new Address([
                'street' => 'Studentska',
                'number' => '1903/14a',
                'zip' => '11000',
                'city' => 'Praha',
            ]),
        ]);

        $this->assertSame('A458 - Internet 100 - Studentska 1903/14a, 110 00 Praha', $contract->name);
    }

    /**
     * Test that a contract without a service and an address is just its number.
     *
     * @return void
     * @link \App\Model\Entity\Contract::_getName()
     */
    public function testNameWithNumberOnly(): void
    {
        $contract = new Contract([
            'number' => 'A458',
        ]);

        $this->assertSame('A458', $contract->name);
    }

    /**
     * Test that a contract without a number does not start with a separator.
     *
     * @return void
     * @link \App\Model\Entity\Contract::_getName()
     */
    public function testNameWithoutNumberHasNoLeadingSeparator(): void
    {
        $contract = new Contract([
            'service_type' => new ServiceType([
                'name' => 'Internet 100',
            ]),
        ]);

        $this->assertSame('Internet 100', $contract->name);
    }

    /**
     * Test that the latest obligation date wins, no matter the order of the versions.
     *
     * @return void
     * @link \App\Model\Entity\Contract::getMaxObligationUntil()
     */
    public function testGetMaxObligationUntilReturnsLatestDate(): void
    {
        $contract = $this->contractWithObligations(['2024-06-30', '2027-03-31', '2025-12-31']);

        $this->assertEquals(new Date('2027-03-31'), $contract->getMaxObligationUntil());
    }

    /**
     * Test that versions without an obligation are skipped.
     *
     * @return void
     * @link \App\Model\Entity\Contract::getMaxObligationUntil()
     */
    public function testGetMaxObligationUntilIgnoresVersionsWithoutObligation(): void
    {
        $contract = $this->contractWithObligations([null, '2025-12-31', null]);

        $this->assertEquals(new Date('2025-12-31'), $contract->getMaxObligationUntil());
    }

    /**
     * Test that a contract without any obligation returns null.
     *
     * @return void
     * @link \App\Model\Entity\Contract::getMaxObligationUntil()
     */
    public function testGetMaxObligationUntilReturnsNullWithoutAnyObligation(): void
    {
        $contract = $this->contractWithObligations([null, null]);

        $this->assertNull($contract->getMaxObligationUntil());
    }

    /**
     * Test that a contract without any version returns null.
     *
     * @return void
     * @link \App\Model\Entity\Contract::getMaxObligationUntil()
     */
    public function testGetMaxObligationUntilReturnsNullWithoutAnyVersion(): void
    {
        $contract = $this->contractWithObligations([]);

        $this->assertNull($contract->getMaxObligationUntil());
    }

    /**
     * Test that not loading the association is reported instead of silently returning null.
     *
     * @return void
     * @link \App\Model\Entity\Contract::getMaxObligationUntil()
     */
    public function testGetMaxObligationUntilThrowsWhenVersionsNotLoaded(): void
    {
        $contract = new Contract([
            'number' => 'A458',
        ]);

        $this->expectException(RuntimeException::class);

        $contract->getMaxObligationUntil();
    }
}
