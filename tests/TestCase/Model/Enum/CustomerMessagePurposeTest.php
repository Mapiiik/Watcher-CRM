<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Enum;

use App\BulkMessages\BulkRecipientFilterRegistry;
use App\Model\Enum\CustomerMessagePurpose;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Model\Enum\CustomerMessagePurpose Test Case
 *
 * The purpose of a bulk message decides who may be written to. It names the consent the customer has
 * to have given and the flag the address has to carry, so a purpose pointing at the wrong column
 * would have a send go out to people who asked not to be written to - which is the one mistake here
 * that cannot be taken back.
 */
#[UsesClass(CustomerMessagePurpose::class)]
class CustomerMessagePurposeTest extends TestCase
{
    /**
     * Every purpose is offered under a name of its own, which is what the operator picks the send by.
     *
     * @return void
     * @link \App\Model\Enum\CustomerMessagePurpose::label()
     */
    public function testEveryPurposeIsOfferedUnderANameOfItsOwn(): void
    {
        $labels = array_map(
            fn(CustomerMessagePurpose $purpose): string => $purpose->label(),
            CustomerMessagePurpose::cases(),
        );

        $this->assertNotContains('', $labels);
        $this->assertSame(count($labels), count(array_unique($labels)));
    }

    /**
     * Each purpose asks about its own consent and its own routing flag. No two share a column, or
     * saying no to one would silence another.
     *
     * @return void
     * @link \App\Model\Enum\CustomerMessagePurpose::customerConsentField()
     * @link \App\Model\Enum\CustomerMessagePurpose::contactUseField()
     */
    public function testEachPurposeAsksAboutItsOwnConsentAndItsOwnRouting(): void
    {
        $consents = [];
        $routings = [];
        foreach (CustomerMessagePurpose::cases() as $purpose) {
            $consents[] = $purpose->customerConsentField();
            $routings[] = $purpose->contactUseField();
        }

        $this->assertSame(
            ['agree_mailing_billing', 'agree_mailing_outages', 'agree_mailing_commercial'],
            $consents,
        );
        $this->assertSame(
            ['use_for_billing', 'use_for_outages', 'use_for_commercial'],
            $routings,
        );
    }

    /**
     * A purpose is prefilled with a channel of its own, so that a billing message leaves from the
     * address invoices come from rather than from whichever one happens to be first.
     *
     * @return void
     * @link \App\Model\Enum\CustomerMessagePurpose::defaultType()
     */
    public function testEachPurposeIsPrefilledWithAChannelOfItsOwn(): void
    {
        $types = array_map(
            fn(CustomerMessagePurpose $purpose): string => $purpose->defaultType()->name,
            CustomerMessagePurpose::cases(),
        );

        $this->assertSame(count($types), count(array_unique($types)));
    }

    /**
     * The filters a purpose offers are ones the registry knows how to build. A key nothing is
     * registered under is passed over without a word, so the wizard would simply stop offering that
     * filter and the send would go out wider than whoever set it up meant it to.
     *
     * @return void
     * @link \App\Model\Enum\CustomerMessagePurpose::filterKeys()
     */
    public function testTheFiltersAPurposeOffersAreOnesTheRegistryKnows(): void
    {
        /** @var \App\Model\Table\CustomerMessagesTable $customerMessages */
        $customerMessages = $this->getTableLocator()->get('CustomerMessages');
        $registry = new BulkRecipientFilterRegistry($customerMessages);

        foreach (CustomerMessagePurpose::cases() as $purpose) {
            $this->assertSame(
                $purpose->filterKeys(),
                array_keys($registry->forPurpose($purpose)),
                $purpose->name . ' offers a filter nothing is registered under.',
            );
        }
    }

    /**
     * The settings a purpose takes its template from are named apart, so that editing the wording of
     * one does not reword the others.
     *
     * @return void
     * @link \App\Model\Enum\CustomerMessagePurpose::settingsKey()
     */
    public function testEachPurposeTakesItsTemplateFromASettingOfItsOwn(): void
    {
        $keys = array_map(
            fn(CustomerMessagePurpose $purpose): string => $purpose->settingsKey(),
            CustomerMessagePurpose::cases(),
        );

        $this->assertSame(['billing', 'outages', 'commercial'], $keys);
    }
}
