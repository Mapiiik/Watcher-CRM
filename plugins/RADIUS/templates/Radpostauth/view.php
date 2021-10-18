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
    <div class="column-responsive column-90">
        <div class="radpostauth view content">
            <h3><?= h($radpostauth->id) ?></h3>
            <table>
                <tr>
                    <th><?= __('Username') ?></th>
                    <td><?= $radpostauth->has('account') ? $this->Html->link($radpostauth->account->username, ['controller' => 'Accounts', 'action' => 'view', $radpostauth->account->id]) : $radpostauth->username ?></td>
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
            <div class="text">
                <strong><?= __('Pass') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($radpostauth->pass)); ?>
                </blockquote>
            </div>
            <div class="text">
                <strong><?= __('Reply') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($radpostauth->reply)); ?>
                </blockquote>
            </div>
            <div class="text">
                <strong><?= __('Calledstationid') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($radpostauth->calledstationid)); ?>
                </blockquote>
            </div>
            <div class="text">
                <strong><?= __('Callingstationid') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($radpostauth->callingstationid)); ?>
                </blockquote>
            </div>
        </div>
    </div>
</div>
