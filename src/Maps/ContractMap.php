<?php
declare(strict_types=1);

namespace App\Maps;

use App\Model\Entity\Contract;
use App\NMS\Links;
use ArrayObject;
use Cake\View\Helper\HtmlHelper;
use Maps\DrawnMap;
use Maps\Marker;
use Maps\Polyline;
use Maps\Position;

/**
 * A contract drawn as the two ends of the service and the line between them.
 *
 * Where the customer is comes from the installation address, which this application knows; where
 * the access point is comes from the network management system, which is the one that knows.
 * Either end may be missing - an address without coordinates, a contract with no access point
 * named - and what can be drawn is drawn regardless.
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

        $customer = $this->customerPosition($contract);
        $accessPoint = $this->accessPointPosition($contract);

        if ($customer !== null) {
            $markers['customer'] = new Marker(
                position: $customer,
                title: (string)$contract->number,
                color: self::CUSTOMER_COLOR,
                content: $this->customerBubble($contract),
                locked: true,
            );
        }

        if ($accessPoint !== null) {
            $markers['access_point'] = new Marker(
                position: $accessPoint,
                title: (string)$contract->access_point_name,
                color: self::ACCESS_POINT_COLOR,
                content: $this->accessPointBubble($contract),
                locked: true,
            );
        }

        if ($customer === null || $accessPoint === null) {
            return new DrawnConnection(new DrawnMap($markers, []));
        }

        return new DrawnConnection(
            new DrawnMap($markers, [
                'service' => new Polyline($customer, $accessPoint, [
                    'color' => self::LINE_COLOR,
                    'weight' => self::LINE_WEIGHT,
                    'opacity' => self::LINE_OPACITY,
                ]),
            ]),
            $customer->distanceTo($accessPoint),
        );
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
     * Where the access point serving it stands, as the network management system has it.
     */
    private function accessPointPosition(Contract $contract): ?Position
    {
        $accessPoint = $contract->access_point;

        if (!$accessPoint instanceof ArrayObject) {
            return null;
        }

        $latitude = $accessPoint['gps_y'] ?? null;
        $longitude = $accessPoint['gps_x'] ?? null;

        if (!is_numeric($latitude) || !is_numeric($longitude)) {
            return null;
        }

        return new Position((float)$latitude, (float)$longitude);
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
     * What the access point's marker says.
     *
     * The point itself is the other application's, so the name leads there rather than anywhere
     * here. Without an address for that application there is nothing to lead to, and the name is
     * written plainly.
     */
    private function accessPointBubble(Contract $contract): string
    {
        $name = (string)$contract->access_point_name;
        $url = $contract->access_point_id === null ? null : Links::accessPoint($contract->access_point_id);

        if ($url === null) {
            return '<strong>' . h($name) . '</strong>';
        }

        return '<strong>' . $this->html->link($name, $url, self::LINK_OPTIONS) . '</strong>';
    }
}
