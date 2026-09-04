<?php
declare(strict_types=1);

namespace App\Test\TestCase\Contracts\Proposal;

use App\Contracts\Proposal\ProposalChanges;
use App\Contracts\Proposal\ProposedBilling;
use App\Contracts\Proposal\ProposedContract;
use App\Contracts\Proposal\ProposedVersion;
use Cake\I18n\Date;
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
                    'id' => 'l1000000-0000-4000-8000-000000000001',
                    'billing_id' => 'b1000000-0000-4000-8000-000000000001',
                    'terminates_only' => false,
                    'service_id' => 'eaacfeb3-1430-43ce-842e-497c5c95d953',
                    'text' => null,
                    'quantity' => 2,
                    'price' => '199.00',
                    'fixed_discount' => '10.00',
                    'percentage_discount' => 5,
                    'billing_from' => '2027-01-01',
                    'billing_until' => '2027-12-31',
                    'separate_invoice' => true,
                    'note' => 'Lorem ipsum',
                    'service' => ['id' => 's2', 'name' => 'Internet 100'],
                ],
            ],
            'version' => ['valid_until' => '2026-03-31'],
            'contract' => ['termination_date' => '2026-03-31'],
        ];

        $this->assertSame($stored, ProposalChanges::fromArray($stored)->toArray());
    }

    /**
     * A line carries its own days, which is what lets one change be several: half price for a year
     * and then full price is two lines, the first ending where the second picks up.
     *
     * @return void
     */
    public function testOneChangeMayBeSeveralLines(): void
    {
        $changes = ProposalChanges::fromArray(['billings' => [
            [
                'billing_id' => 'b1',
                'service_id' => 's1',
                'percentage_discount' => 50,
                'billing_until' => '2026-12-31',
            ],
            ['billing_id' => null, 'service_id' => 's1', 'billing_from' => '2027-01-01'],
        ]]);

        [$discounted, $full] = $changes->billings;

        $this->assertNull($discounted->billing_from, 'A line without a day of its own starts with the proposal.');
        $this->assertSame('2026-12-31', $discounted->billing_until?->toDateString());
        $this->assertSame('2027-01-01', $full->billing_from?->toDateString());

        // Which is what it comes to once the proposal says when it takes effect.
        $this->assertSame('2026-01-01', $discounted->startsOn(new Date('2026-01-01'))->toDateString());
        $this->assertSame('2027-01-01', $full->startsOn(new Date('2026-01-01'))->toDateString());
    }

    /**
     * Every line answers to a name of its own, so that it can be edited and dropped again without
     * counting places in a list.
     *
     * @return void
     */
    public function testALineCanBeChangedAndDroppedByName(): void
    {
        $changes = ProposalChanges::fromArray(['billings' => [
            ['id' => 'one', 'billing_id' => 'b1', 'terminates_only' => true],
            ['id' => 'two', 'billing_id' => null, 'service_id' => 's1'],
        ]]);

        $this->assertNotNull($changes->line('one'));

        $two = $changes->line('two');
        $this->assertNotNull($two);

        $changed = $changes->withLine($two->with(['quantity' => 5]));
        $this->assertCount(2, $changed->billings);
        $this->assertSame(5, $changed->line('two')?->quantity);

        $dropped = $changed->withoutLine('one');
        $this->assertCount(1, $dropped->billings);
        $this->assertNull($dropped->line('one'));
    }

    /**
     * A line drawn up without a name is given one, so that nothing in the store is nameless.
     *
     * @return void
     */
    public function testALineWithoutANameIsGivenOne(): void
    {
        $line = ProposedBilling::fromArray(['billing_id' => null, 'service_id' => 's1']);

        $this->assertNotEmpty($line->id);
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
