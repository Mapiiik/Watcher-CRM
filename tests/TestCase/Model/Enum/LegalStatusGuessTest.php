<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Enum;

use App\Model\Entity\Customer;
use App\Model\Enum\CustomerLegalStatus;
use App\Model\Enum\LegalStatusGuess;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * App\Model\Enum\LegalStatusGuess Test Case
 */
#[CoversClass(LegalStatusGuess::class)]
class LegalStatusGuessTest extends TestCase
{
    /**
     * By the identification number, a customer without one is a household, one with a company
     * beside it a legal entity, and one with only the number a sole trader.
     *
     * @return void
     * @link \App\Model\Enum\LegalStatusGuess::legalStatusOf()
     */
    public function testTheIdentificationNumberTellsAllThree(): void
    {
        $guess = LegalStatusGuess::IdentityNumber;

        $this->assertSame(CustomerLegalStatus::NaturalPerson, $guess->legalStatusOf(new Customer([
            'company' => 'NETAIR, s.r.o.',
        ])));
        $this->assertSame(CustomerLegalStatus::LegalEntity, $guess->legalStatusOf(new Customer([
            'identity_number' => '27496139',
            'company' => 'NETAIR, s.r.o.',
        ])));
        $this->assertSame(CustomerLegalStatus::SoleTrader, $guess->legalStatusOf(new Customer([
            'identity_number' => '27496139',
            'company' => '  ',
        ])));
    }

    /**
     * By the company name, everybody has a number, so only the company tells - and nothing tells a
     * sole trader apart.
     *
     * @return void
     * @link \App\Model\Enum\LegalStatusGuess::legalStatusOf()
     */
    public function testTheCompanyTellsALegalEntityFromAHousehold(): void
    {
        $guess = LegalStatusGuess::Company;

        $this->assertSame(CustomerLegalStatus::LegalEntity, $guess->legalStatusOf(new Customer([
            'identity_number' => '80625159724',
            'company' => 'Primjer d.o.o.',
        ])));
        $this->assertSame(CustomerLegalStatus::NaturalPerson, $guess->legalStatusOf(new Customer([
            'identity_number' => '80625159724',
        ])));
    }
}
