<?php
declare(strict_types=1);

namespace App\Dashboard\Card;

use App\NMS\ApiClient as NMSApiClient;
use App\NMS\Links;
use Cake\Collection\CollectionInterface;
use Dashboard\Card\AbstractDashboardCard;
use Override;

/**
 * Masts of the network the distributor is about to cut the power to, and who is on them.
 *
 * The same card the network management system draws for itself, asked here for a different
 * reason: over there it is work to be arranged, here it is the telephone about to ring. So the
 * number of connections below the mast leads rather than follows - it is the one thing that tells
 * an outage worth warning people about from one nobody will notice.
 *
 * Offered to every role, since anyone in the office may be the one who picks up.
 *
 * The reading is the other application's whole and entire. Which masts are fed from which is not
 * written down here, so the count has to arrive already made.
 */
class PowerOutagesCard extends AbstractDashboardCard
{
    /**
     * @return string
     */
    #[Override]
    public function id(): string
    {
        return 'power_outages';
    }

    /**
     * @return string
     */
    #[Override]
    public function title(): string
    {
        return __('Planned Power Outages');
    }

    /**
     * Asked over the network, so never with the page.
     *
     * @return bool
     */
    #[Override]
    public function deferred(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function data(): array
    {
        $answer = NMSApiClient::getPowerOutages();

        /** @var \Cake\Collection\CollectionInterface<int, \App\NMS\Dto\PowerOutage>|null $outages */
        $outages = $answer->ok() ? $answer->data : null;

        /** @var list<\App\NMS\Dto\PowerOutage> $all */
        $all = $outages instanceof CollectionInterface ? $outages->toList() : [];

        return [
            'outages' => array_slice($all, 0, $this->maximumRows()),
            'total' => count($all),
            // The reading itself travels with them: a card that could not be filled has to say so,
            // or an outage reads as a quiet afternoon.
            'answer' => $answer,
            'url' => Links::plannedPowerOutages(),
        ];
    }
}
