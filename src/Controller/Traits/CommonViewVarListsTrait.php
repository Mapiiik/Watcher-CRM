<?php
declare(strict_types=1);

namespace App\Controller\Traits;

use App\Model\Table\QueuesTable;
use App\Model\Table\ServiceTypesTable;
use App\NMS\ApiClient as NMSApiClient;
use Cake\Collection\CollectionInterface;

/**
 * @psalm-require-extends \Cake\Controller\Controller
 * @method \Cake\Http\ServerRequest getRequest()
 */
trait CommonViewVarListsTrait
{
    /**
     * Set the `serviceTypes` view var (alphabetical list).
     */
    private function setServiceTypesViewVarList(): void
    {
        $this->set(
            'serviceTypes',
            $this->fetchTable(ServiceTypesTable::class)
                ->find('list')
                ->orderBy(['name' => 'ASC'])
                ->all(),
        );
    }

    /**
     * Set the `ctoCategories` view var (distinct, non-null, alphabetical).
     */
    private function setCtoCategoriesViewVarList(): void
    {
        $this->set(
            'ctoCategories',
            $this->fetchTable(QueuesTable::class)
                ->find(
                    'list',
                    group: 'cto_category',
                    keyField: 'cto_category',
                    valueField: 'cto_category',
                )
                ->orderBy(['cto_category' => 'ASC'])
                ->whereNotNull('cto_category'),
        );
    }

    /**
     * Set the `accessPoints` view var from the NMS API.
     *
     * Falls back to an empty list + Flash warning when NMS is unreachable.
     */
    protected function setAccessPointsViewVarList(bool $onlyActive = false): void
    {
        // load access points from NMS if possible (only active)
        $accessPoints = NMSApiClient::getAccessPointsList(onlyActive: $onlyActive);

        if ($accessPoints === null) {
            $this->Flash->warning(__('The access points list could not be loaded. Please, try again.'));
            $accessPoints = [];
        }

        $this->set('accessPoints', $accessPoints);
    }

    /**
     * Set the `accessPoints` view var from the NMS API.
     *
     * Falls back to an empty list + Flash warning when NMS is unreachable.
     */
    protected function setAccessPointsViewVarListWithRanges(bool $onlyActive = false): void
    {
        // load access points with ranges from NMS if possible
        $accessPoints = NMSApiClient::getAccessPoints();
        $ipAddressRanges = NMSApiClient::searchIpAddressRanges([]);

        if ($accessPoints instanceof CollectionInterface && $ipAddressRanges instanceof CollectionInterface) {
            if ($onlyActive) {
                $accessPoints = $accessPoints->match(['archived' => null]);
            }
            $accessPoints = $accessPoints
                ->map(
                    function (array $accessPoint) use ($ipAddressRanges): array {
                        $text = $accessPoint['name']
                            . ($accessPoint['archived'] === null ? '' : ' (' . __('archived') . ')');

                        $ranges = $ipAddressRanges
                            ->match(['access_point_id' => $accessPoint['id']])
                            ->sortBy('name', SORT_ASC, SORT_NATURAL);

                        if (!$ranges->isEmpty()) {
                            $rangeNames = $ranges->extract('name');
                            $text .= '     [' . implode(', ', $rangeNames->toArray()) . ']';
                        }

                        return [
                            'value' => $accessPoint['id'],
                            'text' => $text,
                            'style' => $accessPoint['archived'] === null ? null : 'color: darkgray;',
                        ];
                    },
                )
                ->sortBy('text', SORT_ASC, SORT_NATURAL)
                ->toArray();
        } else {
            $this->Flash->warning(__('The access points list could not be loaded. Please, try again.'));
            $accessPoints = [];
        }

        $this->set('accessPoints', $accessPoints);
    }
}
