<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Ip $ip
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('Edit Ip'), ['action' => 'edit', $ip->ip], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete Ip'), ['action' => 'delete', $ip->ip], ['confirm' => __('Are you sure you want to delete # {0}?', $ip->ip), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Ips'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Ip'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-80">
        <div class="ips view content">
            <h3><?= h($ip->ip) ?></h3>
            <table>
                <tr>
                    <th><?= __('Ip') ?></th>
                    <td><?= h($ip->ip) ?></td>
                </tr>
                <tr>
                    <th><?= __('Customer') ?></th>
                    <td><?= $ip->has('customer') ? $this->Html->link($ip->customer->title, ['controller' => 'Customers', 'action' => 'view', $ip->customer->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Contract') ?></th>
                    <td><?= $ip->has('contract') ? $this->Html->link($ip->contract->id, ['controller' => 'Contracts', 'action' => 'view', $ip->contract->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= $this->Number->format($ip->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Created By') ?></th>
                    <td><?= $this->Number->format($ip->created_by) ?></td>
                </tr>
                <tr>
                    <th><?= __('Modified By') ?></th>
                    <td><?= $this->Number->format($ip->modified_by) ?></td>
                </tr>
                <tr>
                    <th><?= __('Created') ?></th>
                    <td><?= h($ip->created) ?></td>
                </tr>
                <tr>
                    <th><?= __('Modified') ?></th>
                    <td><?= h($ip->modified) ?></td>
                </tr>
            </table>
            <div class="text">
                <strong><?= __('Note') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($ip->note)); ?>
                </blockquote>
            </div>
        </div>
    </div>
</div>
