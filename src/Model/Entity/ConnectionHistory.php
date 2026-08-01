<?php
declare(strict_types=1);

namespace App\Model\Entity;

use App\Service\ConnectionHistory\ConnectionInterval;

/**
 * ConnectionHistory Entity
 *
 * One continuous period during which an account stayed on a single connection
 * point.
 *
 * @property \App\Model\Enum\ConnectionHistorySource $source
 * @property string $source_reference
 * @property string|null $account_id
 * @property string|null $customer_id
 * @property string|null $contract_id
 * @property string|null $station_id
 * @property string|null $called_station_id
 * @property string|null $nas_ip_address
 * @property string|null $nas_port_id
 * @property string|null $ip_address
 * @property string|null $ipv6_prefix
 * @property string|null $access_point_id
 * @property string|null $access_point_name
 * @property string|null $routeros_device_id
 * @property string|null $routeros_device_name
 * @property \Cake\I18n\DateTime $first_seen
 * @property \App\Model\Enum\FirstSeenSource $first_seen_source
 * @property \Cake\I18n\DateTime $last_seen
 * @property \Cake\I18n\DateTime|null $enriched
 *
 * @property bool $first_seen_exact
 *
 * @property \App\Model\Entity\Customer $customer
 * @property \App\Model\Entity\Contract $contract
 */
class ConnectionHistory extends AppEntity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * The tuple describing the connection point is written by the update only
     * and stays out of reach of forms. An operator tidying up a name must not
     * be able to quietly rewrite where somebody was connected.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'access_point_name' => true,
        'routeros_device_name' => true,
    ];

    /**
     * Virtual fields exposed on serialization.
     *
     * @var list<string>
     */
    protected array $_virtual = [
        'first_seen_exact',
    ];

    /**
     * Whether first_seen may be presented as an exact moment.
     *
     * @return bool
     */
    protected function _getFirstSeenExact(): bool
    {
        return $this->first_seen_source->isExact();
    }

    /**
     * The tuple that identifies the connection point.
     *
     * Kept in step with {@see \App\Service\ConnectionHistory\ConnectionInterval::pointKey()},
     * the two are compared against each other on every update.
     *
     * @return array<string, string|null>
     */
    public function pointKey(): array
    {
        return [
            'station_id' => $this->station_id,
            'nas_ip_address' => $this->nas_ip_address,
            'nas_port_id' => $this->nas_port_id,
            'ip_address' => $this->ip_address,
            'ipv6_prefix' => $this->ipv6_prefix,
            'customer_id' => $this->customer_id,
            'contract_id' => $this->contract_id,
        ];
    }

    /**
     * Whether this record describes the same connection point as an interval
     * reported by a source.
     *
     * @param \App\Service\ConnectionHistory\ConnectionInterval $interval Interval to compare with.
     * @return bool
     */
    public function isSamePointAs(ConnectionInterval $interval): bool
    {
        if ($this->source !== $interval->source || $this->source_reference !== $interval->sourceReference) {
            return false;
        }

        return $this->pointKey() === $interval->pointKey();
    }
}
