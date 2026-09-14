<?php
declare(strict_types=1);

namespace App\Controller\Traits;

use App\Model\Enum\AddressType;
use App\Model\Table\AddressesTable;
use App\Model\Table\ContractStatesTable;
use App\Model\Table\QueuesTable;
use App\Model\Table\ServiceTypesTable;
use App\NMS\ApiClient as NMSApiClient;
use App\NMS\Dto\AccessPoint;
use App\NMS\Dto\IpAddressRange;

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
     * Set the `contractStates` view var (alphabetical list).
     */
    private function setContractStatesViewVarList(): void
    {
        $this->set(
            'contractStates',
            $this->fetchTable(ContractStatesTable::class)
                ->find('list', order: [
                    'name',
                ]),
        );
    }

    /**
     * Set the `installationCities` view var.
     *
     * Of the addresses a contract actually points at rather than of all of them: there are
     * seven times as many towns on file as there are towns anybody is served in, and a
     * filter offering one of the rest can only ever empty the page.
     *
     * The type is asked for as well, because the association the filter runs through asks
     * for it. `Addresses.hasMany('Contracts')` carries no such condition, so without this an
     * address that has since stopped being an installation address would be offered here and
     * match nothing there.
     */
    private function setInstallationCitiesViewVarList(): void
    {
        /** @var list<string> $cities */
        $cities = $this->fetchTable(AddressesTable::class)
            ->find()
            ->select(['city' => 'Addresses.city'])
            ->distinct()
            ->innerJoinWith('Contracts')
            ->where(['Addresses.type' => AddressType::Installation])
            ->whereNotNull('Addresses.city')
            ->where(['Addresses.city !=' => ''])
            ->orderBy(['Addresses.city' => 'ASC'])
            ->disableHydration()
            ->all()
            ->extract('city')
            ->toList();

        // Keyed by itself: what the form sends back is the city, there being no id to send.
        $this->set('installationCities', array_combine($cities, $cities));
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

        if (!$accessPoints->ok()) {
            $this->Flash->warning(__('The access points list could not be loaded. Please, try again.'));
        }

        $this->set('accessPoints', $accessPoints->or([]));
    }

    /**
     * Set the `accessPoints` view var from the NMS API.
     *
     * Falls back to an empty list + Flash warning when NMS is unreachable.
     */
    protected function setAccessPointsViewVarListWithRanges(bool $onlyActive = false): void
    {
        // load access points with ranges from NMS if possible
        $accessPointsAnswer = NMSApiClient::getAccessPoints();
        $rangesAnswer = NMSApiClient::searchIpAddressRanges([]);

        if (!$accessPointsAnswer->ok() || !$rangesAnswer->ok()) {
            $this->Flash->warning(__('The access points list could not be loaded. Please, try again.'));
            $this->set('accessPoints', []);

            return;
        }

        /** @var \Cake\Collection\CollectionInterface<int, \App\NMS\Dto\IpAddressRange> $ranges */
        $ranges = $rangesAnswer->data;

        $this->set('accessPoints', $accessPointsAnswer->data
            ->filter(fn(AccessPoint $accessPoint): bool => !$onlyActive || !$accessPoint->isArchived())
            ->map(function (AccessPoint $accessPoint) use ($ranges): array {
                $text = (string)$accessPoint->name
                    . ($accessPoint->isArchived() ? ' (' . __('archived') . ')' : '');

                /** @var list<string> $rangeNames */
                $rangeNames = $ranges
                    ->filter(fn(IpAddressRange $range): bool => $range->accessPointId === $accessPoint->id)
                    ->sortBy(fn(IpAddressRange $range): string => (string)$range->name, SORT_ASC, SORT_NATURAL)
                    ->map(fn(IpAddressRange $range): string => (string)$range->name)
                    ->toList();

                if ($rangeNames !== []) {
                    $text .= '     [' . implode(', ', $rangeNames) . ']';
                }

                return [
                    'value' => $accessPoint->id,
                    'text' => $text,
                    'style' => $accessPoint->isArchived() ? 'color: darkgray;' : null,
                ];
            })
            ->sortBy(fn(array $option): string => $option['text'], SORT_ASC, SORT_NATURAL)
            ->toList());
    }
}
