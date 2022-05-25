<?php
declare(strict_types=1);

namespace Radius\Model\Entity;

use App\ApiClient;
use Cake\Collection\CollectionInterface;
use Cake\ORM\Entity;

/**
 * Radacct Entity
 *
 * @property int $radacctid
 * @property string $acctsessionid
 * @property string $acctuniqueid
 * @property string|null $username
 * @property string|null $realm
 * @property string $nasipaddress
 * @property string|null $nasportid
 * @property string|null $nasporttype
 * @property \Cake\I18n\FrozenTime|null $acctstarttime
 * @property \Cake\I18n\FrozenTime|null $acctupdatetime
 * @property \Cake\I18n\FrozenTime|null $acctstoptime
 * @property int|null $acctinterval
 * @property int|null $acctsessiontime
 * @property string|null $acctauthentic
 * @property string|null $connectinfo_start
 * @property string|null $connectinfo_stop
 * @property int|null $acctinputoctets
 * @property int|null $acctoutputoctets
 * @property string|null $calledstationid
 * @property string|null $callingstationid
 * @property string|null $acctterminatecause
 * @property string|null $servicetype
 * @property string|null $framedprotocol
 * @property string|null $framedipaddress
 * @property string|null $framedipv6address
 * @property string|null $framedipv6prefix
 * @property string|null $framedinterfaceid
 * @property string|null $delegatedipv6prefix
 *
 * @property \Radius\Model\Entity\Account $account
 * @property \Cake\Collection\CollectionInterface|null $routeros_devices_for_nas
 */
class Radacct extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array<bool>
     */
    protected $_accessible = [
        'acctsessionid' => true,
        'acctuniqueid' => true,
        'username' => true,
        'realm' => true,
        'nasipaddress' => true,
        'nasportid' => true,
        'nasporttype' => true,
        'acctstarttime' => true,
        'acctupdatetime' => true,
        'acctstoptime' => true,
        'acctinterval' => true,
        'acctsessiontime' => true,
        'acctauthentic' => true,
        'connectinfo_start' => true,
        'connectinfo_stop' => true,
        'acctinputoctets' => true,
        'acctoutputoctets' => true,
        'calledstationid' => true,
        'callingstationid' => true,
        'acctterminatecause' => true,
        'servicetype' => true,
        'framedprotocol' => true,
        'framedipaddress' => true,
        'framedipv6address' => true,
        'framedipv6prefix' => true,
        'framedinterfaceid' => true,
        'delegatedipv6prefix' => true,
        'account' => true,
    ];

    /**
     * getter for RouterOS devices for NAS (try to load via ApiClient)
     *
     * @return \Cake\Collection\CollectionInterface|null
     */
    protected function _getRouterosDevicesForNas(): ?CollectionInterface
    {
        if (!empty($this->nasipaddress)) {
            $routerosDevices = ApiClient::getRouterosDevicesForIp($this->nasipaddress);

            if ($routerosDevices) {
                return $routerosDevices;
            }
        }

        return null;
    }
}
