<?php
declare(strict_types=1);

namespace App\Test\TestCase\BusinessRegister\Source;

use App\BusinessRegister\Source\SudregSource;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * App\BusinessRegister\SudregSource Test Case
 *
 * Only the reading of an answer is checked here. Asking the register is left alone: it needs
 * credentials, and a test of it would only be as reliable as the register itself.
 */
#[CoversClass(SudregSource::class)]
class SudregSourceTest extends TestCase
{
    /**
     * A subject comes out in the shape every register answers in, with the seat put back onto
     * one line and the VAT number made of the identification number the way Croatia does.
     *
     * @return void
     * @link \App\BusinessRegister\SudregSource::mapSubject()
     */
    public function testSubjectIsReadIntoTheSharedShape(): void
    {
        $subject = [
            'oib' => '80625159724',
            'mbs' => '080000000',
            'skracena_tvrtka' => ['ime' => 'Primjer d.o.o.'],
            'sjediste' => [
                'ulica' => 'Ilica',
                'kucni_broj' => '1',
                'naziv_naselja' => 'Zagreb',
            ],
        ];

        $this->assertSame(
            [
                'reference' => '80625159724',
                'company' => 'Primjer d.o.o.',
                'identity_number' => '80625159724',
                'vat_number' => 'HR80625159724',
                'address' => 'Ilica 1, Zagreb',
            ],
            SudregSource::mapSubject($subject),
        );
    }

    /**
     * The register holds the name under whichever of its forms it has, so the full name answers
     * where the shortened one is missing.
     *
     * @return void
     * @link \App\BusinessRegister\SudregSource::mapSubject()
     */
    public function testFullNameAnswersWhereTheShortenedOneIsMissing(): void
    {
        $subject = [
            'oib' => '80625159724',
            'tvrtka' => ['ime' => 'Primjer društvo s ograničenom odgovornošću'],
        ];

        $this->assertSame(
            'Primjer društvo s ograničenom odgovornošću',
            SudregSource::mapSubject($subject)['company'],
        );
    }

    /**
     * A subject without a number has nothing to be fetched back by.
     *
     * @return void
     * @link \App\BusinessRegister\SudregSource::mapSubject()
     */
    public function testSubjectWithoutANumberHasNoReference(): void
    {
        $mapped = SudregSource::mapSubject(['skracena_tvrtka' => ['ime' => 'Primjer d.o.o.']]);

        $this->assertNull($mapped['reference']);
        $this->assertNull($mapped['identity_number']);
        $this->assertNull($mapped['vat_number']);
    }

    /**
     * A seat the register does not hold leaves the entry without an address, not with an empty
     * one made of the separators between its missing parts.
     *
     * @return void
     * @link \App\BusinessRegister\SudregSource::mapSubject()
     */
    public function testMissingSeatLeavesNoAddress(): void
    {
        $this->assertNull(SudregSource::mapSubject(['oib' => '80625159724'])['address']);
        $this->assertNull(SudregSource::mapSubject([
            'oib' => '80625159724',
            'sjediste' => ['ulica' => '', 'naziv_naselja' => ''],
        ])['address']);
    }
}
