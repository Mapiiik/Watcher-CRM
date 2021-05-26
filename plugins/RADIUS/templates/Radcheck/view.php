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
    <div class="column-responsive column-90">
        <div class="radcheck view content">
            <h3><?= h($radcheck->username) ?></h3>
            <table>
                <tr>
                    <th><?= __('Username') ?></th>
                    <td><?= $radcheck->has('user') ? $this->Html->link($radcheck->user->username, ['controller' => 'Users', 'action' => 'view', $radcheck->user->id]) : $radcheck->username ?></td>
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
                    <th><?= __('Id') ?></th>
                    <td><?= $this->Number->format($radcheck->id) ?></td>
                </tr>
            </table>
        </div>
    </div>
</div>
