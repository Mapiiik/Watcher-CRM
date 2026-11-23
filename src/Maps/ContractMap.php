<?php
declare(strict_types=1);

namespace App\Maps;

use App\Model\Entity\Contract;
use App\NMS\ApiClient as NMSApiClient;
use App\NMS\Dto\AccessPoint;
use App\NMS\Links;
use Cake\Collection\CollectionInterface;
use Cake\View\Helper\HtmlHelper;
use Maps\DrawnMap;
use Maps\Marker;
use Maps\Polyline;
use Maps\Position;

/**
 * A contract drawn as the two ends of the service and the line between them, and the way on from
 * the access point through the points it hangs off.
 *
 * Where the customer is comes from the installation address, which this application knows; where
 * the access point is comes from the network management system, which is the one that knows.
 * Either end may be missing - an address without coordinates, a contract with no access point
 * named - and what can be drawn is drawn regardless.
 *
 * The map opens on the two ends. The way on is drawn as well, but the map does not zoom out to it.
 */
final class ContractMap
{
    /**
     * A bubble is read while the map is still wanted, so what it points at opens beside it.
     *
     * @var array<string, string>
     */
    private const LINK_OPTIONS = ['target' => '_blank'];

    /**
     * What marks each end. The same colours the network map uses, so that a place looks the same
     * in both applications.
     */
    private const CUSTOMER_COLOR = '#65ba4a';
    private const ACCESS_POINT_COLOR = '#d02f37';

    /**
     * How the line between them is drawn.
     */
    private const LINE_COLOR = '#0066ff';
    private const LINE_WEIGHT = 2;
    private const LINE_OPACITY = 0.7;

    /**
     * The way on is dashed, so that the line of the service itself stands out of it.
     */
    private const ROUTE_DASH_ARRAY = '6 6';

    /**
     * @param \Cake\View\Helper\HtmlHelper $html What the bubbles are written with.
     */
    public function __construct(private readonly HtmlHelper $html)
    {
    }

    /**
     * Draws the contract.
     *
     * @param \App\Model\Entity\Contract $contract The contract to draw.
     * @return \App\Maps\DrawnConnection
     */
    public function draw(Contract $contract): DrawnConnection
    {
        $markers = [];
        $polylines = [];

        $customer = $this->customerPosition($contract);
        $accessPoint = $contract->access_point->data;
        $accessPointAt = $accessPoint === null ? null : $this->accessPointPosition($accessPoint);

        if ($customer !== null) {
            $markers['customer'] = new Marker(
                position: $customer,
                title: (string)$contract->number,
                color: self::CUSTOMER_COLOR,
                content: $this->customerBubble($contract),
                locked: true,
            );
        }

        if ($accessPoint !== null && $accessPointAt !== null) {
            $markers['access_point'] = new Marker(
                position: $accessPointAt,
                title: (string)$accessPoint->name,
                color: self::ACCESS_POINT_COLOR,
                content: $this->accessPointBubble($accessPoint),
                locked: true,
            );
        }

        // the way on is drawn around the contract, and without either end of it there is nothing
        if ($markers === []) {
            return new DrawnConnection(new DrawnMap([], []));
        }

        if ($accessPoint !== null) {
            $this->drawRoute($accessPoint, $accessPointAt, $markers, $polylines);
        }

        if ($customer === null || $accessPointAt === null) {
            return new DrawnConnection(new DrawnMap($markers, $polylines));
        }

        // added last, so that it is drawn over the way on where they cross
        $polylines['service'] = new Polyline($customer, $accessPointAt, [
            'color' => self::LINE_COLOR,
            'weight' => self::LINE_WEIGHT,
            'opacity' => self::LINE_OPACITY,
        ]);

        return new DrawnConnection(
            new DrawnMap($markers, $polylines),
            $customer->distanceTo($accessPointAt),
        );
    }

    /**
     * Draws the way on from the access point through the points it hangs off.
     *
     * A point without coordinates is passed over and the line goes on to the next one, as the way
     * still runs through it.
     *
     * @param \App\NMS\Dto\AccessPoint $accessPoint The point serving the contract.
     * @param \Maps\Position|null $from Where it stands, if anybody knows.
     * @param array<string, \Maps\Marker> $markers Markers gathered so far.
     * @param array<string, \Maps\Polyline> $polylines Lines gathered so far.
     * @return void
     */
    private function drawRoute(AccessPoint $accessPoint, ?Position $from, array &$markers, array &$polylines): void
    {
        foreach ($this->upstreamOf($accessPoint) as $upstream) {
            $at = $this->accessPointPosition($upstream);

            if ($at === null) {
                continue;
            }

            $markers['route_' . $upstream->id] = new Marker(
                position: $at,
                title: (string)$upstream->name,
                color: self::ACCESS_POINT_COLOR,
                content: $this->accessPointBubble($upstream),
                locked: true,
                focused: false,
            );

            if ($from !== null) {
                $polylines['route_' . $upstream->id] = new Polyline($from, $at, [
                    'color' => self::LINE_COLOR,
                    'weight' => self::LINE_WEIGHT,
                    'opacity' => self::LINE_OPACITY,
                    'dashArray' => self::ROUTE_DASH_ARRAY,
                ]);
            }

            $from = $at;
        }
    }

    /**
     * The points an access point hangs off, the nearest first, up to the one that hangs off nothing.
     *
     * Only every point together says which one each hangs off, so they are all read - from the
     * same reading the access point itself came out of. A point Watcher NMS does not know, or one
     * already walked, ends the way there.
     *
     * @param \App\NMS\Dto\AccessPoint $accessPoint Where to start.
     * @return list<\App\NMS\Dto\AccessPoint>
     */
    private function upstreamOf(AccessPoint $accessPoint): array
    {
        if ($accessPoint->parentAccessPointId === null) {
            return [];
        }

        /** @var array<string, \App\NMS\Dto\AccessPoint> $byId */
        $byId = NMSApiClient::getAccessPoints()
            ->map(fn(CollectionInterface $all): array => $all
                ->indexBy(fn(AccessPoint $one): string => $one->id)
                ->toArray())
            ->or([]);

        $walked = [$accessPoint->id => true];
        $upstream = [];
        $parentId = $accessPoint->parentAccessPointId;

        while ($parentId !== null && isset($byId[$parentId]) && !isset($walked[$parentId])) {
            $walked[$parentId] = true;
            $upstream[] = $byId[$parentId];
            $parentId = $byId[$parentId]->parentAccessPointId;
        }

        return $upstream;
    }

    /**
     * Where the service is installed.
     */
    private function customerPosition(Contract $contract): ?Position
    {
        $address = $contract->installation_address;

        if ($address === null || !is_numeric($address->gps_y) || !is_numeric($address->gps_x)) {
            return null;
        }

        return new Position((float)$address->gps_y, (float)$address->gps_x);
    }

    /**
     * Where an access point stands, as the network management system has it.
     */
    private function accessPointPosition(AccessPoint $accessPoint): ?Position
    {
        if ($accessPoint->latitude === null || $accessPoint->longitude === null) {
            return null;
        }

        return new Position($accessPoint->latitude, $accessPoint->longitude);
    }

    /**
     * What the customer's marker says.
     */
    private function customerBubble(Contract $contract): string
    {
        $lines = [
            '<strong>' . h($contract->number) . '</strong>',
            h($contract->customer?->name),
            h($contract->installation_address?->address),
        ];

        return implode('<br>', array_filter($lines));
    }

    /**
     * What an access point's marker says.
     *
     * The point itself is the other application's, so the name leads there rather than anywhere
     * here. Without an address for that application there is nothing to lead to, and the name is
     * written plainly.
     */
    private function accessPointBubble(AccessPoint $accessPoint): string
    {
        $name = (string)$accessPoint->name;
        $url = Links::accessPoint($accessPoint->id);

        if ($url === null) {
            return '<strong>' . h($name) . '</strong>';
        }

        return '<strong>' . $this->html->link($name, $url, self::LINK_OPTIONS) . '</strong>';
    }
}
