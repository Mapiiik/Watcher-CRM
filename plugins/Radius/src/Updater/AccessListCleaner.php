<?php
declare(strict_types=1);

namespace Radius\Updater;

use App\Agent\ApiClient as AgentApiClient;
use App\Messages\Messages;
use App\NMS\ApiClient as NMSApiClient;
use App\NMS\Dto\RouterosDevice;
use Cake\ORM\Locator\LocatorAwareTrait;
use Radius\Model\Entity\Account;
use Radius\Model\Entity\Radacct;

/**
 * Removes the MAC address of an account from the access lists on every access point of the site it
 * connected to last, so that the station cannot connect to another sector instead.
 *
 * The site is the access point Watcher NMS keeps the board of the last session under. The access
 * points are changed by the Watcher Agent, which sits in their network.
 */
class AccessListCleaner
{
    use LocatorAwareTrait;

    /**
     * How many days ago an agent must last have read a board of the site for it to be cleaned too.
     * A board not read for longer has most likely been replaced or taken down. Watcher NMS takes
     * 1, 7, 14, 28, 56 or 365.
     */
    public const MAXIMUM_DEVICE_AGE = 14;

    /**
     * Messages
     */
    public Messages $Messages;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->Messages = new Messages();
    }

    /**
     * Remove the MAC address of the last session of the account from the access points of its site.
     *
     * @param \Radius\Model\Entity\Account $account RADIUS account entity.
     * @return bool Returns true if the access point of the last session has been reached.
     */
    public function removeMacAddress(Account $account): bool
    {
        $session = $this->fetchTable('Radius.Radacct')
            ->find()
            ->where(['Radacct.username' => $account->username])
            ->orderBy(['Radacct.acctstarttime' => 'DESC'])
            ->first();

        if (!$session instanceof Radacct) {
            $this->Messages->warning(__d(
                'radius',
                'No RADIUS session for {0} found.',
                $account->username,
            ));

            return false;
        }

        if ($session->callingstationid === null || $session->callingstationid === '') {
            $this->Messages->warning(__d(
                'radius',
                'The last RADIUS session of {0} carries no MAC address.',
                $account->username,
            ));

            return false;
        }

        $successor = $this->activeAccountConnectedSince($session);
        if ($successor !== null) {
            $this->Messages->warning(__d(
                'radius',
                'The active account {1} has connected from the MAC address {0} since,'
                    . ' so it has been left on the access points.',
                $session->callingstationid,
                $successor,
            ));

            return false;
        }

        [$hosts, $site] = $this->accessPointsOfTheSite($session->nasipaddress);

        return $this->clean($hosts, $session->callingstationid, $site);
    }

    /**
     * Have the agent take the station off every access point and tell the operator what it did.
     *
     * @param non-empty-list<string> $hosts The access points, the one of the session first.
     * @param string $macAddress The station.
     * @param string|null $site The name of the site, where Watcher NMS named one.
     * @return bool Whether the access point of the session has been reached.
     */
    private function clean(array $hosts, string $macAddress, ?string $site): bool
    {
        $reached = [];
        $result = '';
        foreach ($hosts as $host) {
            $answer = AgentApiClient::removeFromAccessList($host, $macAddress);

            if (!$answer->ok()) {
                $failure = $answer->failure ?? __d('radius', 'Watcher Agent is not configured.');

                if ($host !== $hosts[0]) {
                    $this->Messages->warning(__d(
                        'radius',
                        'Problem connecting to the access point {0}: {1}',
                        $host,
                        $failure,
                    ));
                    continue;
                }

                $this->Messages->error(__d('radius', 'Problem connecting to an access point: {0}', $failure));
                // without an agent, there is no one to ask about the rest either
                if (!$answer->asked) {
                    return false;
                }
                continue;
            }

            $reached[] = $host;
            foreach ($answer->data->removed as $entry) {
                $result .= __d(
                    'radius',
                    'Removed MAC address entry {0} on interface {1} from router {2}.',
                    $entry->macAddress,
                    $entry->interface,
                    $host,
                ) . PHP_EOL;
            }
        }

        if ($reached === []) {
            return false;
        }

        $count = count($reached);
        $this->Messages->success(
            '<strong>' . __dn('radius', 'Access point updated.', 'Access points updated.', $count) . '</strong><br>'
                . ($site !== null ? h(__dn(
                    'radius',
                    '{0} access point of the site {1} checked.',
                    '{0} access points of the site {1} checked.',
                    $count,
                    $count,
                    $site,
                )) . '<br>' : '')
                . ($result ? nl2br(h($result)) : __d('radius', 'Nothing has changed.')),
            ['escape' => false],
        );

        return in_array($hosts[0], $reached, true);
    }

    /**
     * The active account, other than the one of the session, that has connected from the same
     * station after it. A unit handed over with the contract is still in use, and removing it
     * would cut off the new customer.
     *
     * @param \Radius\Model\Entity\Radacct $session The last session of the account being cleaned up.
     * @return string|null The username of that account.
     */
    private function activeAccountConnectedSince(Radacct $session): ?string
    {
        $later = $this->fetchTable('Radius.Radacct')
            ->find()
            ->contain(['Accounts'])
            ->where([
                'Radacct.callingstationid' => $session->callingstationid,
                'Radacct.username !=' => (string)$session->username,
                'Radacct.radacctid >' => $session->radacctid,
                'Accounts.active' => true,
            ])
            ->orderBy(['Radacct.radacctid' => 'DESC'])
            ->first();

        return $later instanceof Radacct ? $later->username : null;
    }

    /**
     * The access points to clean: the one of the session first, then the others of its site.
     *
     * Where Watcher NMS is not there to ask, only the access point of the session is cleaned,
     * as it was before there were sites. Where it was asked and did not answer, the operator is
     * told so.
     *
     * @param string $nas The address of the access point of the session.
     * @return array{0: non-empty-list<string>, 1: string|null} The addresses and the name of the site.
     */
    private function accessPointsOfTheSite(string $nas): array
    {
        $boards = NMSApiClient::getRouterosDevicesForIp($nas);
        if (!$boards->ok()) {
            $this->warnAboutTheSite($boards->unanswered(), $nas);

            return [[$nas], null];
        }

        /** @var \App\NMS\Dto\RouterosDevice|null $board */
        $board = $boards->data
            ->filter(fn(RouterosDevice $device): bool => $device->accessPointId !== null)
            ->first();
        if ($board === null) {
            $this->Messages->warning(__d(
                'radius',
                'Watcher NMS does not place the access point {0} on any site, so only that one is cleaned.',
                $nas,
            ));

            return [[$nas], null];
        }
        $accessPointId = (string)$board->accessPointId;
        $site = $board->accessPoint?->name;

        $devices = NMSApiClient::searchRouterosDevices([
            'access_point_id' => $accessPointId,
            'maximum_age' => self::MAXIMUM_DEVICE_AGE,
        ]);
        if (!$devices->ok()) {
            $this->warnAboutTheSite($devices->unanswered(), $nas);

            return [[$nas], $site];
        }

        // the boards answering at the address of the session are already on the list, under it
        $known = $boards->data
            ->extract(fn(RouterosDevice $device): string => $device->id)
            ->toList();

        $hosts = [$nas];
        foreach ($devices->data as $device) {
            // checked here as well, as a Watcher NMS that does not know the filter answers with all of them
            if ($device->accessPointId !== $accessPointId || in_array($device->id, $known, true)) {
                continue;
            }
            if ($device->ipAddress === null || in_array($device->ipAddress, $hosts, true)) {
                continue;
            }
            $hosts[] = $device->ipAddress;
        }

        return [$hosts, $site];
    }

    /**
     * Tell the operator the site could not be read, where Watcher NMS was asked.
     *
     * @param bool $unanswered Whether Watcher NMS was asked and did not answer.
     * @param string $nas The address of the access point of the session.
     * @return void
     */
    private function warnAboutTheSite(bool $unanswered, string $nas): void
    {
        if ($unanswered) {
            $this->Messages->warning(__d(
                'radius',
                'Watcher NMS did not answer, so only the access point {0} is cleaned.',
                $nas,
            ));
        }
    }
}
