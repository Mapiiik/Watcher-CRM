<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\RemovedIpNetwork $removedIpNetwork
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('Edit Removed IP Network'),
                ['action' => 'edit', $removedIpNetwork->id],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->postLink(
                __('Delete Removed IP Network'),
                ['action' => 'delete', $removedIpNetwork->id],
                [
                    'confirm' => __('Are you sure you want to delete # {0}?', $removedIpNetwork->id),
                    'class' => 'side-nav-item',
                ],
            ) ?>
            <?= $this->AuthLink->link(
                __('List Removed IP Networks'),
                ['action' => 'index'],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->link(
                __('New Removed IP Network'),
                ['action' => 'add'],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="removedIpNetworks view content">
            <h3><?= h($removedIpNetwork->ip_network) ?></h3>
            <div class="row">
                <div class="column">
                    <table>
                        <tr>
                            <th><?= __('Customer') ?></th>
                            <td><?= $removedIpNetwork->customer !== null ?
                                $this->Html->link(
                                    $removedIpNetwork->customer->name ?? '(' . $removedIpNetwork->customer->id . ')',
                                    ['controller' => 'Customers', 'action' => 'view', $removedIpNetwork->customer->id],
                                ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Customer Number') ?></th>
                            <td><?= $removedIpNetwork->customer !== null ?
                                h($removedIpNetwork->customer->number) : ''
                            ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Contract') ?></th>
                            <td><?= $removedIpNetwork->contract !== null ?
                                $this->Html->link(
                                    $removedIpNetwork->contract->number ?? '--',
                                    [
                                        'controller' => 'Contracts',
                                        'action' => 'view',
                                        $removedIpNetwork->contract->id,
                                        'customer_id' => $removedIpNetwork->contract->customer_id,
                                    ],
                                ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('IP Network') ?></th>
                            <td><?= h($removedIpNetwork->ip_network) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Type Of Use') ?></th>
                            <td><?= h($removedIpNetwork->type_of_use->label()) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('IP Address Range') ?></th>
                            <td><?php
                            $range = $removedIpNetwork->ip_address_ranges->data?->first();
                            if ($range !== null) {
                                echo $this->element('IpAddressRanges/summary', ['range' => $range]);
                            }
                            echo $this->element('NMS/unavailable', [
                                'answer' => $removedIpNetwork->ip_address_ranges,
                            ]);
                            ?></td>
                        </tr>
                    </table>
                </div>
                <div class="column">
                    <?= $this->element('common/audit', ['entity' => $removedIpNetwork]) ?>
                </div>
            </div>
            <div class="text">
                <strong><?= __('Note') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($removedIpNetwork->note)); ?>
                </blockquote>
            </div>
        </div>
    </div>
</div>
