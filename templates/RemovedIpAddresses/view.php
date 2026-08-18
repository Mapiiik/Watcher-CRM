<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\RemovedIpAddress $removedIpAddress
 */

use App\NMS\Links;
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('Edit Removed IP Address'),
                ['action' => 'edit', $removedIpAddress->id],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->postLink(
                __('Delete Removed IP Address'),
                ['action' => 'delete', $removedIpAddress->id],
                [
                    'confirm' => __('Are you sure you want to delete # {0}?', $removedIpAddress->id),
                    'class' => 'side-nav-item',
                ],
            ) ?>
            <?= $this->AuthLink->link(
                __('List Removed IP Addresses'),
                ['action' => 'index'],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->link(
                __('New Removed IP Address'),
                ['action' => 'add'],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="removedIpAddresses view content">
            <h3><?= h($removedIpAddress->ip_address) ?></h3>
            <div class="row">
                <div class="column">
                    <table>
                        <tr>
                            <th><?= __('Customer') ?></th>
                            <td><?= $removedIpAddress->customer !== null ? $this->Html->link(
                                $removedIpAddress->customer->name ?? '(' . $removedIpAddress->customer->id . ')',
                                ['controller' => 'Customers', 'action' => 'view', $removedIpAddress->customer->id],
                            ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Customer Number') ?></th>
                            <td><?= $removedIpAddress->customer !== null ?
                                h($removedIpAddress->customer->number) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Contract') ?></th>
                            <td><?= $removedIpAddress->contract !== null ? $this->Html->link(
                                $removedIpAddress->contract->number ?? '--',
                                [
                                    'controller' => 'Contracts',
                                    'action' => 'view',
                                    $removedIpAddress->contract->id,
                                    'customer_id' => $removedIpAddress->contract->customer_id,
                                ],
                            ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('IP Address') ?></th>
                            <td><?= h($removedIpAddress->ip_address) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Type Of Use') ?></th>
                            <td><?= h($removedIpAddress->type_of_use->label()) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('IP Address Range') ?></th>
                            <td><?php
                            if (isset($removedIpAddress->ip_address_ranges)) {
                                $range = $removedIpAddress->ip_address_ranges->first();
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
                    <?= $this->element('common/audit', ['entity' => $removedIpAddress]) ?>
                </div>
            </div>
            <div class="text">
                <strong><?= __('Note') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($removedIpAddress->note)); ?>
                </blockquote>
            </div>
        </div>
    </div>
</div>
