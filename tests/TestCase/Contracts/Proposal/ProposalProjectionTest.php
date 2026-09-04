<?php
declare(strict_types=1);

namespace App\Test\TestCase\Contracts\Proposal;

use App\Contracts\Proposal\ProposalChanges;
use App\Contracts\Proposal\ProposalProjection;
use App\Contracts\Proposal\ProposedBilling;
use App\Contracts\Proposal\ProposedVersion;
use App\Model\Entity\Billing;
use App\Model\Entity\ContractVersion;
use App\Model\Entity\Service;
use Cake\I18n\Date;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Contracts\Proposal\ProposalProjection Test Case
 */
#[CoversClass(ProposalProjection::class)]
#[UsesClass(ProposalChanges::class)]
#[UsesClass(ProposedBilling::class)]
#[UsesClass(ProposedVersion::class)]
class ProposalProjectionTest extends TestCase
{
    /**
     * The day everything below takes effect.
     */
    private const EFFECTIVE_FROM = '2026-10-01';

    /**
     * The day before it.
     */
    private const DAY_BEFORE = '2026-09-30';

    /**
     * A billing as the snapshot took it.
     *
     * @param string $id Which billing.
     * @param string $text What it is called.
     * @return \App\Model\Entity\Billing
     */
    private function billing(string $id, string $text = 'Internet'): Billing
    {
        return new Billing([
            'id' => $id,
            'billing_from' => new Date('2025-01-01'),
            'billing_until' => null,
            'text' => $text,
            'quantity' => 1,
            'price' => null,
            'separate_invoice' => false,
        ]);
    }

    /**
     * The projection of the given changes over the given billings.
     *
     * @param array<\App\Model\Entity\Billing> $billings What the snapshot took.
     * @param array<string, mixed> $changes What the proposal asks for.
     * @param array<string, \App\Model\Entity\Service> $services The services the lines name.
     * @return array<\App\Model\Entity\Billing>
     */
    private function project(array $billings, array $changes, array $services = []): array
    {
        return (new ProposalProjection())->projectBillings(
            $billings,
            ProposalChanges::fromArray($changes),
            new Date(self::EFFECTIVE_FROM),
            $services,
        );
    }

    /**
     * A proposal that changes nothing leaves the billings exactly as they were - which is the
     * ordinary case, because a new contract has its billings in place before the papers are drawn
     * up.
     *
     * @return void
     */
    public function testNothingProposedLeavesEverythingAlone(): void
    {
        $billings = [$this->billing('b1'), $this->billing('b2', 'Television')];

        $projected = $this->project($billings, []);

        $this->assertCount(2, $projected);
        $this->assertNull($projected[0]->billing_until);
        $this->assertNull($projected[1]->billing_until);
    }

    /**
     * A replaced billing stops the day before, and what takes its place starts on the day itself -
     * the same two halves the transfer will write.
     *
     * @return void
     */
    public function testAReplacedBillingStopsTheDayBeforeItsReplacementStarts(): void
    {
        $projected = $this->project(
            [$this->billing('b1')],
            ['billings' => [[
                'billing_id' => 'b1',
                'service_id' => 's2',
                'quantity' => 1,
                'price' => '299.00',
            ]]],
        );

        $this->assertCount(2, $projected);

        [$ending, $starting] = $projected;
        $this->assertSame(self::DAY_BEFORE, $ending->billing_until?->toDateString());
        $this->assertSame(self::EFFECTIVE_FROM, $starting->billing_from?->toDateString());
        $this->assertSame('299.00', $starting->price?->toString());
        $this->assertNull($starting->id);
    }

    /**
     * A line that only ends a billing leaves nothing behind it.
     *
     * @return void
     */
    public function testAnEndedBillingHasNothingTakeItsPlace(): void
    {
        $projected = $this->project(
            [$this->billing('b1')],
            ['billings' => [['billing_id' => 'b1', 'terminates_only' => true]]],
        );

        $this->assertCount(1, $projected);
        $this->assertSame(self::DAY_BEFORE, $projected[0]->billing_until?->toDateString());
    }

    /**
     * A line without a billing puts one there that was not there before - which is how a contract
     * looks when its billings are only drawn up once the papers come back signed.
     *
     * @return void
     */
    public function testAnAddedBillingAppearsOnTheDay(): void
    {
        $projected = $this->project([], ['billings' => [[
            'billing_id' => null,
            'service_id' => 's1',
            'quantity' => 2,
        ]]]);

        $this->assertCount(1, $projected);
        $this->assertSame(self::EFFECTIVE_FROM, $projected[0]->billing_from?->toDateString());
        $this->assertSame(2, $projected[0]->quantity);
    }

    /**
     * Billings the proposal says nothing about pass through untouched.
     *
     * @return void
     */
    public function testWhatIsNotSpokenOfPassesThrough(): void
    {
        $projected = $this->project(
            [$this->billing('b1'), $this->billing('b2', 'Television')],
            ['billings' => [['billing_id' => 'b1', 'terminates_only' => true]]],
        );

        $untouched = array_values(array_filter(
            $projected,
            fn(Billing $billing): bool => $billing->id === 'b2',
        ));

        $this->assertCount(1, $untouched);
        $this->assertNull($untouched[0]->billing_until);
    }

    /**
     * A line that changes the service brings the one it chose, because the contract's own snapshot
     * was taken before it was chosen and has never heard of it.
     *
     * @return void
     */
    public function testALineThatChangesTheServiceBringsIt(): void
    {
        $chosen = new Service(['id' => 's2', 'name' => 'Internet 100', 'price' => '399.00']);

        $projected = $this->project(
            [$this->billing('b1')],
            ['billings' => [['billing_id' => 'b1', 'service_id' => 's2']]],
            ['s2' => $chosen],
        );

        $this->assertSame('Internet 100', $projected[1]->service?->name);
    }

    /**
     * Two printings of one proposal have to look the same, so the order is settled here rather than
     * left to whatever order the lines were written in.
     *
     * @return void
     */
    public function testTheOrderDoesNotDependOnHowTheLinesWereWritten(): void
    {
        $billings = [$this->billing('b1', 'Zulu'), $this->billing('b2', 'Alpha')];

        $one = $this->project($billings, []);
        $other = $this->project(array_reverse($billings), []);

        $this->assertSame(
            array_map(fn(Billing $billing): string => (string)$billing->name, $one),
            array_map(fn(Billing $billing): string => (string)$billing->name, $other),
        );
        $this->assertSame('Alpha', (string)$one[0]->name);
    }

    /**
     * The version takes what the proposal asks of it, including being asked to have no obligation
     * at all - which is a different thing from not being asked about it.
     *
     * @return void
     */
    public function testTheVersionTakesWhatIsAskedOfIt(): void
    {
        $version = new ContractVersion([
            'id' => 'v1',
            'valid_from' => new Date('2025-01-01'),
            'valid_until' => null,
            'obligation_until' => new Date('2027-01-01'),
        ]);

        $projection = new ProposalProjection();

        $ended = $projection->projectVersion(
            $version,
            ProposedVersion::fromArray(['valid_until' => '2026-12-31', 'obligation_until' => null]),
        );
        $this->assertSame('2026-12-31', $ended->valid_until?->toDateString());
        $this->assertNull($ended->obligation_until);

        $untouched = $projection->projectVersion($version, ProposedVersion::untouched());
        $this->assertNull($untouched->valid_until);
        $this->assertSame('2027-01-01', $untouched->obligation_until?->toDateString());

        $this->assertNull($version->valid_until, 'The record the projection was made from moved.');
    }

    /**
     * The version a proposal replaces stops the day before the replacement takes effect.
     *
     * @return void
     */
    public function testAReplacedVersionStopsTheDayBefore(): void
    {
        $version = new ContractVersion(['id' => 'v1', 'valid_until' => null]);

        $projected = (new ProposalProjection())
            ->projectTerminatedVersion($version, new Date(self::EFFECTIVE_FROM));

        $this->assertSame(self::DAY_BEFORE, $projected->valid_until?->toDateString());
        $this->assertNull($version->valid_until, 'The record the projection was made from moved.');
    }
}
