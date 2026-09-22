<?php
declare(strict_types=1);

namespace App\Test\TestCase\Contracts;

use App\Contracts\MinimumConnectionPrice;
use App\Model\Entity\Billing;
use App\Model\Entity\ConnectionProfile;
use App\Model\Entity\ContractProposal;
use App\Model\Entity\Service;
use Cake\I18n\Date;
use Cake\TestSuite\TestCase;
use PhpCollective\DecimalObject\Decimal;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * App\Contracts\MinimumConnectionPrice Test Case
 */
#[CoversClass(MinimumConnectionPrice::class)]
class MinimumConnectionPriceTest extends TestCase
{
    /**
     * A billing the contract has.
     */
    private const KNOWN_BILLING_ID = 'b2000000-0000-4000-8000-000000000002';

    /**
     * A service read from the table says so by its connection profile id, one kept by a proposal by the connection profile.
     *
     * @return void
     */
    public function testTheConnectionIsTheServiceWithAConnectionProfile(): void
    {
        $this->assertTrue(MinimumConnectionPrice::isConnection($this->billing(new Service(['connection_profile_id' => 1]))));
        $this->assertTrue(
            MinimumConnectionPrice::isConnection($this->billing(new Service(['connection_profile' => new ConnectionProfile(['id' => 1])]))),
        );
        $this->assertFalse(MinimumConnectionPrice::isConnection($this->billing(new Service(['connection_profile_id' => null]))));
        $this->assertFalse(MinimumConnectionPrice::isConnection($this->billing(null)));
    }

    /**
     * What is measured is the price after discounts, the list price where the billing has none.
     *
     * @return void
     */
    public function testThePriceAfterDiscountsIsMeasured(): void
    {
        $minimum = Decimal::create('100');
        $connection = new Service(['connection_profile_id' => 1, 'price' => Decimal::create('100')]);

        $this->assertFalse(MinimumConnectionPrice::fallsBelow($this->billing($connection), $minimum));
        $this->assertTrue(MinimumConnectionPrice::fallsBelow(
            $this->billing($connection, ['percentage_discount' => 10]),
            $minimum,
        ));
        $this->assertTrue(MinimumConnectionPrice::fallsBelow(
            $this->billing($connection, ['price' => Decimal::create('99.99')]),
            $minimum,
        ));
    }

    /**
     * Without a minimum, or beside the connection, nothing falls below anything.
     *
     * @return void
     */
    public function testNothingFallsBelowWhereThereIsNothingToMeasure(): void
    {
        $cheap = ['price' => Decimal::create('1')];

        $this->assertFalse(MinimumConnectionPrice::fallsBelow(
            $this->billing(new Service(['connection_profile_id' => 1]), $cheap),
            null,
        ));
        $this->assertFalse(MinimumConnectionPrice::fallsBelow(
            $this->billing(new Service(['connection_profile_id' => null]), $cheap),
            Decimal::create('100'),
        ));
    }

    /**
     * A line pricing the connection below the minimum is found, one meeting it is not.
     *
     * @return void
     */
    public function testALineBelowTheMinimumIsFound(): void
    {
        $minimum = Decimal::create('100');

        $below = MinimumConnectionPrice::linesBelow($this->proposal([$this->connectionLine('50')]), $minimum);
        $this->assertCount(1, $below);

        $meeting = $this->proposal([$this->connectionLine('100')]);
        $this->assertSame([], MinimumConnectionPrice::linesBelow($meeting, $minimum));
        $this->assertSame([], MinimumConnectionPrice::linesBelow($this->proposal([$this->connectionLine('50')]), null));
    }

    /**
     * What an administrator allowed on the line stands, and ending a billing is not a price at all.
     *
     * @return void
     */
    public function testAnAllowedLineAndAnEndingAreNotFound(): void
    {
        $proposal = $this->proposal([
            $this->connectionLine('50') + ['below_minimum_allowed' => true],
            ['billing_id' => self::KNOWN_BILLING_ID, 'terminates_only' => true],
        ]);

        $this->assertSame([], MinimumConnectionPrice::linesBelow($proposal, Decimal::create('100')));
    }

    /**
     * Where only some lines are asked about, the others are left to whoever asked them before.
     *
     * @return void
     */
    public function testOnlyTheLinesAskedAboutAreFound(): void
    {
        $proposal = $this->proposal([
            $this->connectionLine('50') + ['id' => 'standing'],
            $this->connectionLine('100') + ['id' => 'written'],
        ]);
        $minimum = Decimal::create('100');

        $this->assertSame([], MinimumConnectionPrice::linesBelow(
            $proposal,
            $minimum,
            fn($line): bool => $line->id === 'written',
        ));
        $this->assertCount(1, MinimumConnectionPrice::linesBelow(
            $proposal,
            $minimum,
            fn($line): bool => $line->id === 'standing',
        ));
    }

    /**
     * A billing of one, for the given service.
     *
     * @param \App\Model\Entity\Service|null $service The service.
     * @param array<string, mixed> $terms Anything else it says.
     * @return \App\Model\Entity\Billing
     */
    private function billing(?Service $service, array $terms = []): Billing
    {
        return new Billing($terms + ['quantity' => 1, 'service' => $service]);
    }

    /**
     * A proposal against a contract with one billing, asking for the given lines.
     *
     * @param list<array<string, mixed>> $lines The lines.
     * @return \App\Model\Entity\ContractProposal
     */
    private function proposal(array $lines): ContractProposal
    {
        return new ContractProposal([
            'effective_from' => new Date('2026-10-01'),
            'snapshot' => [
                'contract' => ['id' => 'contract', 'number' => '2022/0001'],
                'customer' => ['nid' => 1, 'addresses' => [], 'emails' => [], 'phones' => []],
                'version' => ['id' => 'version'],
                'billings' => [['id' => self::KNOWN_BILLING_ID, 'billing_from' => '2022-01-01']],
            ],
            'changes' => ['billings' => $lines],
        ]);
    }

    /**
     * A line adding a connection at the given price, carrying its service the way the form does.
     *
     * @param string $price The price.
     * @return array<string, mixed>
     */
    private function connectionLine(string $price): array
    {
        return [
            'service_id' => 'connection',
            'quantity' => 1,
            'price' => $price,
            'service' => [
                'id' => 'connection',
                'name' => 'Internet',
                'price' => '2',
                'connection_profile' => ['id' => 'connection_profile', 'name' => 'Internet'],
            ],
        ];
    }
}
