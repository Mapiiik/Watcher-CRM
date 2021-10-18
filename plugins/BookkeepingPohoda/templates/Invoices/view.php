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
            <?= $this->Html->link(__('Edit Invoice'), ['action' => 'edit', $invoice->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete Invoice'), ['action' => 'delete', $invoice->id], ['confirm' => __('Are you sure you want to delete # {0}?', $invoice->id), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Invoices'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Invoice'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-80">
        <div class="invoices view content">
            <h3><?= h($invoice->number) ?></h3>
            <table>
                <tr>
                    <th><?= __('Customer') ?></th>
                    <td><?= $invoice->has('customer') ? $this->Html->link($invoice->customer->name, ['controller' => 'Customers', 'action' => 'view', $invoice->customer->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Number') ?></th>
                    <td><?= $this->Number->format($invoice->number) ?></td>
                </tr>
                <tr>
                    <th><?= __('Varsym') ?></th>
                    <td><?= $this->Number->format($invoice->varsym) ?></td>
                </tr>
                <tr>
                    <th><?= __('Sum') ?></th>
                    <td><?= $this->Number->format($invoice->sum) ?></td>
                </tr>
                <tr>
                    <th><?= __('Debt') ?></th>
                    <td><?= $this->Number->format($invoice->debt) ?></td>
                </tr>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= $this->Number->format($invoice->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Date') ?></th>
                    <td><?= h($invoice->date) ?></td>
                </tr>
                <tr>
                    <th><?= __('Maturity') ?></th>
                    <td><?= h($invoice->maturity) ?></td>
                </tr>
                <tr>
                    <th><?= __('Payment Date') ?></th>
                    <td><?= h($invoice->payment_date) ?></td>
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
