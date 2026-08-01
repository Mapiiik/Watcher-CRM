<?php
declare(strict_types=1);

namespace App\Service\ConnectionHistory;

use App\Model\Enum\ConnectionHistorySource;
use Cake\I18n\DateTime;

/**
 * One continuous period during which an account stayed on a single connection
 * point, as observed by a source.
 *
 * A source hands these over read only, it never decides how they are stored.
 * Merging them with what is already recorded is the updater's business.
 */
class ConnectionInterval
{
    /**
     * @param \App\Model\Enum\ConnectionHistorySource $source The source that observed the interval.
     * @param string $sourceReference Identity of the account within the source.
     * @param \Cake\I18n\DateTime $firstSeen Start of the interval.
     * @param \Cake\I18n\DateTime $lastSeen End of the interval, equal to firstSeen when still open.
     * @param string|null $accountId Account the interval belongs to, in the source system.
     * @param string|null $customerId Customer the account belonged to.
     * @param string|null $contractId Contract the account belonged to.
     * @param string|null $stationId Station identifier, normally the MAC address of the client.
     * @param string|null $calledStationId Station identifier of the other end.
     * @param string|null $nasIpAddress IP address of the network access server.
     * @param string|null $nasPortId Port identifier on the network access server.
     * @param string|null $ipAddress IP address assigned for the duration of the interval.
     * @param string|null $ipv6Prefix IPv6 prefix assigned for the duration of the interval.
     * @param \Cake\I18n\DateTime|null $accountModified When the account was last edited in the source.
     */
    public function __construct(
        public readonly ConnectionHistorySource $source,
        public readonly string $sourceReference,
        public readonly DateTime $firstSeen,
        public readonly DateTime $lastSeen,
        public readonly ?string $accountId = null,
        public readonly ?string $customerId = null,
        public readonly ?string $contractId = null,
        public readonly ?string $stationId = null,
        public readonly ?string $calledStationId = null,
        public readonly ?string $nasIpAddress = null,
        public readonly ?string $nasPortId = null,
        public readonly ?string $ipAddress = null,
        public readonly ?string $ipv6Prefix = null,
        public readonly ?DateTime $accountModified = null,
    ) {
    }

    /**
     * The tuple that identifies the connection point.
     *
     * Two intervals sharing it describe the same place, so an interval may
     * extend a stored one only when these match. Everything outside the tuple
     * is descriptive and never splits an interval, which is why swapping a
     * routerboard during a service call leaves the history alone.
     *
     * The account placement is part of it: moving an account to another
     * contract has to close the running interval and open a new one, otherwise
     * the move stays invisible to anyone without access to the audit log.
     *
     * @return array<string, string|null>
     */
    public function pointKey(): array
    {
        return [
            'station_id' => $this->stationId,
            'nas_ip_address' => $this->nasIpAddress,
            'nas_port_id' => $this->nasPortId,
            'ip_address' => $this->ipAddress,
            'ipv6_prefix' => $this->ipv6Prefix,
            'customer_id' => $this->customerId,
            'contract_id' => $this->contractId,
        ];
    }

    /**
     * Returns a copy with the placement of the account replaced.
     *
     * Sources report the placement that holds now, the updater needs to be able
     * to restate an interval under the placement that held while it ran.
     *
     * @param string|null $customerId Customer to place the interval under.
     * @param string|null $contractId Contract to place the interval under.
     * @return self
     */
    public function withPlacement(?string $customerId, ?string $contractId): self
    {
        return new self(
            source: $this->source,
            sourceReference: $this->sourceReference,
            firstSeen: $this->firstSeen,
            lastSeen: $this->lastSeen,
            accountId: $this->accountId,
            customerId: $customerId,
            contractId: $contractId,
            stationId: $this->stationId,
            calledStationId: $this->calledStationId,
            nasIpAddress: $this->nasIpAddress,
            nasPortId: $this->nasPortId,
            ipAddress: $this->ipAddress,
            ipv6Prefix: $this->ipv6Prefix,
            accountModified: $this->accountModified,
        );
    }
}
