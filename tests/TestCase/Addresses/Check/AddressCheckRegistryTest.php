<?php
declare(strict_types=1);

namespace App\Test\TestCase\Addresses\Check;

use App\Addresses\Check\AddressCheckInterface;
use App\Addresses\Check\AddressCheckRegistry;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Addresses\Check\AddressCheckRegistry Test Case
 */
#[UsesClass(AddressCheckRegistry::class)]
class AddressCheckRegistryTest extends TestCase
{
    /**
     * The customer the fixtures hang their contracts on, and one of those contracts.
     */
    private const CUSTOMER_ID = '403bab0e-52cd-4a8e-83f8-43c2457d0481';

    private const CONTRACT_ID = '7f76dc3f-a11b-4109-958b-4b0382545a66';

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.AppUsers',
        'app.AccountingProfiles',
        'app.Customers',
        'app.Countries',
        'app.Addresses',
        'app.Commissions',
        'app.ContractStates',
        'app.ServiceTypes',
        'app.Contracts',
    ];

    /**
     * Every registered check has to be able to run and to say how many it found. A check
     * whose query does not hold together is not something to find out from the dashboard.
     *
     * @return void
     * @link \App\Addresses\Check\AddressCheckRegistry::all()
     */
    public function testEveryCheckRuns(): void
    {
        $checks = (new AddressCheckRegistry())->all();

        $this->assertNotEmpty($checks);

        foreach ($checks as $check) {
            $this->assertGreaterThanOrEqual(0, $check->count(), $check->id() . ' cannot be counted.');
            $this->assertNotSame('', $check->title());
            $this->assertNotSame('', $check->emptyMessage());
        }
    }

    /**
     * Ids are what the query string, the anchors and the template names are built from, so
     * two checks answering to the same one would quietly stand in for each other.
     *
     * @return void
     * @link \App\Addresses\Check\AddressCheckRegistry::all()
     */
    public function testIdsAreDistinct(): void
    {
        $ids = array_map(
            fn(AddressCheckInterface $check): string => $check->id(),
            (new AddressCheckRegistry())->all(),
        );

        $this->assertSame($ids, array_unique($ids));
    }

    /**
     * The two that are not lists of faults stay off the dashboard.
     *
     * @return void
     * @link \App\Addresses\Check\AddressCheckRegistry::forDashboard()
     */
    public function testTheCardLeavesTheInformationalChecksAlone(): void
    {
        $registry = new AddressCheckRegistry();

        $ids = array_map(
            fn(AddressCheckInterface $check): string => $check->id(),
            $registry->forDashboard(),
        );

        // neither the strict registry check nor the contracts-at-one-address one is a fault
        $this->assertNotContains('unregistered_installation_address', $ids);
        $this->assertNotContains('several_contracts_at_one_address', $ids);

        $this->assertContains('unclear_billing_address', $ids);
        $this->assertContains('missing_installation_address', $ids);
        $this->assertContains('unlocated_installation_address', $ids);
        $this->assertContains('duplicate_address', $ids);
    }

    /**
     * Every check has to answer to the filter, including the ones whose subject is the
     * address rather than the customer - those are the ones where the history is longest,
     * and a check that quietly ignored it would report today's work under a heading that
     * says otherwise.
     *
     * @return void
     * @link \App\Addresses\Check\AddressCheckRegistry::__construct()
     */
    public function testEveryCheckAnswersToTheFilter(): void
    {
        $running = new AddressCheckRegistry(true);
        $everything = new AddressCheckRegistry(false);

        foreach ($running->all() as $check) {
            $lifted = $everything->get($check->id());

            $this->assertNotNull($lifted);
            $this->assertLessThanOrEqual(
                $lifted->count(),
                $check->count(),
                sprintf('%s reports more when the filter is on than when it is lifted.', $check->id()),
            );

            // A check whose subject is what is running has no longer history to fall back
            // on, and says so rather than inventing a narrower question to answer instead.
            if ($check->hasAWiderReading()) {
                $this->assertNotSame(
                    $check->find()->sql(),
                    $lifted->find()->sql(),
                    sprintf('%s asks the same thing either way, so the filter passes it by.', $check->id()),
                );
            }
        }
    }

    /**
     * A customer's card carries these findings beside what is wrong with their contracts, so
     * every one of them has to be able to speak about one customer.
     *
     * @return void
     * @link \App\Addresses\Check\AddressCheckRegistry::__construct()
     */
    public function testEveryCheckCanSpeakAboutOneCustomer(): void
    {
        $anybody = new AddressCheckRegistry(customer_id: null);
        $theirs = new AddressCheckRegistry(customer_id: self::CUSTOMER_ID);

        $this->assertCount(count($anybody->all()), $theirs->all());

        foreach ($theirs->all() as $check) {
            $this->assertNotSame(
                $anybody->get($check->id())?->find()->sql(),
                $check->find()->sql(),
                sprintf('%s asks the same thing either way, so the customer passes it by.', $check->id()),
            );
        }
    }

    /**
     * Most of these are about a customer's address book rather than about one contract - the
     * same place recorded twice, an invoice with nowhere to go - and one contract cannot be
     * asked about that. Rather than answering about the whole file under a heading that says
     * otherwise, they leave themselves out.
     *
     * @return void
     * @link \App\Check\AbstractCheck::answersWhatWasAsked()
     */
    public function testOnlyTheChecksThatCanSpeakAboutOneContractAreOffered(): void
    {
        $ids = array_map(
            fn(AddressCheckInterface $check): string => $check->id(),
            (new AddressCheckRegistry(contract_id: self::CONTRACT_ID))->all(),
        );

        // the contracts are its subject, so one of them is a plain condition
        $this->assertSame(['missing_installation_address'], $ids);
    }

    /**
     * Asking twice gives the same instance, so that counting a check and then listing it
     * does not build it again.
     *
     * @return void
     * @link \App\Addresses\Check\AddressCheckRegistry::get()
     */
    public function testChecksAreKept(): void
    {
        $registry = new AddressCheckRegistry();

        $this->assertSame(
            $registry->get('unclear_billing_address'),
            $registry->get('unclear_billing_address'),
        );
        $this->assertNull($registry->get('no_such_check'));
    }
}
