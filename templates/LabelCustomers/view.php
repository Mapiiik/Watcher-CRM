<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\LabelCustomer $labelCustomer
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('Edit Label Customer'), ['action' => 'edit', $labelCustomer->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete Label Customer'), ['action' => 'delete', $labelCustomer->id], ['confirm' => __('Are you sure you want to delete # {0}?', $labelCustomer->id), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Label Customers'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Label Customer'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-90">
        <div class="labelCustomers view content">
            <h3><?= h($labelCustomer->id) ?></h3>
            <table>
                <tr>
                    <th><?= __('Label') ?></th>
                    <td><?= $labelCustomer->has('label') ? $this->Html->link($labelCustomer->label->name, ['controller' => 'Labels', 'action' => 'view', $labelCustomer->label->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Customer') ?></th>
                    <td><?= $labelCustomer->has('customer') ? $this->Html->link($labelCustomer->customer->name, ['controller' => 'Customers', 'action' => 'view', $labelCustomer->customer->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= $this->Number->format($labelCustomer->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Created By') ?></th>
                    <td><?= $this->Number->format($labelCustomer->created_by) ?></td>
                </tr>
                <tr>
                    <th><?= __('Created') ?></th>
                    <td><?= h($labelCustomer->created) ?></td>
                </tr>
            </table>
            <div class="text">
                <strong><?= __('Note') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($labelCustomer->note)); ?>
                </blockquote>
            </div>
        </div>
    </div>
</div>
