<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\EntityInterface $radgroupreply
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('Edit Radgroupreply'), ['action' => 'edit', $radgroupreply->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete Radgroupreply'), ['action' => 'delete', $radgroupreply->id], ['confirm' => __('Are you sure you want to delete # {0}?', $radgroupreply->id), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Radgroupreply'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Radgroupreply'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-80">
        <div class="radgroupreply view content">
            <h3><?= h($radgroupreply->id) ?></h3>
            <table>
                <tr>
                    <th><?= __('Groupname') ?></th>
                    <td><?= h($radgroupreply->groupname) ?></td>
                </tr>
                <tr>
                    <th><?= __('Attribute') ?></th>
                    <td><?= h($radgroupreply->attribute) ?></td>
                </tr>
                <tr>
                    <th><?= __('Op') ?></th>
                    <td><?= h($radgroupreply->op) ?></td>
                </tr>
                <tr>
                    <th><?= __('Value') ?></th>
                    <td><?= h($radgroupreply->value) ?></td>
                </tr>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= $this->Number->format($radgroupreply->id) ?></td>
                </tr>
            </table>
        </div>
    </div>
</div>
