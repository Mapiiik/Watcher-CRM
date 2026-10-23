<?php
declare(strict_types=1);

namespace Radius\Updater;

use App\Agent\ApiClient as AgentApiClient;
use App\Messages\Messages;
use Cake\ORM\Locator\LocatorAwareTrait;
use Radius\Model\Entity\Account;
use Radius\Model\Entity\Radacct;

/**
 * Removes the MAC address of an account from the access list on the access point it connected to last,
 * so that a station accepted earlier has to authenticate again.
 *
 * The access point is changed by the Watcher Agent, which sits in its network.
 */
class AccessListCleaner
{
    use LocatorAwareTrait;

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
     * Remove the MAC address of the last session of the account from its access point.
     *
     * @param \Radius\Model\Entity\Account $account RADIUS account entity.
     * @return bool Returns true if the access point has been reached.
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

        $answer = AgentApiClient::removeFromAccessList($session->nasipaddress, $session->callingstationid);

        if (!$answer->ok()) {
            $this->Messages->error(__d(
                'radius',
                'Problem connecting to an access point: {0}',
                $answer->failure ?? __d('radius', 'Watcher Agent is not configured.'),
            ));

            return false;
        }

        $result = '';
        foreach ($answer->data->removed as $entry) {
            $result .= __d(
                'radius',
                'Removed MAC address entry {0} on interface {1} from router {2}.',
                $entry->macAddress,
                $entry->interface,
                $session->nasipaddress,
            ) . PHP_EOL;
        }

        $this->Messages->success(
            '<strong>' . __d('radius', 'Access point updated.') . '</strong><br>'
                . ($result ? nl2br(h($result)) : __d('radius', 'Nothing has changed.')),
            ['escape' => false],
        );

        return true;
    }
}
