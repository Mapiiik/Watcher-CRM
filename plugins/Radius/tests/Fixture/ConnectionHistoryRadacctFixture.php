<?php
declare(strict_types=1);

namespace Radius\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;
use Override;

/**
 * ConnectionHistoryRadacctFixture
 *
 * Accounting for one account that moves away and comes back, written the way a
 * network access server actually would: the same station reported in two MAC
 * notations, an interval with no IPv6 at all, and a session still running.
 */
class ConnectionHistoryRadacctFixture extends TestFixture
{
    /**
     * RADIUS keeps its own database, and so does the test suite.
     */
    public string $connection = 'test_radius';

    /**
     * Table alias, which is where the table name comes from
     */
    public string $tableAlias = 'Radius.Radacct';

    /**
     * Init method
     *
     * @return void
     */
    #[Override]
    public function init(): void
    {
        $this->records = [
            // first stay, the same station written two different ways
            $this->session(1, '10.0.0.1', 'ether1', '2026-01-01 10:00:00', '2026-01-01 12:00:00', [
                'callingstationid' => 'AA-BB-CC-DD-EE-FF',
                'calledstationid' => 'ap-one',
                'framedipaddress' => '192.0.2.10',
                'framedipv6prefix' => '2001:db8:1::/64',
            ]),
            $this->session(2, '10.0.0.1', 'ether1', '2026-01-02 10:00:00', '2026-01-02 12:00:00', [
                'callingstationid' => 'aa:bb:cc:dd:ee:ff',
                'calledstationid' => 'ap-one',
                'framedipaddress' => '192.0.2.10',
                'framedipv6prefix' => '2001:db8:1::/64',
            ]),
            // moved elsewhere, and without IPv6 there
            $this->session(3, '10.0.0.2', 'ether5', '2026-02-01 10:00:00', '2026-02-01 12:00:00', [
                'callingstationid' => 'aa:bb:cc:dd:ee:ff',
                'calledstationid' => 'ap-two',
                'framedipaddress' => '192.0.2.99',
                'framedipv6prefix' => null,
            ]),
            // back where it started
            $this->session(4, '10.0.0.1', 'ether1', '2026-03-01 10:00:00', '2026-03-01 12:00:00', [
                'callingstationid' => 'aa:bb:cc:dd:ee:ff',
                'calledstationid' => 'ap-one',
                'framedipaddress' => '192.0.2.10',
                'framedipv6prefix' => '2001:db8:1::/64',
            ]),
            // still connected, so no stop time yet
            $this->session(5, '10.0.0.1', 'ether1', '2026-03-02 10:00:00', null, [
                'callingstationid' => 'aa:bb:cc:dd:ee:ff',
                'calledstationid' => 'ap-one',
                'framedipaddress' => '192.0.2.10',
                'framedipv6prefix' => '2001:db8:1::/64',
                'acctupdatetime' => '2026-03-02 14:00:00',
            ]),
        ];
        parent::init();
    }

    /**
     * One accounting session.
     *
     * @param int $id Record identifier.
     * @param string $nasIpAddress Address of the network access server.
     * @param string $nasPortId Port on the network access server.
     * @param string $start When the session started.
     * @param string|null $stop When the session stopped, null while it runs.
     * @param array<string, mixed> $rest The rest of the record.
     * @return array<string, mixed>
     */
    private function session(
        int $id,
        string $nasIpAddress,
        string $nasPortId,
        string $start,
        ?string $stop,
        array $rest,
    ): array {
        return $rest + [
            'radacctid' => $id,
            'acctsessionid' => 'session-' . $id,
            'acctuniqueid' => 'unique-' . $id,
            'username' => 'tester',
            'nasipaddress' => $nasIpAddress,
            'nasportid' => $nasPortId,
            'acctstarttime' => $start,
            'acctstoptime' => $stop,
            'acctupdatetime' => null,
        ];
    }
}
