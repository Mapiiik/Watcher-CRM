<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\EntityInterface $radgroupcheck
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('Edit Radgroupcheck'), ['action' => 'edit', $radgroupcheck->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete Radgroupcheck'), ['action' => 'delete', $radgroupcheck->id], ['confirm' => __('Are you sure you want to delete # {0}?', $radgroupcheck->id), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Radgroupcheck'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Radgroupcheck'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-80">
        <div class="radgroupcheck view content">
            <h3><?= h($radgroupcheck->id) ?></h3>
            <table>
                <tr>
                    <th><?= __('Groupname') ?></th>
                    <td><?= h($radgroupcheck->groupname) ?></td>
                </tr>
                <tr>
                    <th><?= __('Attribute') ?></th>
                    <td><?= h($radgroupcheck->attribute) ?></td>
                </tr>
                <tr>
                    <th><?= __('Op') ?></th>
                    <td><?= h($radgroupcheck->op) ?></td>
                </tr>
                <tr>
                    <th><?= __('Value') ?></th>
                    <td><?= h($radgroupcheck->value) ?></td>
                </tr>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= $this->Number->format($radgroupcheck->id) ?></td>
                </tr>
            </table>
        </div>
    </div>
</div>
