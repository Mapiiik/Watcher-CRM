<?php
/**
 * @var \App\View\AppView $this
 * @var \Radius\Model\Entity\Radcheck $radcheck
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __d('radius', 'Actions') ?></h4>
            <?= $this->AuthLink->link(
                __d('radius', 'Edit RADIUS Check'),
                ['action' => 'edit', $radcheck->id],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->postLink(
                __d('radius', 'Delete RADIUS Check'),
                ['action' => 'delete', $radcheck->id],
                [
                    'confirm' => __d('radius', 'Are you sure you want to delete # {0}?', $radcheck->id),
                    'class' => 'side-nav-item',
                ]
            ) ?>
            <?= $this->AuthLink->link(
                __d('radius', 'List RADIUS Checks'),
                ['action' => 'index'],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->link(
                __d('radius', 'New RADIUS Check'),
                ['action' => 'add'],
                ['class' => 'side-nav-item']
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="radcheck view content">
            <h3><?= h($radcheck->id) ?></h3>
            <table>
                <tr>
                    <th><?= __d('radius', 'Username') ?></th>
                    <td><?= $radcheck->__isset('account') ? $this->Html->link(
                        $radcheck->account->username,
                        ['controller' => 'Accounts', 'action' => 'view', $radcheck->account->id]
                    ) : $radcheck->username ?></td>
                </tr>
                <tr>
                    <th><?= __d('radius', 'Attribute') ?></th>
                    <td><?= h($radcheck->attribute) ?></td>
                </tr>
                <tr>
                    <th><?= __d('radius', 'Op') ?></th>
                    <td><?= h($radcheck->op) ?></td>
                </tr>
                <tr>
                    <th><?= __d('radius', 'Value') ?></th>
                    <td><?= h($radcheck->value) ?></td>
                </tr>
                 <tr>
                    <th><?= __d('radius', 'Id') ?></th>
                    <td><?= $this->Number->format($radcheck->id) ?></td>
                </tr>
            </table>
        </div>
    </div>
</div>
