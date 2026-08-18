<?php
declare(strict_types=1);

namespace Maps\Test\TestCase;

use Cake\TestSuite\TestCase;
use Maps\Geocoder\FirstAnsweringGeocoder;
use Maps\Geocoder\GeocoderInterface;
use Maps\Geocoder\Suggestion;
use Maps\Position;
use PHPUnit\Framework\Attributes\UsesClass;
use RuntimeException;

/**
 * Maps\Geocoder\FirstAnsweringGeocoder Test Case
 */
#[UsesClass(FirstAnsweringGeocoder::class)]
class FirstAnsweringGeocoderTest extends TestCase
{
    /**
     * A geocoder that answers with what it was told to, or fails when told to fail.
     */
    private function geocoder(?Suggestion $answer, bool $fails = false): GeocoderInterface
    {
        return new class ($answer, $fails) implements GeocoderInterface {
            public function __construct(private ?Suggestion $answer, private bool $fails)
            {
            }

            public function search(string $query, ?string $country = null, int $limit = 5): array
            {
                if ($this->fails) {
                    throw new RuntimeException('down');
                }

                return $this->answer === null ? [] : [$this->answer];
            }

            public function reverse(Position $position, ?string $country = null): ?Suggestion
            {
                if ($this->fails) {
                    throw new RuntimeException('down');
                }

                return $this->answer;
            }
        };
    }

    /**
     * The first one that has something to say is the one that is heard.
     *
     * @return void
     */
    public function testTheFirstAnswerIsTheOneTaken(): void
    {
        $first = new Suggestion('The registry', new Position(50.0, 15.0));
        $second = new Suggestion('The world map', new Position(51.0, 16.0));

        $geocoder = new FirstAnsweringGeocoder([
            $this->geocoder($first),
            $this->geocoder($second),
        ]);

        $this->assertSame('The registry', $geocoder->search('somewhere')[0]->label);
        $this->assertSame('The registry', $geocoder->reverse(new Position(50.0, 15.0))?->label);
    }

    /**
     * One with nothing to say is passed over rather than taken for the answer.
     *
     * @return void
     */
    public function testSilenceIsPassedOver(): void
    {
        $answer = new Suggestion('The world map', new Position(51.0, 16.0));

        $geocoder = new FirstAnsweringGeocoder([
            $this->geocoder(null),
            $this->geocoder($answer),
        ]);

        $this->assertSame('The world map', $geocoder->search('somewhere')[0]->label);
        $this->assertSame('The world map', $geocoder->reverse(new Position(51.0, 16.0))?->label);
    }

    /**
     * One that is down costs precision rather than the search itself.
     *
     * @return void
     */
    public function testAFailingGeocoderDoesNotEndTheSearch(): void
    {
        $answer = new Suggestion('The world map', new Position(51.0, 16.0));

        $geocoder = new FirstAnsweringGeocoder([
            $this->geocoder(null, fails: true),
            $this->geocoder($answer),
        ]);

        $this->assertSame('The world map', $geocoder->search('somewhere')[0]->label);
        $this->assertSame('The world map', $geocoder->reverse(new Position(51.0, 16.0))?->label);
    }

    /**
     * Nobody having anything to say is an answer of its own.
     *
     * @return void
     */
    public function testNoAnswerAtAll(): void
    {
        $geocoder = new FirstAnsweringGeocoder([
            $this->geocoder(null),
            $this->geocoder(null, fails: true),
        ]);

        $this->assertSame([], $geocoder->search('somewhere'));
        $this->assertNull($geocoder->reverse(new Position(50.0, 15.0)));
    }
}
