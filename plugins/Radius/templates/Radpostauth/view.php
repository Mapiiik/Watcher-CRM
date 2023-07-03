<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\EntityInterface $radpostauth
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __d('radius', 'Actions') ?></h4>
            <?= $this->AuthLink->link(
                __d('radius', 'Edit RADIUS Post Authentication'),
                ['action' => 'edit', $radpostauth->id],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->postLink(
                __d('radius', 'Delete RADIUS Post Authentication'),
                ['action' => 'delete', $radpostauth->id],
                [
                    'confirm' => __d('radius', 'Are you sure you want to delete # {0}?', $radpostauth->id),
                    'class' => 'side-nav-item',
                ]
            ) ?>
            <?= $this->AuthLink->link(
                __d('radius', 'List RADIUS Post Authentications'),
                ['action' => 'index'],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->link(
                __d('radius', 'New RADIUS Post Authentication'),
                ['action' => 'add'],
                ['class' => 'side-nav-item']
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="radpostauth view content">
            <h3><?= h($radpostauth->id) ?></h3>
            <table>
                <tr>
                    <th><?= __d('radius', 'Username') ?></th>
                    <td><?= $radpostauth->__isset('account') ? $this->Html->link(
                        $radpostauth->account->username,
                        ['controller' => 'Accounts', 'action' => 'view', $radpostauth->account->id]
                    ) : $radpostauth->username ?></td>
                </tr>
                <tr>
                    <th><?= __d('radius', 'Authentication Date') ?></th>
                    <td><?= h($radpostauth->authdate) ?></td>
                </tr>
                <tr>
                    <th><?= __d('radius', 'Id') ?></th>
                    <td><?= $this->Number->format($radpostauth->id) ?></td>
                </tr>
            </table>
            <div class="text">
                <strong><?= __d('radius', 'Pass') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($radpostauth->pass)); ?>
                </blockquote>
            </div>
            <div class="text">
                <strong><?= __d('radius', 'Reply') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($radpostauth->reply)); ?>
                </blockquote>
            </div>
            <div class="text">
                <strong><?= __d('radius', 'Called Station ID') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($radpostauth->calledstationid)); ?>
                </blockquote>
            </div>
            <div class="text">
                <strong><?= __d('radius', 'Calling Station ID') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($radpostauth->callingstationid)); ?>
                </blockquote>
            </div>
        </div>
    </div>
</div>
