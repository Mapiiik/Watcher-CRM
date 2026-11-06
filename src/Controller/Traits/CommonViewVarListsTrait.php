<?php
declare(strict_types=1);

namespace App\Controller\Traits;

use App\Model\Enum\AccessTechnology;
use App\Model\Enum\AddressType;
use App\Model\Table\AddressesTable;
use App\Model\Table\ContractStatesTable;
use App\Model\Table\ServiceTypesTable;
use App\NMS\ApiClient as NMSApiClient;
use App\NMS\Dto\AccessPoint;
use App\NMS\Dto\IpAddressRange;
use Cake\ORM\Query\SelectQuery;

/**
 * @psalm-require-extends \Cake\Controller\Controller
 * @method \Cake\Http\ServerRequest getRequest()
 */
trait CommonViewVarListsTrait
{
    /**
     * The customers, as a form offers them.
     *
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface|array<mixed>>
     */
    protected function customersList(): SelectQuery
    {
        return $this->fetchTable('Customers')->find('list', order: [
            'company',
            'last_name',
            'first_name',
        ]);
    }

    /**
     * The contracts, as a form offers them.
     *
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface|array<mixed>>
     */
    protected function contractsList(): SelectQuery
    {
        return $this->fetchTable('Contracts')->find(
            'list',
            contain: [
                'InstallationAddresses',
                'ServiceTypes',
            ],
            order: [
                'Contracts.number',
            ],
        );
    }

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
     * Set the `accessTechnologies` view var, grouped by medium.
     */
    private function setAccessTechnologiesViewVarList(): void
    {
        $this->set('accessTechnologies', AccessTechnology::groupedOptions());
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
     * Falls back to a Flash warning when NMS is unreachable. What a record already holds stays on
     * offer whatever the list says, or saving the form would take it away without a word.
     *
     * @param bool $onlyActive Whether to leave out what has been archived.
     * @param string|null $keep The access point the record holds.
     * @return void
     */
    protected function setAccessPointsViewVarList(bool $onlyActive = false, ?string $keep = null): void
    {
        // load access points from NMS if possible (only active)
        $accessPoints = NMSApiClient::getAccessPointsList(onlyActive: $onlyActive);

        if (!$accessPoints->ok()) {
            $this->Flash->warning(__('The access points list could not be loaded. Please, try again.'));
        }

        $list = $accessPoints->or([]);
        if ($keep !== null && $keep !== '' && !isset($list[$keep])) {
            $all = NMSApiClient::getAccessPointsList()->or([]);
            if (isset($all[$keep]) || !$accessPoints->ok()) {
                $list[$keep] = $all[$keep] ?? $keep;
            }
        }

        $this->set('accessPoints', $list);
    }

    /**
     * Set the `accessPoints` view var from the NMS API.
     *
     * Falls back to a Flash warning when NMS is unreachable. What a record already holds stays on
     * offer whatever the list says, or saving the form would take it away without a word.
     *
     * @param bool $onlyActive Whether to leave out what has been archived.
     * @param string|null $keep The access point the record holds.
     * @return void
     */
    protected function setAccessPointsViewVarListWithRanges(bool $onlyActive = false, ?string $keep = null): void
    {
        // load access points with ranges from NMS if possible
        $accessPointsAnswer = NMSApiClient::getAccessPoints();
        $rangesAnswer = NMSApiClient::searchIpAddressRanges([]);

        if (!$accessPointsAnswer->ok() || !$rangesAnswer->ok()) {
            $this->Flash->warning(__('The access points list could not be loaded. Please, try again.'));
            $this->set('accessPoints', $keep === null || $keep === '' ? [] : [
                ['value' => $keep, 'text' => $keep, 'style' => null],
            ]);

            return;
        }

        /** @var \Cake\Collection\CollectionInterface<int, \App\NMS\Dto\IpAddressRange> $ranges */
        $ranges = $rangesAnswer->data;

        $this->set('accessPoints', $accessPointsAnswer->data
            ->filter(fn(AccessPoint $accessPoint): bool => !$onlyActive
                || !$accessPoint->isArchived()
                || $accessPoint->id === $keep)
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
