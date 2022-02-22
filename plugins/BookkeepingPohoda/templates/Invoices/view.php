<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\EntityInterface $invoice
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(
                __('Edit Invoice'),
                ['action' => 'edit', $invoice->id],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->Form->postLink(
                __('Delete Invoice'),
                ['action' => 'delete', $invoice->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $invoice->id), 'class' => 'side-nav-item']
            ) ?>
            <?= $this->Html->link(__('List Invoices'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Invoice'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
            <br />
            <?= $this->Html->link(
                __('Download Invoice'),
                ['action' => 'download', $invoice->id],
                ['class' => 'side-nav-item', 'target' => '_blank']
            ) ?>
        </div>
    </aside>
    <div class="column-responsive column-80">
        <div class="invoices view content">
            <h3><?= h($invoice->number) ?></h3>
            <table>
                <tr>
                    <th><?= __('Customer') ?></th>
                    <td><?= $invoice->has('customer') ? $this->Html->link(
                        $invoice->customer->name,
                        ['plugin' => null, 'controller' => 'Customers', 'action' => 'view', $invoice->customer->id]
                    ) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= $this->Number->format($invoice->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Number') ?></th>
                    <td><?= $this->Number->format($invoice->number) ?></td>
                </tr>
                <tr>
                    <th><?= __('Variable Symbol') ?></th>
                    <td><?= $this->Number->format($invoice->variable_symbol) ?></td>
                </tr>
                <tr>
                    <th><?= __('Total') ?></th>
                    <td><?= $this->Number->format($invoice->total) ?></td>
                </tr>
                <tr>
                    <th><?= __('Debt') ?></th>
                    <td><?= $this->Number->format($invoice->debt) ?></td>
                </tr>
                <tr>
                    <th><?= __('Created By') ?></th>
                    <td><?= $this->Number->format($invoice->created_by) ?></td>
                </tr>
                <tr>
                    <th><?= __('Modified By') ?></th>
                    <td><?= $this->Number->format($invoice->modified_by) ?></td>
                </tr>
                <tr>
                    <th><?= __('Creation Date') ?></th>
                    <td><?= h($invoice->creation_date) ?></td>
                </tr>
                <tr>
                    <th><?= __('Due Date') ?></th>
                    <td><?= h($invoice->due_date) ?></td>
                </tr>
                <tr>
                    <th><?= __('Payment Date') ?></th>
                    <td><?= h($invoice->payment_date) ?></td>
                </tr>
                <tr>
                    <th><?= __('Email Sent') ?></th>
                    <td><?= h($invoice->email_sent) ?></td>
                </tr>
                <tr>
                    <th><?= __('Created') ?></th>
                    <td><?= h($invoice->created) ?></td>
                </tr>
                <tr>
                    <th><?= __('Modified') ?></th>
                    <td><?= h($invoice->modified) ?></td>
                </tr>
                <tr>
                    <th><?= __('Send By Email') ?></th>
                    <td><?= $invoice->send_by_email ? __('Yes') : __('No'); ?></td>
                </tr>
            </table>
            <div class="text">
                <strong><?= __('Text') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($invoice->text)); ?>
                </blockquote>
            </div>
        </div>
    </div>
</div>
