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
            <?= $this->Html->link(
                __('Edit Removed Ip Network'),
                ['action' => 'edit', $removedIpNetwork->id],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->Form->postLink(
                __('Delete Removed Ip Network'),
                ['action' => 'delete', $removedIpNetwork->id],
                [
                    'confirm' => __('Are you sure you want to delete # {0}?', $removedIpNetwork->id),
                    'class' => 'side-nav-item',
                ]
            ) ?>
            <?= $this->Html->link(
                __('List Removed Ip Networks'),
                ['action' => 'index'],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->Html->link(
                __('New Removed Ip Network'),
                ['action' => 'add'],
                ['class' => 'side-nav-item']
            ) ?>
        </div>
    </aside>
    <div class="column-responsive column-90">
        <div class="removedIpNetworks view content">
            <h3><?= h($removedIpNetwork->ip_network) ?></h3>
            <table>
                <tr>
                    <th><?= __('Customer') ?></th>
                    <td><?= $removedIpNetwork->has('customer') ?
                        $this->Html->link(
                            $removedIpNetwork->customer->name,
                            ['controller' => 'Customers', 'action' => 'view', $removedIpNetwork->customer->id]
                        ) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Contract') ?></th>
                    <td><?= $removedIpNetwork->has('contract') ?
                        $this->Html->link(
                            $removedIpNetwork->contract->name,
                            ['controller' => 'Contracts', 'action' => 'view', $removedIpNetwork->contract->id]
                        ) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Ip Network') ?></th>
                    <td><?= h($removedIpNetwork->ip_network) ?></td>
                </tr>
                <tr>
                    <th><?= __('Type Of Use') ?></th>
                    <td><?= h($types_of_use[$removedIpNetwork->type_of_use]) ?></td>
                </tr>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= $this->Number->format($removedIpNetwork->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Removed') ?></th>
                    <td><?= h($removedIpNetwork->removed) ?></td>
                </tr>
                <tr>
                    <th><?= __('Removed By') ?></th>
                    <td><?= $this->Number->format($removedIpNetwork->removed_by) ?></td>
                </tr>
            </table>
            <div class="text">
                <strong><?= __('Note') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($removedIpNetwork->note)); ?>
                </blockquote>
            </div>
        </div>
    </div>
</div>
