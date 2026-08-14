<?php
declare(strict_types=1);

namespace App\Test\TestCase\BusinessRegister\Source;

use App\BusinessRegister\Source\ViesSource;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * App\BusinessRegister\ViesSource Test Case
 *
 * Only the reading of an answer is checked here. Asking VIES is left alone: a test of that
 * would only be as reliable as the service itself.
 */
#[CoversClass(ViesSource::class)]
class ViesSourceTest extends TestCase
{
    /**
     * A confirmed number comes out in the shape every register answers in, with the address on
     * the one line a suggestion has room for.
     *
     * @return void
     * @link \App\BusinessRegister\ViesSource::mapSubject()
     */
    public function testConfirmedNumberIsReadIntoTheSharedShape(): void
    {
        $subject = [
            'isValid' => true,
            'name' => 'Asseco Central Europe, a.s.',
            'address' => "Budějovická 778/3a\nPRAHA 4 - MICHLE\n140 00  PRAHA 4",
        ];

        $this->assertSame(
            [
                'reference' => 'CZ27074358',
                'name' => 'Asseco Central Europe, a.s.',
                'company' => 'Asseco Central Europe, a.s.',
                'title' => null,
                'first_name' => null,
                'last_name' => null,
                'suffix' => null,
                'date_of_birth' => null,
                'officers' => [],
                'identity_number' => '27074358',
                'vat_number' => 'CZ27074358',
                'address' => 'Budějovická 778/3a, PRAHA 4 - MICHLE, 140 00  PRAHA 4',
                'address_key' => null,
            ],
            ViesSource::mapSubject($subject, 'CZ', '27074358'),
        );
    }

    /**
     * A number VIES does not confirm is not an entry.
     *
     * @return void
     * @link \App\BusinessRegister\ViesSource::mapSubject()
     */
    public function testUnconfirmedNumberIsNotAnEntry(): void
    {
        $subject = ['isValid' => false, 'name' => '---', 'address' => '---'];

        $this->assertNull(ViesSource::mapSubject($subject, 'HR', '00000000000'));
    }

    /**
     * VIES writes a row of dashes where it holds nothing, which is nothing and not a name.
     *
     * @return void
     * @link \App\BusinessRegister\ViesSource::mapSubject()
     */
    public function testPlaceholdersAreReadAsNothing(): void
    {
        $subject = ['isValid' => true, 'name' => '---', 'address' => '---'];

        $mapped = ViesSource::mapSubject($subject, 'CZ', '27074358');

        $this->assertIsArray($mapped);
        $this->assertSame('', $mapped['company']);
        $this->assertNull($mapped['address']);
    }

    /**
     * The number without its prefix is only taken as an identification number where it holds up
     * as one - a Czech VAT number may be a personal one that is not an IČO at all.
     *
     * @return void
     * @link \App\BusinessRegister\ViesSource::mapSubject()
     */
    public function testNumberIsOnlyTakenAsAnIdentityNumberWhereItHoldsUp(): void
    {
        $subject = ['isValid' => true, 'name' => 'Someone', 'address' => 'Somewhere'];

        $personal = ViesSource::mapSubject($subject, 'CZ', '6012310123');
        $this->assertIsArray($personal);
        $this->assertNull($personal['identity_number']);

        $croatian = ViesSource::mapSubject($subject, 'HR', '80625159724');
        $this->assertIsArray($croatian);
        $this->assertSame('80625159724', $croatian['identity_number']);
    }
}
