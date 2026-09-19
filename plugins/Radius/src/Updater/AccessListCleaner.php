<?php
declare(strict_types=1);

namespace Radius\Updater;

use App\Messages\Messages;
use Cake\Core\Configure;
use Cake\ORM\Locator\LocatorAwareTrait;
use Radius\Model\Entity\Account;
use Radius\Model\Entity\Radacct;
use RouterOS\Client;
use RouterOS\Exceptions\ClientException;
use RouterOS\Query;

/**
 * Removes the MAC address of an account from the access list on the access point it connected to last,
 * so that a station accepted earlier has to authenticate again.
 */
class AccessListCleaner
{
    use LocatorAwareTrait;

    /**
     * Access lists of the legacy wireless package and of the wifi package, the latter also under
     * its name before RouterOS 7.13. A router has only some of them.
     *
     * @var list<string>
     */
    private const ACCESS_LISTS = [
        '/interface/wireless/access-list',
        '/interface/wifi/access-list',
        '/interface/wifiwave2/access-list',
    ];

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

        try {
            $client = new Client([
                'host' => $session->nasipaddress,
                'user' => Configure::read('Radius.routerosUsername'),
                'pass' => Configure::read('Radius.routerosPassword'),
            ]);
        } catch (ClientException $e) {
            $this->Messages->error(__d(
                'radius',
                'Problem connecting to an access point: {0}',
                $e->getMessage(),
            ));

            return false;
        }

        $result = '';
        foreach (self::ACCESS_LISTS as $menu) {
            $query = new Query($menu . '/print');
            $query
                ->where('mac-address', $session->callingstationid)
                ->equal('.proplist', '.id,interface,mac-address');

            foreach ($client->query($query)->read() as $item) {
                // a menu the router lacks answers with an error instead of entries
                if (!isset($item['.id'])) {
                    continue;
                }

                $query = new Query($menu . '/remove');
                $query->equal('.id', $item['.id']);

                // an empty response means no error message
                if (empty($client->query($query)->read())) {
                    $result .= __d(
                        'radius',
                        'Removed MAC address entry {0} on interface {1} from router {2}.',
                        $item['mac-address'],
                        $item['interface'] ?? '',
                        $session->nasipaddress,
                    ) . PHP_EOL;
                }
            }
        }

        $this->Messages->success(
            '<strong>' . __d('radius', 'Access point updated.') . '</strong><br>'
                . ($result ? nl2br(h($result)) : __d('radius', 'Nothing has changed.')),
            ['escape' => false],
        );

        return true;
    }
}
