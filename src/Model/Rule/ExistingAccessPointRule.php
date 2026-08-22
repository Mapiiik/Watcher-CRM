<?php
declare(strict_types=1);

namespace App\Model\Rule;

use App\NMS\ApiClient as NMSApiClient;
use Cake\Datasource\EntityInterface;

/**
 * A record may only name a place of the network that Watcher NMS keeps.
 *
 * The places are the other application's, so this is not an `existsIn` over a table of ours but a
 * reading over the network - and a reading can come to nothing. Where nobody could say whether the
 * place is there, the record is taken: the list a form picks from comes from the same reading, so
 * an operator met with a system that is down cannot name a new place anyway, and the one already
 * written on a record is not to stop them from finishing it.
 */
final class ExistingAccessPointRule
{
    /**
     * @param \Cake\Datasource\EntityInterface $entity The record being saved.
     * @param array<string, mixed> $options Options the rules checker was given.
     * @return bool
     */
    public function __invoke(EntityInterface $entity, array $options): bool
    {
        $accessPointId = $entity->get('access_point_id');

        if (!is_string($accessPointId) || $accessPointId === '') {
            return true;
        }

        $answer = NMSApiClient::getAccessPoint($accessPointId);

        // a place nobody could look up is not a place that is not there
        return !$answer->ok() || $answer->data !== null;
    }
}
