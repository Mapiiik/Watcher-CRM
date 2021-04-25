<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\RemovedIp $removedIp
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('Edit Removed Ip'), ['action' => 'edit', $removedIp->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete Removed Ip'), ['action' => 'delete', $removedIp->id], ['confirm' => __('Are you sure you want to delete # {0}?', $removedIp->id), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Removed Ips'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Removed Ip'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-80">
        <div class="removedIps view content">
            <h3><?= h($removedIp->ip) ?></h3>
            <table>
                <tr>
                    <th><?= __('Customer') ?></th>
                    <td><?= $removedIp->has('customer') ? $this->Html->link($removedIp->customer->name, ['controller' => 'Customers', 'action' => 'view', $removedIp->customer->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Contract') ?></th>
                    <td><?= $removedIp->has('contract') ? $this->Html->link($removedIp->contract->number, ['controller' => 'Contracts', 'action' => 'view', $removedIp->contract->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Ip') ?></th>
                    <td><?= h($removedIp->ip) ?></td>
                </tr>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= $this->Number->format($removedIp->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Removed') ?></th>
                    <td><?= h($removedIp->removed) ?></td>
                </tr>
                <tr>
                    <th><?= __('Removed By') ?></th>
                    <td><?= $this->Number->format($removedIp->removed_by) ?></td>
                </tr>
            </table>
            <div class="text">
                <strong><?= __('Note') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($removedIp->note)); ?>
                </blockquote>
            </div>
        </div>
    </div>
</div>
