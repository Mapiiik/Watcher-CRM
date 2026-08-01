<?php
declare(strict_types=1);

namespace App\Service\ConnectionHistory;

use App\Messages\Messages;
use App\Model\Entity\ConnectionHistory;
use App\Model\Enum\FirstSeenSource;
use App\Model\Table\ConnectionHistoryTable;
use App\NMS\ApiClient as NMSApiClient;
use Cake\Collection\CollectionInterface;
use Cake\I18n\DateTime;
use Cake\Log\Log;
use Cake\ORM\Locator\LocatorAwareTrait;
use Throwable;

/**
 * Connection History Updater
 *
 * Reconciles the intervals a source can still derive with the history already
 * recorded, and fills in what the NMS knows about the place.
 *
 * The sources only ever hold a limited window of accounting data, which is the
 * whole reason this history exists. Anything already recorded is therefore
 * treated as the older and better evidence: the update extends and appends, it
 * never rewrites or inserts into the middle of what is there.
 */
class ConnectionHistoryUpdater
{
    use LocatorAwareTrait;

    /**
     * Messages
     */
    public Messages $Messages;

    /**
     * Connection History Table
     */
    protected ConnectionHistoryTable $ConnectionHistory;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->Messages = new Messages();

        /** @var \App\Model\Table\ConnectionHistoryTable $connectionHistory */
        $connectionHistory = $this->fetchTable('ConnectionHistory');
        $this->ConnectionHistory = $connectionHistory;
    }

    /**
     * Bring the history up to date from the given sources.
     *
     * @param array<\App\Service\ConnectionHistory\SourceInterface> $sources Sources to read.
     * @return \App\Service\ConnectionHistory\UpdateSummary
     */
    public function update(array $sources): UpdateSummary
    {
        $summary = new UpdateSummary();

        foreach ($sources as $source) {
            // a source that cannot be reached reports nothing, which must not be
            // mistaken for every account having gone away
            if (!$source->isAvailable()) {
                $summary->unavailableSources[] = $source->getSource()->value;

                $this->Messages->warning(__(
                    'The {0} connection history source could not be reached and was skipped.',
                    $source->getSource()->label(),
                ));

                continue;
            }

            $this->mergeSource($source, $summary);
        }

        $this->enrich($summary);

        return $summary;
    }

    /**
     * Walk one source, account by account.
     *
     * @param \App\Service\ConnectionHistory\SourceInterface $source Source to read.
     * @param \App\Service\ConnectionHistory\UpdateSummary $summary Running summary.
     * @return void
     */
    protected function mergeSource(SourceInterface $source, UpdateSummary $summary): void
    {
        $batch = [];
        $reference = null;

        // intervals arrive grouped by account and in order, so a change of
        // account marks the end of a batch
        foreach ($source->getIntervals() as $interval) {
            if ($reference !== null && $interval->sourceReference !== $reference) {
                $this->mergeAccount($batch, $summary);
                $batch = [];
            }

            $reference = $interval->sourceReference;
            $batch[] = $interval;
        }

        if ($batch !== []) {
            $this->mergeAccount($batch, $summary);
        }
    }

    /**
     * Reconcile the intervals of a single account with what is recorded.
     *
     * @param array<\App\Service\ConnectionHistory\ConnectionInterval> $intervals Intervals, in order.
     * @param \App\Service\ConnectionHistory\UpdateSummary $summary Running summary.
     * @return void
     */
    protected function mergeAccount(array $intervals, UpdateSummary $summary): void
    {
        $first = $intervals[0] ?? null;
        if ($first === null) {
            return;
        }

        $summary->accounts++;

        $latest = $this->ConnectionHistory->getLatestForAccount($first->source, $first->sourceReference);

        // nothing recorded yet, so the earliest interval only tells us the
        // account was already connected by then, not that it started there
        if ($latest === null) {
            foreach ($intervals as $index => $interval) {
                $latest = $this->open(
                    $interval,
                    $index === 0 ? FirstSeenSource::InitialLoad : FirstSeenSource::Session,
                    $summary,
                );
            }

            return;
        }

        // the account may have been moved to another customer or contract since
        // the last run, which the accounting data itself cannot show
        $latest = $this->applyPlacementChange($latest, $first, $summary);

        foreach ($intervals as $interval) {
            // already covered by what was recorded before, and the source can no
            // longer see far enough back to say anything new about it
            if ($interval->lastSeen <= $latest->first_seen) {
                $summary->skipped++;

                continue;
            }

            // the same place, so this is the running interval seen again, part
            // of it possibly through sessions that have since been purged
            if ($latest->isSamePointAs($interval)) {
                $this->extend($latest, $interval->lastSeen, $summary);

                continue;
            }

            $latest = $this->open($interval, FirstSeenSource::Session, $summary);
        }
    }

    /**
     * Close the running interval and open a new one when the account has been
     * moved to another customer or contract.
     *
     * The accounting data carries no trace of such a move, so it is detected by
     * comparing the placement recorded on the running interval with the one the
     * source reports now. The moment is taken from when the account was last
     * edited where that is plausible, and from the run itself otherwise.
     *
     * @param \App\Model\Entity\ConnectionHistory $latest Running interval.
     * @param \App\Service\ConnectionHistory\ConnectionInterval $interval Any interval of the account.
     * @param \App\Service\ConnectionHistory\UpdateSummary $summary Running summary.
     * @return \App\Model\Entity\ConnectionHistory The interval that is running after the change.
     */
    protected function applyPlacementChange(
        ConnectionHistory $latest,
        ConnectionInterval $interval,
        UpdateSummary $summary,
    ): ConnectionHistory {
        if (
            $latest->customer_id === $interval->customerId
            && $latest->contract_id === $interval->contractId
        ) {
            return $latest;
        }

        $now = DateTime::now();
        $boundary = $now;

        // the edit can only be the cause if it happened while the interval ran
        if (
            $interval->accountModified !== null
            && $interval->accountModified > $latest->first_seen
            && $interval->accountModified <= $now
        ) {
            $boundary = $interval->accountModified;
        }

        // the customer has not physically moved, so the new interval carries the
        // same place forward and only the placement changes
        $moved = new ConnectionInterval(
            source: $latest->source,
            sourceReference: $latest->source_reference,
            firstSeen: $boundary,
            lastSeen: $boundary,
            accountId: $interval->accountId,
            customerId: $interval->customerId,
            contractId: $interval->contractId,
            stationId: $latest->station_id,
            calledStationId: $latest->called_station_id,
            nasIpAddress: $latest->nas_ip_address,
            nasPortId: $latest->nas_port_id,
            ipAddress: $latest->ip_address,
            ipv6Prefix: $latest->ipv6_prefix,
            accountModified: $interval->accountModified,
        );

        $summary->openedByAccountChange++;

        return $this->open($moved, FirstSeenSource::AccountChange, $summary, $latest);
    }

    /**
     * Record a new interval.
     *
     * @param \App\Service\ConnectionHistory\ConnectionInterval $interval Interval to record.
     * @param \App\Model\Enum\FirstSeenSource $firstSeenSource How accurate its start is.
     * @param \App\Service\ConnectionHistory\UpdateSummary $summary Running summary.
     * @param \App\Model\Entity\ConnectionHistory|null $carryPlace Interval to copy the resolved place from.
     * @return \App\Model\Entity\ConnectionHistory
     */
    protected function open(
        ConnectionInterval $interval,
        FirstSeenSource $firstSeenSource,
        UpdateSummary $summary,
        ?ConnectionHistory $carryPlace = null,
    ): ConnectionHistory {
        $entity = $this->ConnectionHistory->newEmptyEntity();

        $entity->source = $interval->source;
        $entity->source_reference = $interval->sourceReference;
        $entity->account_id = $interval->accountId;
        $entity->customer_id = $interval->customerId;
        $entity->contract_id = $interval->contractId;
        $entity->station_id = $interval->stationId;
        $entity->called_station_id = $interval->calledStationId;
        $entity->nas_ip_address = $interval->nasIpAddress;
        $entity->nas_port_id = $interval->nasPortId;
        $entity->ip_address = $interval->ipAddress;
        $entity->ipv6_prefix = $interval->ipv6Prefix;
        $entity->first_seen = $interval->firstSeen;
        $entity->first_seen_source = $firstSeenSource;
        $entity->last_seen = $interval->lastSeen;

        // the place is unchanged, no need to ask the NMS about it again
        if ($carryPlace !== null && $carryPlace->nas_ip_address === $interval->nasIpAddress) {
            $entity->access_point_id = $carryPlace->access_point_id;
            $entity->access_point_name = $carryPlace->access_point_name;
            $entity->routeros_device_id = $carryPlace->routeros_device_id;
            $entity->routeros_device_name = $carryPlace->routeros_device_name;
            $entity->enriched = $carryPlace->enriched;
        }

        if ($this->ConnectionHistory->save($entity) === false) {
            $this->Messages->error(__(
                'The connection history interval for {0} starting {1} could not be saved.',
                $interval->sourceReference,
                $interval->firstSeen->nice(),
            ));

            Log::error(
                'Connection history interval could not be saved: '
                    . json_encode($entity->getErrors()),
            );

            return $entity;
        }

        $summary->opened++;

        return $entity;
    }

    /**
     * Push the end of a running interval forward.
     *
     * @param \App\Model\Entity\ConnectionHistory $entity Running interval.
     * @param \Cake\I18n\DateTime $lastSeen Newly observed end.
     * @param \App\Service\ConnectionHistory\UpdateSummary $summary Running summary.
     * @return void
     */
    protected function extend(ConnectionHistory $entity, DateTime $lastSeen, UpdateSummary $summary): void
    {
        if ($lastSeen <= $entity->last_seen) {
            $summary->skipped++;

            return;
        }

        $entity->last_seen = $lastSeen;

        if ($this->ConnectionHistory->save($entity) === false) {
            $this->Messages->error(__(
                'The connection history interval for {0} could not be extended.',
                $entity->source_reference,
            ));

            return;
        }

        $summary->extended++;
    }

    /**
     * Fill in what the NMS knows about the places behind the recorded intervals.
     *
     * Asked once per network access server rather than once per interval, and
     * kept as written at the time so a later rename or removal in the NMS does
     * not quietly rewrite history.
     *
     * @param \App\Service\ConnectionHistory\UpdateSummary $summary Running summary.
     * @return void
     */
    protected function enrich(UpdateSummary $summary): void
    {
        $pending = $this->ConnectionHistory->find()
            ->where([
                'enriched IS' => null,
                'nas_ip_address IS NOT' => null,
            ])
            ->all()
            ->groupBy('nas_ip_address');

        foreach ($pending as $nasIpAddress => $entities) {
            try {
                $place = $this->resolvePlace((string)$nasIpAddress);
            } catch (Throwable $e) {
                // leaving enriched unset means the next run tries again
                Log::warning(
                    'Could not resolve the place behind ' . $nasIpAddress . ': ' . $e->getMessage(),
                );

                $this->Messages->warning(__(
                    'The network management system could not be asked about {0}.',
                    $nasIpAddress,
                ));

                continue;
            }

            if ($place === null) {
                continue;
            }

            foreach ($entities as $entity) {
                $entity->access_point_id = $place['access_point_id'];
                $entity->access_point_name = $place['access_point_name'];
                $entity->routeros_device_id = $place['routeros_device_id'];
                $entity->routeros_device_name = $place['routeros_device_name'];
                $entity->enriched = DateTime::now();

                if ($this->ConnectionHistory->save($entity) !== false) {
                    $summary->enriched++;
                }
            }
        }
    }

    /**
     * Ask the NMS what stands behind a network access server address.
     *
     * The device search already carries its access point along, so one call
     * answers both halves of the question.
     *
     * @param string $nasIpAddress Address of the network access server.
     * @return array<string, string|null>|null Null when the NMS knows of nothing there.
     */
    protected function resolvePlace(string $nasIpAddress): ?array
    {
        $devices = NMSApiClient::getRouterosDevicesForIp($nasIpAddress);

        if (!$devices instanceof CollectionInterface) {
            return null;
        }

        $device = $devices->first();

        if (!is_array($device)) {
            return null;
        }

        return [
            'access_point_id' => $device['access_point']['id'] ?? null,
            'access_point_name' => $device['access_point']['name'] ?? null,
            'routeros_device_id' => $device['id'] ?? null,
            'routeros_device_name' => $device['name'] ?? null,
        ];
    }
}
