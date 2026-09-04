<?php
declare(strict_types=1);

namespace App\Test\TestCase\Contracts\Proposal;

use App\Contracts\Proposal\ProposalChanges;
use App\Contracts\Proposal\ProposedBilling;
use App\Contracts\Proposal\ProposedContract;
use App\Contracts\Proposal\ProposedVersion;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Contracts\Proposal\ProposalChanges Test Case
 */
#[CoversClass(ProposalChanges::class)]
#[UsesClass(ProposedBilling::class)]
#[UsesClass(ProposedContract::class)]
#[UsesClass(ProposedVersion::class)]
class ProposalChangesTest extends TestCase
{
    /**
     * A proposal that asks for nothing is the ordinary case, not an odd one - a new contract has
     * its billings in place before the papers are printed.
     *
     * @return void
     */
    public function testNothingIsAskedFor(): void
    {
        $changes = ProposalChanges::fromArray([]);

        $this->assertTrue($changes->isEmpty());
        $this->assertSame([], $changes->billings);
        $this->assertTrue($changes->version->isEmpty());
        $this->assertTrue($changes->contract->isEmpty());
        $this->assertSame([], $changes->toArray());
    }

    /**
     * What goes in comes back out the same, so that reading a stored proposal says what writing it
     * said.
     *
     * @return void
     */
    public function testWhatIsStoredIsWhatIsRead(): void
    {
        $stored = [
            'billings' => [
                [
                    'billing_id' => 'b1000000-0000-4000-8000-000000000001',
                    'terminates_only' => false,
                    'service_id' => 'eaacfeb3-1430-43ce-842e-497c5c95d953',
                    'text' => null,
                    'quantity' => 2,
                    'price' => '199.00',
                    'fixed_discount' => '10.00',
                    'percentage_discount' => 5,
                    'separate_invoice' => true,
                    'billing_until' => '2027-12-31',
                    'note' => 'Lorem ipsum',
                ],
            ],
            'version' => ['valid_until' => '2026-03-31'],
            'contract' => ['termination_date' => '2026-03-31'],
        ];

        $this->assertSame($stored, ProposalChanges::fromArray($stored)->toArray());
    }

    /**
     * A record asked for nothing is left out rather than written as empty, so that an untouched
     * record and one asked to change nothing do not read the same.
     *
     * @return void
     */
    public function testARecordAskedForNothingIsLeftOut(): void
    {
        $changes = ProposalChanges::fromArray(['version' => ['valid_until' => '2026-03-31']]);

        $this->assertArrayHasKey('version', $changes->toArray());
        $this->assertArrayNotHasKey('contract', $changes->toArray());
        $this->assertArrayNotHasKey('billings', $changes->toArray());
    }

    /**
     * Naming a field with no date clears it, which is a different thing from not naming it - "the
     * obligation is cancelled" against "the obligation stays as it is".
     *
     * @return void
     */
    public function testClearingAFieldIsNotTheSameAsLeavingItAlone(): void
    {
        $cleared = ProposalChanges::fromArray(['version' => ['obligation_until' => null]])->version;
        $untouched = ProposalChanges::fromArray([])->version;

        $this->assertTrue($cleared->names('obligation_until'));
        $this->assertNull($cleared->get('obligation_until'));
        $this->assertFalse($cleared->sets('obligation_until'));

        $this->assertFalse($untouched->names('obligation_until'));
    }

    /**
     * Asking an unnamed field is a mistake in the caller, not an empty answer.
     *
     * @return void
     */
    public function testAnUnnamedFieldCannotBeAsked(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ProposalChanges::fromArray([])->version->get('valid_until');
    }

    /**
     * Nothing outside the known records gets in, so a typo cannot sit in the column until printing
     * finds it.
     *
     * @return void
     */
    public function testAnUnknownRecordIsRefused(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ProposalChanges::fromArray(['equipment' => []]);
    }

    /**
     * The same for a field of a record that is known.
     *
     * @return void
     */
    public function testAnUnknownFieldIsRefused(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ProposalChanges::fromArray(['version' => ['valid_from' => '2026-01-01']]);
    }

    /**
     * Ending is one act written in two places, and either one says it happened.
     *
     * @return void
     */
    public function testEitherDateSaysTheContractEnds(): void
    {
        $this->assertTrue(
            ProposalChanges::fromArray(['version' => ['valid_until' => '2026-03-31']])->endsTheContract(),
        );
        $this->assertTrue(
            ProposalChanges::fromArray(['contract' => ['termination_date' => '2026-03-31']])->endsTheContract(),
        );
        $this->assertFalse(
            ProposalChanges::fromArray(['version' => ['obligation_until' => '2026-03-31']])->endsTheContract(),
        );
        $this->assertFalse(ProposalChanges::fromArray([])->endsTheContract());
    }

    /**
     * A line without a billing adds one; one with a billing acts on it, and says so.
     *
     * @return void
     */
    public function testALineEitherAddsOrActsOnABilling(): void
    {
        $changes = ProposalChanges::fromArray([
            'billings' => [
                ['billing_id' => null, 'service_id' => 'eaacfeb3-1430-43ce-842e-497c5c95d953'],
                ['billing_id' => 'b1000000-0000-4000-8000-000000000001', 'terminates_only' => true],
            ],
        ]);

        [$added, $ended] = $changes->billings;

        $this->assertTrue($added->isAddition());
        $this->assertTrue($added->startsABilling());

        $this->assertFalse($ended->isAddition());
        $this->assertTrue($ended->terminatesOnly());
        $this->assertFalse($ended->startsABilling());

        $this->assertSame(
            ['b1000000-0000-4000-8000-000000000001'],
            array_keys($changes->billingsByBillingId()),
        );
    }

    /**
     * A line that neither names a billing nor puts one there says nothing at all.
     *
     * @return void
     */
    public function testALineThatAddsNothingCannotEndAnythingEither(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ProposalChanges::fromArray([
            'billings' => [['billing_id' => null, 'terminates_only' => true]],
        ]);
    }

    /**
     * Money survives the trip through the column, which has no decimal type worth trusting.
     *
     * @return void
     */
    public function testMoneyKeepsItsScale(): void
    {
        $line = ProposedBilling::fromArray(['price' => '199.90', 'fixed_discount' => '0.10']);

        $this->assertSame('199.90', $line->price?->toString());
        $this->assertSame('0.10', $line->fixed_discount?->toString());
        $this->assertNull(ProposedBilling::fromArray([])->price);
    }
}
