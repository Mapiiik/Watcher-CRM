<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Entity;

use App\Model\Entity\Address;
use App\Model\Entity\Contract;
use App\Model\Entity\Customer;
use App\Model\Entity\Task;
use App\Model\Entity\TaskType;
use App\Model\Enum\AddressType;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * App\Model\Entity\Task Test Case
 */
#[CoversClass(Task::class)]
class TaskTest extends TestCase
{
    /**
     * setUp method
     *
     * @return void
     */
    #[Override]
    public function setUp(): void
    {
        parent::setUp();

        Configure::write('Phones.stripPrefixForSummary', false);
        // a deployment names a region, a development machine names one in config/.env and CI names
        // none - the tests that read numbers against one say so themselves
        Configure::write('Phones.defaultRegion', 'CZ');
        // the customer number is built from the nid and the configured series
        Configure::write('Customers.series', 0);
    }

    /**
     * Test that every part of the summary is separated as expected.
     *
     * @return void
     * @link \App\Model\Entity\Task::_getSummaryText()
     */
    public function testSummaryTextJoinsAllParts(): void
    {
        $task = new Task([
            'subject' => 'Connection outage',
            'phone' => '+420 111 222 333',
            'customer' => new Customer([
                'company' => 'NETAIR, s.r.o.',
            ]),
            'contract' => new Contract([
                'number' => 'A458',
                'installation_address' => new Address([
                    'street' => 'Studentska',
                    'number' => '1903/14a',
                    'city' => 'Praha',
                ]),
            ]),
        ]);

        $this->assertSame(
            'Connection outage - NETAIR, s.r.o., Studentska 1903/14a, Praha, +420 111 222 333 (A458)',
            $task->summary_text,
        );
    }

    /**
     * Test that the task type stands in for a missing subject.
     *
     * @return void
     * @link \App\Model\Entity\Task::_getSummaryText()
     */
    public function testSummaryTextFallsBackToTaskType(): void
    {
        $task = new Task([
            'task_type' => new TaskType([
                'name' => 'Installation',
            ]),
            'customer' => new Customer([
                'nid' => 1234,
                'last_name' => 'Novak',
                'addresses' => [],
            ]),
        ]);

        $this->assertSame('Installation - Novak (1234)', $task->summary_text);
    }

    /**
     * Test that the customer's address stands in when the contract has none.
     *
     * @return void
     * @link \App\Model\Entity\Task::_getSummaryText()
     */
    public function testSummaryTextFallsBackToCustomerAddress(): void
    {
        $task = new Task([
            'subject' => 'Connection outage',
            'customer' => new Customer([
                'nid' => 1234,
                'last_name' => 'Novak',
                'addresses' => [
                    new Address([
                        'type' => AddressType::Installation,
                        'street' => 'Studentska',
                        'number' => '1903/14a',
                        'city' => 'Praha',
                    ]),
                ],
            ]),
        ]);

        $this->assertSame(
            'Connection outage - Novak, Studentska 1903/14a, Praha (1234)',
            $task->summary_text,
        );
    }

    /**
     * Test that an address located by coordinates alone leaves no empty slot.
     *
     * @return void
     * @link \App\Model\Entity\Task::_getSummaryText()
     */
    public function testSummaryTextSkipsAnAddressWithoutAStreetLine(): void
    {
        $task = new Task([
            'subject' => 'Connection outage',
            'customer' => new Customer([
                'nid' => 1234,
                'last_name' => 'Novak',
            ]),
            'contract' => new Contract([
                'number' => 'A458',
                'installation_address' => new Address([
                    'city' => 'Praha',
                ]),
            ]),
        ]);

        $this->assertSame('Connection outage - Novak, Praha (A458)', $task->summary_text);
    }

    /**
     * Test that a customer without any name does not leave a dangling separator.
     *
     * @return void
     * @link \App\Model\Entity\Task::_getSummaryText()
     */
    public function testSummaryTextSkipsANamelessCustomer(): void
    {
        $task = new Task([
            'subject' => 'Connection outage',
            'customer' => new Customer([
                'nid' => 1234,
                'addresses' => [],
            ]),
        ]);

        $this->assertSame('Connection outage (1234)', $task->summary_text);
    }

    /**
     * Test that the international prefix is stripped when configured to.
     *
     * @return void
     * @link \App\Model\Entity\Task::_getSummaryText()
     */
    public function testSummaryTextStripsThePhonePrefixWhenConfigured(): void
    {
        Configure::write('Phones.stripPrefixForSummary', true);

        $task = new Task([
            'subject' => 'Connection outage',
            'phone' => '+420 601 234 567,+420 602 345 678',
        ]);

        $this->assertSame('Connection outage, 601234567, 602345678', $task->summary_text);
    }

    /**
     * Test that a number stored without spaces survives the stripping.
     *
     * @return void
     * @link \App\Model\Entity\Task::_getSummaryText()
     */
    public function testSummaryTextKeepsAPhoneStoredWithoutSpaces(): void
    {
        Configure::write('Phones.stripPrefixForSummary', true);

        $task = new Task([
            'subject' => 'Connection outage',
            'phone' => '+420601234567',
        ]);

        $this->assertSame('Connection outage, 601234567', $task->summary_text);
    }

    /**
     * Test that a foreign number keeps the prefix it cannot be dialled without.
     *
     * @return void
     * @link \App\Model\Entity\Task::_getSummaryText()
     */
    public function testSummaryTextKeepsThePrefixOfAForeignPhone(): void
    {
        Configure::write('Phones.stripPrefixForSummary', true);

        $task = new Task([
            'subject' => 'Connection outage',
            'phone' => '+1 650-253-0000',
        ]);

        $this->assertSame('Connection outage, +1 650-253-0000', $task->summary_text);
    }
}
