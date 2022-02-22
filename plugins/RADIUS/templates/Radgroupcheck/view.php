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
            <?= $this->Html->link(
                __('Edit Radgroupcheck'),
                ['action' => 'edit', $radgroupcheck->id],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->Form->postLink(
                __('Delete Radgroupcheck'),
                ['action' => 'delete', $radgroupcheck->id],
                [
                    'confirm' => __('Are you sure you want to delete # {0}?', $radgroupcheck->id),
                    'class' => 'side-nav-item',
                ]
            ) ?>
            <?= $this->Html->link(__('List Radgroupcheck'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Radgroupcheck'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-90">
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
            <div class="related">
                <h4><?= __('Related Radusergroup') ?></h4>
                <?php if (!empty($radgroupcheck->radusergroup)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <th><?= __('Username') ?></th>
                            <th><?= __('Groupname') ?></th>
                            <th><?= __('Priority') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($radgroupcheck->radusergroup as $radusergroup) : ?>
                        <tr>
                            <td><?= h($radusergroup->id) ?></td>
                            <td><?= h($radusergroup->username) ?></td>
                            <td><?= h($radusergroup->groupname) ?></td>
                            <td><?= h($radusergroup->priority) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(
                                    __('View'),
                                    ['controller' => 'Radusergroup', 'action' => 'view', $radusergroup->id]
                                ) ?>
                                <?= $this->Html->link(
                                    __('Edit'),
                                    ['controller' => 'Radusergroup', 'action' => 'edit', $radusergroup->id],
                                    ['class' => 'win-link']
                                ) ?>
                                <?= $this->Form->postLink(
                                    __('Delete'),
                                    ['controller' => 'Radusergroup', 'action' => 'delete', $radusergroup->id],
                                    ['confirm' => __('Are you sure you want to delete # {0}?', $radusergroup->id)]
                                ) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
