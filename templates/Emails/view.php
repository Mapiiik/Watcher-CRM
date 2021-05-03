<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Email $email
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('Edit Email'), ['action' => 'edit', $email->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete Email'), ['action' => 'delete', $email->id], ['confirm' => __('Are you sure you want to delete # {0}?', $email->id), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Emails'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Email'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-80">
        <div class="emails view content">
            <h3><?= h($email->id) ?></h3>
            <table>
                <tr>
                    <th><?= __('Customer') ?></th>
                    <td><?= $email->has('customer') ? $this->Html->link($email->customer->name, ['controller' => 'Customers', 'action' => 'view', $email->customer->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Email') ?></th>
                    <td><?= h($email->email) ?></td>
                </tr>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= $this->Number->format($email->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Use For Billing') ?></th>
                    <td><?= $email->use_for_billing ? __('Yes') : __('No'); ?></td>
                </tr>
                <tr>
                    <th><?= __('Use For Outages') ?></th>
                    <td><?= $email->use_for_outages ? __('Yes') : __('No'); ?></td>
                </tr>
                <tr>
                    <th><?= __('Use For Commercial') ?></th>
                    <td><?= $email->use_for_commercial ? __('Yes') : __('No'); ?></td>
                </tr>
            </table>
        </div>
    </div>
</div>
