<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Entity\HistoricalConnection;
use App\Model\Enum\FirstSeenSource;
use App\Model\Enum\HistoricalConnectionSource;
use App\Model\Table\HistoricalConnectionsTable;
use App\Test\Traits\TableTestTrait;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;
use Override;

/**
 * App\Model\Table\HistoricalConnectionsTable Test Case
 *
 * The table records when an account was seen connected and until when, and the finders on it are
 * what every page asking "where has this been" reads through. The fixture is deliberately empty -
 * the updater test counts the rows in the whole table - so the records each test needs are written
 * by the test itself.
 */
class HistoricalConnectionsTableTest extends TestCase
{
    use TableTestTrait;

    /**
     * The customer the fixtures carry.
     *
     * @var string
     */
    private const CUSTOMER_ID = '403bab0e-52cd-4a8e-83f8-43c2457d0481';

    /**
     * Test subject
     *
     * @var \App\Model\Table\HistoricalConnectionsTable
     */
    protected $HistoricalConnections;

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
        'app.HistoricalConnections',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('HistoricalConnections')
            ? []
            : ['className' => HistoricalConnectionsTable::class];
        $this->HistoricalConnections = $this->getTableLocator()->get('HistoricalConnections', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    #[Override]
    protected function tearDown(): void
    {
        /** @phpstan-ignore unset.possiblyHookedProperty */
        unset($this->HistoricalConnections);

        parent::tearDown();
    }

    /**
     * Record an interval, filling in what the table insists on and leaving the caller to say what
     * the test is actually about.
     *
     * The fields are set rather than marshalled, because the entity accepts almost nothing by mass
     * assignment - an interval is written by the updater, never from a form, and this is the way it
     * writes one.
     *
     * @param array<string, mixed> $data What this interval is to say for itself.
     * @return \App\Model\Entity\HistoricalConnection
     */
    private function record(array $data = []): HistoricalConnection
    {
        $connection = $this->HistoricalConnections->newEmptyEntity();

        $firstSeen = $data['first_seen'] ?? new DateTime('2026-01-01 10:00:00');
        assert($firstSeen instanceof DateTime);

        // an interval that ended before it began is refused, so the end follows whichever
        // beginning the caller asked for
        $data += [
            'source' => HistoricalConnectionSource::Radius,
            'source_reference' => 'account-1',
            'first_seen' => $firstSeen,
            'first_seen_source' => FirstSeenSource::Session,
            'last_seen' => $firstSeen->addHours(2),
        ];

        foreach ($data as $field => $value) {
            $connection->set($field, $value);
        }

        /** @var \App\Model\Entity\HistoricalConnection $saved */
        $saved = $this->HistoricalConnections->saveOrFail($connection);

        return $saved;
    }

    /**
     * A new record with nothing filled in is refused - see the trait for why that is the question
     * worth asking here.
     *
     * @return void
     * @link \App\Model\Table\HistoricalConnectionsTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->assertEmptyRecordIsRefused($this->HistoricalConnections);
    }

    /**
     * A record that says nothing about where it came from is refused before the database has to.
     *
     * Both columns are `NOT NULL`, and the validator only forbade them being filled in empty - a
     * marshal leaving the key out altogether went through and the insert was what failed, which is
     * an error page rather than a word about a field. Nothing marshals this table today, the
     * updater assigns to the entity and never goes past the validator at all, so this is the guard
     * standing in for a form that does not exist yet.
     *
     * @return void
     * @link \App\Model\Table\HistoricalConnectionsTable::validationDefault()
     */
    public function testValidationDefaultRequiresWhereTheRecordCameFrom(): void
    {
        $firstSeen = new DateTime('2026-01-01 10:00:00');
        $complete = [
            'source' => HistoricalConnectionSource::Radius->value,
            'source_reference' => 'account-1',
            'first_seen' => $firstSeen,
            'first_seen_source' => FirstSeenSource::Session->value,
            'last_seen' => $firstSeen->addHours(2),
        ];

        $this->assertEmpty($this->HistoricalConnections->newEntity($complete)->getErrors());

        foreach (['source', 'first_seen_source'] as $field) {
            $without = $complete;
            unset($without[$field]);

            $this->assertArrayHasKey(
                $field,
                $this->HistoricalConnections->newEntity($without)->getErrors(),
                $field . ' is not null in the database and has to be asked for before it gets there',
            );
        }
    }

    /**
     * The rules refuse a record whose references point nowhere - see the trait for why that is the
     * question worth asking here. The record it repoints is written here, because the fixture holds
     * none.
     *
     * @return void
     * @link \App\Model\Table\HistoricalConnectionsTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $this->record(['customer_id' => self::CUSTOMER_ID]);

        $this->assertDanglingReferencesAreRefused($this->HistoricalConnections);
    }

    /**
     * The latest interval for an account is the one an incoming session may be able to extend, so
     * what comes back has to be the newest rather than whichever the database hands over first.
     *
     * @return void
     * @link \App\Model\Table\HistoricalConnectionsTable::getLatestForAccount()
     */
    public function testGetLatestForAccountAnswersWithTheNewest(): void
    {
        $this->record(['first_seen' => new DateTime('2026-01-01 10:00:00')]);
        $this->record(['first_seen' => new DateTime('2026-03-01 10:00:00')]);
        $this->record(['first_seen' => new DateTime('2026-02-01 10:00:00')]);

        $latest = $this->HistoricalConnections->getLatestForAccount(
            HistoricalConnectionSource::Radius,
            'account-1',
        );

        $this->assertNotNull($latest);
        $this->assertSame('2026-03-01 10:00:00', $latest->first_seen->format('Y-m-d H:i:s'));
    }

    /**
     * Another account's history is not this one's. The reference is what tells two accounts apart
     * within a source, and an interval extended onto the wrong one would rewrite somebody else's
     * history.
     *
     * @return void
     * @link \App\Model\Table\HistoricalConnectionsTable::getLatestForAccount()
     */
    public function testGetLatestForAccountIgnoresAnotherAccount(): void
    {
        $this->record(['source_reference' => 'account-2']);

        $this->assertNull($this->HistoricalConnections->getLatestForAccount(
            HistoricalConnectionSource::Radius,
            'account-1',
        ));
    }

    /**
     * An account nothing has been recorded for has no latest interval, which is what tells the
     * updater it is opening the first one rather than extending anything.
     *
     * @return void
     * @link \App\Model\Table\HistoricalConnectionsTable::getLatestForAccount()
     */
    public function testGetLatestForAccountAnswersWithNullWhereNothingWasRecorded(): void
    {
        $this->assertNull($this->HistoricalConnections->getLatestForAccount(
            HistoricalConnectionSource::Radius,
            'account-1',
        ));
    }

    /**
     * A customer's history is what is listed on their page, newest first - the recent connections
     * are what anybody looking at it came for.
     *
     * @return void
     * @link \App\Model\Table\HistoricalConnectionsTable::findForCustomer()
     */
    public function testFindForCustomerListsTheirsNewestFirst(): void
    {
        $this->record([
            'customer_id' => self::CUSTOMER_ID,
            'first_seen' => new DateTime('2026-01-01 10:00:00'),
        ]);
        $this->record([
            'customer_id' => self::CUSTOMER_ID,
            'first_seen' => new DateTime('2026-03-01 10:00:00'),
        ]);
        // somebody else's, which must not be listed among theirs
        $this->record(['customer_id' => null, 'source_reference' => 'account-2']);

        /** @var list<\App\Model\Entity\HistoricalConnection> $found */
        $found = $this->HistoricalConnections
            ->find('forCustomer', customerId: self::CUSTOMER_ID)
            ->toArray();

        $this->assertCount(2, $found);
        $this->assertSame('2026-03-01 10:00:00', $found[0]->first_seen->format('Y-m-d H:i:s'));
        $this->assertSame('2026-01-01 10:00:00', $found[1]->first_seen->format('Y-m-d H:i:s'));
    }

    /**
     * The same for a contract, which is the narrower question a customer's page asks per service.
     *
     * @return void
     * @link \App\Model\Table\HistoricalConnectionsTable::findForContract()
     */
    public function testFindForContractListsItsOwnNewestFirst(): void
    {
        /** @var \App\Model\Entity\Contract $contract */
        $contract = $this->getTableLocator()->get('Contracts')->find()->firstOrFail();
        $contractId = $contract->id;

        $this->record(['contract_id' => $contractId, 'first_seen' => new DateTime('2026-01-01 10:00:00')]);
        $this->record(['contract_id' => $contractId, 'first_seen' => new DateTime('2026-03-01 10:00:00')]);
        $this->record(['contract_id' => null, 'source_reference' => 'account-2']);

        /** @var list<\App\Model\Entity\HistoricalConnection> $found */
        $found = $this->HistoricalConnections
            ->find('forContract', contractId: $contractId)
            ->toArray();

        $this->assertCount(2, $found);
        $this->assertSame('2026-03-01 10:00:00', $found[0]->first_seen->format('Y-m-d H:i:s'));
    }

    /**
     * A station is looked up across accounts rather than within one - the same station turning up
     * under two of them is the thing worth noticing, so the finder must not narrow it down.
     *
     * @return void
     * @link \App\Model\Table\HistoricalConnectionsTable::findForStation()
     */
    public function testFindForStationReachesAcrossAccounts(): void
    {
        $this->record(['source_reference' => 'account-1', 'station_id' => 'AA:BB:CC:DD:EE:FF']);
        $this->record(['source_reference' => 'account-2', 'station_id' => 'AA:BB:CC:DD:EE:FF']);
        $this->record(['source_reference' => 'account-3', 'station_id' => '11:22:33:44:55:66']);

        /** @var list<\App\Model\Entity\HistoricalConnection> $found */
        $found = $this->HistoricalConnections
            ->find('forStation', stationId: 'AA:BB:CC:DD:EE:FF')
            ->toArray();

        $this->assertCount(2, $found);
        $this->assertSame(
            ['account-1', 'account-2'],
            array_map(fn(HistoricalConnection $c): string => $c->source_reference, $found),
        );
    }
}
