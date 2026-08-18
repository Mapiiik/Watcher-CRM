<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\IpAddress $ipAddress
 */

use App\NMS\Links;
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('Edit IP Address'),
                ['action' => 'edit', $ipAddress->id],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->postLink(
                __('Delete IP Address'),
                ['action' => 'delete', $ipAddress->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $ipAddress->id), 'class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->link(__('List IP Addresses'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->AuthLink->link(__('New IP Address'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="ipAddresses view content">
            <h3><?= h($ipAddress->ip_address) ?></h3>
            <div class="row">
                <div class="column">
                    <table>
                        <tr>
                            <th><?= __('Customer') ?></th>
                            <td><?= $ipAddress->customer !== null ? $this->Html->link(
                                $ipAddress->customer->name ?? '(' . $ipAddress->customer->id . ')',
                                ['controller' => 'Customers', 'action' => 'view', $ipAddress->customer->id],
                            ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Customer Number') ?></th>
                            <td><?= $ipAddress->customer !== null ? h($ipAddress->customer->number) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Contract') ?></th>
                            <td><?= $ipAddress->contract !== null ? $this->Html->link(
                                $ipAddress->contract->number ?? '--',
                                [
                                    'controller' => 'Contracts',
                                    'action' => 'view',
                                    $ipAddress->contract->id,
                                    'customer_id' => $ipAddress->contract->customer_id,
                                ],
                            ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('IP Address') ?></th>
                            <td><?= h($ipAddress->ip_address) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Type Of Use') ?></th>
                            <td><?= h($ipAddress->type_of_use->label()) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Device') ?></th>
                            <td><?php
                            if (isset($ipAddress->routeros_devices)) {
                                $device = $ipAddress->routeros_devices->first();
                                $deviceUrl = isset($device['id']) ? Links::routerosDevice((string)$device['id']) : null;
                                echo $deviceUrl !== null ?
                                    $this->Html->link(
                                        $device['system_description'],
                                        $deviceUrl,
                                        ['target' => '_blank'],
                                    ) . '<br>' : '';
                                unset($device);
                            }
                            ?></td>
                        </tr>
                        <tr>
                            <th><?= __('IP Address Range') ?></th>
                            <td><?php
                            if (isset($ipAddress->ip_address_ranges)) {
                                $range = $ipAddress->ip_address_ranges->first();
                                $accessPointUrl = isset($range['access_point']['id'])
                                    ? Links::accessPoint((string)$range['access_point']['id'])
                                    : null;
                                echo $accessPointUrl !== null ?
                                    __('Access Point') . ': ' . $this->Html->link(
                                        $range['access_point']['name'],
                                        $accessPointUrl,
                                        ['target' => '_blank'],
                                    ) . '<br>' : '';
                                $rangeUrl = isset($range['id']) ? Links::ipAddressRange((string)$range['id']) : null;
                                echo $rangeUrl !== null ?
                                    __('Range') . ': ' . $this->Html->link(
                                        $range['name'],
                                        $rangeUrl,
                                        ['target' => '_blank'],
                                    ) . '<br>' : '';
                                    unset($range);
                            }
                            ?></td>
                        </tr>
                    </table>
                </div>
                <div class="column">
                    <?= $this->element('common/audit', ['entity' => $ipAddress]) ?>
                </div>
            </div>
            <div class="text">
                <strong><?= __('Note') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($ipAddress->note)); ?>
                </blockquote>
            </div>
        </div>
    </div>
</div>
