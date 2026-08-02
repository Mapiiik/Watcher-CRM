<?php
declare(strict_types=1);

namespace Radius\HistoricalConnections;

use App\Model\Enum\HistoricalConnectionSource;
use App\Service\HistoricalConnections\ConnectionInterval;
use App\Service\HistoricalConnections\SourceInterface;
use Cake\Database\Connection;
use Cake\Datasource\ConnectionManager;
use Cake\I18n\DateTime;
use Cake\Log\Log;
use Override;
use Throwable;

/**
 * Derives connection intervals from RADIUS accounting.
 *
 * Reads and nothing else. The accounting table is written by FreeRADIUS and
 * kept for about six months, so this class works out what it can from whatever
 * is left and leaves it to the updater to reconcile that with the history
 * already recorded on the application side.
 */
class RadiusSource implements SourceInterface
{
    /**
     * Splits accounting sessions into runs of consecutive sessions sharing a
     * connection point, and reduces each run to one interval.
     *
     * The point tuple deliberately excludes anything descriptive: the called
     * station and everything resolved later from the NMS may change without the
     * customer having moved anywhere.
     *
     * Sessions with no start time cannot be placed on the timeline and are
     * dropped, as are those not tied to an account.
     *
     * @var string
     */
    private const INTERVALS_SQL = <<<'SQL'
        WITH sessions AS (
            SELECT
                radacct.radacctid,
                radacct.username,
                accounts.id AS account_id,
                accounts.customer_id,
                accounts.contract_id,
                accounts.modified AS account_modified,
                radacct.acctstarttime AS started,
                COALESCE(
                    radacct.acctstoptime,
                    radacct.acctupdatetime,
                    radacct.acctstarttime
                ) AS ended,
                CASE
                    WHEN radacct.callingstationid ~* '^([0-9a-f]{2}[:-]){5}[0-9a-f]{2}$'
                        THEN lower(replace(radacct.callingstationid, '-', ':'))
                    ELSE radacct.callingstationid
                END AS station_id,
                radacct.calledstationid AS called_station_id,
                host(radacct.nasipaddress) AS nas_ip_address,
                radacct.nasportid AS nas_port_id,
                host(radacct.framedipaddress) AS ip_address,
                text(radacct.framedipv6prefix) AS ipv6_prefix
            FROM radacct
            INNER JOIN accounts ON accounts.username = radacct.username
            WHERE radacct.username IS NOT NULL
                AND radacct.acctstarttime IS NOT NULL
        ),
        marked AS (
            SELECT
                sessions.*,
                CASE WHEN (
                    station_id,
                    nas_ip_address,
                    nas_port_id,
                    ip_address,
                    ipv6_prefix
                ) IS DISTINCT FROM (
                    lag(station_id) OVER account_timeline,
                    lag(nas_ip_address) OVER account_timeline,
                    lag(nas_port_id) OVER account_timeline,
                    lag(ip_address) OVER account_timeline,
                    lag(ipv6_prefix) OVER account_timeline
                ) THEN 1 ELSE 0 END AS moved
            FROM sessions
            WINDOW account_timeline AS (PARTITION BY username ORDER BY started, radacctid)
        ),
        islands AS (
            SELECT
                marked.*,
                sum(moved) OVER (
                    PARTITION BY username
                    ORDER BY started, radacctid
                    ROWS UNBOUNDED PRECEDING
                ) AS island
            FROM marked
        )
        SELECT
            username,
            account_id,
            customer_id,
            contract_id,
            account_modified,
            station_id,
            nas_ip_address,
            nas_port_id,
            ip_address,
            ipv6_prefix,
            min(called_station_id) AS called_station_id,
            min(started) AS first_seen,
            max(ended) AS last_seen
        FROM islands
        GROUP BY
            username,
            island,
            account_id,
            customer_id,
            contract_id,
            account_modified,
            station_id,
            nas_ip_address,
            nas_port_id,
            ip_address,
            ipv6_prefix
        ORDER BY username, first_seen
        SQL;

    /**
     * @inheritDoc
     */
    #[Override]
    public function getSource(): HistoricalConnectionSource
    {
        return HistoricalConnectionSource::Radius;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function isAvailable(): bool
    {
        try {
            $this->getConnection()->execute('SELECT 1');

            return true;
        } catch (Throwable $e) {
            Log::error('RADIUS historical connections source unavailable: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function getIntervals(): iterable
    {
        $statement = $this->getConnection()->execute(self::INTERVALS_SQL);

        while (($row = $statement->fetch('assoc')) !== false) {
            yield new ConnectionInterval(
                source: $this->getSource(),
                sourceReference: (string)$row['username'],
                firstSeen: new DateTime($row['first_seen']),
                lastSeen: new DateTime($row['last_seen']),
                accountId: $row['account_id'] !== null ? (string)$row['account_id'] : null,
                customerId: $row['customer_id'] !== null ? (string)$row['customer_id'] : null,
                contractId: $row['contract_id'] !== null ? (string)$row['contract_id'] : null,
                stationId: $row['station_id'] !== null ? (string)$row['station_id'] : null,
                calledStationId: $row['called_station_id'] !== null ? (string)$row['called_station_id'] : null,
                nasIpAddress: $row['nas_ip_address'] !== null ? (string)$row['nas_ip_address'] : null,
                nasPortId: $row['nas_port_id'] !== null ? (string)$row['nas_port_id'] : null,
                ipAddress: $row['ip_address'] !== null ? (string)$row['ip_address'] : null,
                ipv6Prefix: $row['ipv6_prefix'] !== null ? (string)$row['ipv6_prefix'] : null,
                accountModified: $row['account_modified'] !== null
                    ? new DateTime($row['account_modified'])
                    : null,
            );
        }
    }

    /**
     * Returns the RADIUS connection.
     *
     * @return \Cake\Database\Connection
     */
    private function getConnection(): Connection
    {
        /** @var \Cake\Database\Connection $connection */
        $connection = ConnectionManager::get('radius');

        return $connection;
    }
}
