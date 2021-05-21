<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\EntityInterface $radusergroup
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('Edit Radusergroup'), ['action' => 'edit', $radusergroup->username], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete Radusergroup'), ['action' => 'delete', $radusergroup->username], ['confirm' => __('Are you sure you want to delete # {0}?', $radusergroup->username), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Radusergroup'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Radusergroup'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-80">
        <div class="radusergroup view content">
            <h3><?= h($radusergroup->username) ?></h3>
            <table>
                <tr>
                    <th><?= __('Username') ?></th>
                    <td><?= h($radusergroup->username) ?></td>
                </tr>
                <tr>
                    <th><?= __('Groupname') ?></th>
                    <td><?= h($radusergroup->groupname) ?></td>
                </tr>
                <tr>
                    <th><?= __('Priority') ?></th>
                    <td><?= $this->Number->format($radusergroup->priority) ?></td>
                </tr>
            </table>
        </div>
    </div>
</div>
