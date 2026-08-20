<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\IpNetwork $ipNetwork
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('Edit IP Network'),
                ['action' => 'edit', $ipNetwork->id],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->postLink(
                __('Delete IP Network'),
                ['action' => 'delete', $ipNetwork->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $ipNetwork->id), 'class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->link(
                __('List IP Networks'),
                ['action' => 'index'],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->link(
                __('New IP Network'),
                ['action' => 'add'],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="ipNetworks view content">
            <h3><?= h($ipNetwork->ip_network) ?></h3>
            <div class="row">
                <div class="column">
                    <table>
                        <tr>
                            <th><?= __('Customer') ?></th>
                            <td><?= $ipNetwork->customer !== null ?
                                $this->Html->link(
                                    $ipNetwork->customer->name ?? '(' . $ipNetwork->customer->id . ')',
                                    ['controller' => 'Customers', 'action' => 'view', $ipNetwork->customer->id],
                                ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Customer Number') ?></th>
                            <td><?= $ipNetwork->customer !== null ? h($ipNetwork->customer->number) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Contract') ?></th>
                            <td><?= $ipNetwork->contract !== null ?
                                $this->Html->link(
                                    $ipNetwork->contract->number ?? '--',
                                    [
                                        'controller' => 'Contracts',
                                        'action' => 'view',
                                        $ipNetwork->contract->id,
                                        'customer_id' => $ipNetwork->contract->customer_id,
                                    ],
                                ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('IP Network') ?></th>
                            <td><?= h($ipNetwork->ip_network) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Type Of Use') ?></th>
                            <td><?= h($ipNetwork->type_of_use->label()) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('IP Address Range') ?></th>
                            <td><?php
                            if (isset($ipNetwork->ip_address_ranges)) {
                                echo $this->element('IpAddressRanges/summary', [
                                    'range' => $ipNetwork->ip_address_ranges->first(),
                                ]);
                            }
                            ?></td>
                        </tr>
                    </table>
                </div>
                <div class="column">
                    <?= $this->element('common/audit', ['entity' => $ipNetwork]) ?>
                </div>
            </div>
            <div class="text">
                <strong><?= __('Note') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($ipNetwork->note)); ?>
                </blockquote>
            </div>
        </div>
    </div>
</div>
