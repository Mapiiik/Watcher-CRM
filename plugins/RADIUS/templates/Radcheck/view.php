<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\EntityInterface $radcheck
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('Edit Radcheck'), ['action' => 'edit', $radcheck->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete Radcheck'), ['action' => 'delete', $radcheck->id], ['confirm' => __('Are you sure you want to delete # {0}?', $radcheck->id), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Radcheck'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Radcheck'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-80">
        <div class="radcheck view content">
            <h3><?= h($radcheck->id) ?></h3>
            <table>
                <tr>
                    <th><?= __('Username') ?></th>
                    <td><?= h($radcheck->username) ?></td>
                </tr>
                <tr>
                    <th><?= __('Attribute') ?></th>
                    <td><?= h($radcheck->attribute) ?></td>
                </tr>
                <tr>
                    <th><?= __('Op') ?></th>
                    <td><?= h($radcheck->op) ?></td>
                </tr>
                <tr>
                    <th><?= __('Value') ?></th>
                    <td><?= h($radcheck->value) ?></td>
                </tr>
                <tr>
                    <th><?= __('Customer') ?></th>
                    <td><?= $radcheck->has('customer') ? $this->Html->link($radcheck->customer->title, ['controller' => 'Customers', 'action' => 'view', $radcheck->customer->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Contract') ?></th>
                    <td><?= $radcheck->has('contract') ? $this->Html->link($radcheck->contract->id, ['controller' => 'Contracts', 'action' => 'view', $radcheck->contract->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= $this->Number->format($radcheck->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Customer Connection Id') ?></th>
                    <td><?= $this->Number->format($radcheck->customer_connection_id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Modified By') ?></th>
                    <td><?= $this->Number->format($radcheck->modified_by) ?></td>
                </tr>
                <tr>
                    <th><?= __('Created By') ?></th>
                    <td><?= $this->Number->format($radcheck->created_by) ?></td>
                </tr>
                <tr>
                    <th><?= __('Type') ?></th>
                    <td><?= $this->Number->format($radcheck->type) ?></td>
                </tr>
                <tr>
                    <th><?= __('Modified') ?></th>
                    <td><?= h($radcheck->modified) ?></td>
                </tr>
                <tr>
                    <th><?= __('Created') ?></th>
                    <td><?= h($radcheck->created) ?></td>
                </tr>
            </table>
        </div>
    </div>
</div>
