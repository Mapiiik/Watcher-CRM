<?php
declare(strict_types=1);

namespace App\Test\TestCase\Addresses\Provider;

use App\Addresses\Provider\AddressPayloadNormalizer;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * App\Addresses\Provider\AddressPayloadNormalizer Test Case
 *
 * Two national registries answer through one API and they do not carry the same fields, so what is
 * pinned down here is the reading being forgiving in the right places and unforgiving in the one
 * that matters: an address with no registry and no number in it cannot be pointed back at, and is
 * passed over rather than kept half-read.
 */
#[CoversClass(AddressPayloadNormalizer::class)]
class AddressPayloadNormalizerTest extends TestCase
{
    /**
     * An address as the registry sends it, cut down to the fields that are read.
     *
     * @return array<string, mixed>
     */
    private function entry(): array
    {
        return [
            'source' => 'cz',
            'registry_ref' => '41405609',
            'formatted_address' => 'Budějovická 778/3a, Michle, 14000 Praha 4',
            'street' => 'Budějovická',
            'house_number' => '778',
            'city' => 'Praha',
            'postal_code' => '14000',
            'number_type' => 'house',
            'geometry' => ['type' => 'Point', 'coordinates' => [14.44, 50.05]],
        ];
    }

    /**
     * The address comes out with the registry and its number as the handle on it, and the
     * coordinates the right way round.
     *
     * @return void
     * @link \App\Addresses\Provider\AddressPayloadNormalizer::address()
     */
    public function testAnAddressIsReadWithItsHandleAndItsPlace(): void
    {
        $address = AddressPayloadNormalizer::address($this->entry());

        $this->assertNotNull($address);
        $this->assertSame('cz|41405609', $address->key());
        $this->assertSame('Budějovická', $address->street);
        // GeoJSON names the coordinates the other way round from the way one is written
        $this->assertSame(50.05, $address->latitude);
        $this->assertSame(14.44, $address->longitude);
        $this->assertFalse($address->hasRegistrationNumber());
    }

    /**
     * A registration number is written differently from a house number, so which one it is travels
     * with the address rather than being guessed at from its shape.
     *
     * @return void
     * @link \App\Addresses\Provider\AddressPayloadNormalizer::address()
     */
    public function testARegistrationNumberSaysSoOfItself(): void
    {
        $address = AddressPayloadNormalizer::address(['number_type' => 'registration'] + $this->entry());

        $this->assertTrue($address?->hasRegistrationNumber());
    }

    /**
     * An address with nothing to point back at is passed over: the registry and the number in it
     * are the only handle this application has.
     *
     * @return void
     * @link \App\Addresses\Provider\AddressPayloadNormalizer::addresses()
     */
    public function testAnAddressWithNoHandleIsPassedOver(): void
    {
        $addresses = AddressPayloadNormalizer::addresses([
            ['formatted_address' => 'Nowhere in particular'],
            $this->entry(),
            'not an address at all',
        ]);

        $this->assertSame(1, $addresses->count());
    }

    /**
     * A field the registry left out, or wrote as something nobody expected, is read as not being
     * there rather than as a reason to stop.
     *
     * @return void
     * @link \App\Addresses\Provider\AddressPayloadNormalizer::address()
     */
    public function testWhatIsMissingOrOddIsReadAsNotBeingThere(): void
    {
        $address = AddressPayloadNormalizer::address([
            'source' => 'hr',
            'registry_ref' => '12345',
            'street' => '',
            'geometry' => ['coordinates' => ['nonsense', null]],
        ]);

        $this->assertNotNull($address);
        $this->assertNull($address->street);
        $this->assertNull($address->latitude);
        $this->assertNull($address->longitude);
    }

    /**
     * More than one match is the registry refusing to choose, which is not the same as its having
     * found nothing - so the lookup says which it was and hands over neither.
     *
     * @return void
     * @link \App\Addresses\Provider\AddressPayloadNormalizer::lookup()
     */
    public function testALookupThatFoundSeveralWillNotChooseBetweenThem(): void
    {
        $lookup = AddressPayloadNormalizer::lookup([
            'matches' => [$this->entry(), ['source' => 'cz', 'registry_ref' => '2'] + $this->entry()],
            'ambiguous' => true,
            'fallback_step' => 3,
        ]);

        $this->assertTrue($lookup->ambiguous);
        $this->assertSame(3, $lookup->fallbackStep);
        $this->assertSame(2, $lookup->matches->count());
        $this->assertNull($lookup->only());
    }

    /**
     * One match is the answer a form can act on.
     *
     * @return void
     * @link \App\Addresses\Provider\AddressPayloadNormalizer::lookup()
     */
    public function testALookupThatFoundOneHandsItOver(): void
    {
        $lookup = AddressPayloadNormalizer::lookup(['matches' => [$this->entry()], 'ambiguous' => false]);

        $this->assertSame('cz|41405609', $lookup->only()?->key());
    }

    /**
     * A lookup the registry answered nothing for is not ambiguous, it is empty.
     *
     * @return void
     * @link \App\Addresses\Provider\AddressPayloadNormalizer::lookup()
     */
    public function testALookupThatFoundNothingIsEmptyRatherThanAmbiguous(): void
    {
        $lookup = AddressPayloadNormalizer::lookup([]);

        $this->assertFalse($lookup->ambiguous);
        $this->assertTrue($lookup->matches->isEmpty());
        $this->assertNull($lookup->only());
    }

    /**
     * A whole set comes back keyed the way this application stores its references, and what the
     * registry never heard of is kept apart rather than left out.
     *
     * @return void
     * @link \App\Addresses\Provider\AddressPayloadNormalizer::batch()
     */
    public function testASetComesBackKeyedAndWithWhatWasNotFound(): void
    {
        $batch = AddressPayloadNormalizer::batch([
            'matches' => [$this->entry()],
            'not_found' => [['source' => 'hr', 'registry_id' => '999']],
        ]);

        $this->assertSame(['cz|41405609'], array_keys($batch->byKey()));
        $this->assertCount(1, $batch->notFound);
    }

    /**
     * A set of written addresses is answered one apiece and in the order they were asked, because
     * that order is the only thing tying an answer back to the address it is about.
     *
     * @return void
     * @link \App\Addresses\Provider\AddressPayloadNormalizer::lookups()
     */
    public function testASetOfLookupsKeepsTheOrderItWasAskedIn(): void
    {
        $lookups = AddressPayloadNormalizer::lookups([
            'results' => [
                ['matches' => []],
                ['matches' => [$this->entry()]],
            ],
        ]);

        $this->assertCount(2, $lookups);
        $this->assertNull($lookups[0]->only());
        $this->assertSame('cz|41405609', $lookups[1]->only()?->key());
    }
}
