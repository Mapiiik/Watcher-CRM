<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\EntityInterface $radpostauth
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('Edit Radpostauth'), ['action' => 'edit', $radpostauth->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete Radpostauth'), ['action' => 'delete', $radpostauth->id], ['confirm' => __('Are you sure you want to delete # {0}?', $radpostauth->id), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Radpostauth'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Radpostauth'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-80">
        <div class="radpostauth view content">
            <h3><?= h($radpostauth->id) ?></h3>
            <table>
                <tr>
                    <th><?= __('Username') ?></th>
                    <td><?= h($radpostauth->username) ?></td>
                </tr>
                <tr>
                    <th><?= __('Pass') ?></th>
                    <td><?= h($radpostauth->pass) ?></td>
                </tr>
                <tr>
                    <th><?= __('Reply') ?></th>
                    <td><?= h($radpostauth->reply) ?></td>
                </tr>
                <tr>
                    <th><?= __('Calledstationid') ?></th>
                    <td><?= h($radpostauth->calledstationid) ?></td>
                </tr>
                <tr>
                    <th><?= __('Callingstationid') ?></th>
                    <td><?= h($radpostauth->callingstationid) ?></td>
                </tr>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= $this->Number->format($radpostauth->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Authdate') ?></th>
                    <td><?= h($radpostauth->authdate) ?></td>
                </tr>
            </table>
        </div>
    </div>
</div>
