<?php
/**
 * @var \App\View\AppView $this
 * @var \Bookkeeping\Model\Entity\Invoice $invoice
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __d('bookkeeping', 'Actions') ?></h4>
            <?= $this->AuthLink->link(
                __d('bookkeeping', 'Edit Invoice'),
                ['action' => 'edit', $invoice->id],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->postLink(
                __d('bookkeeping', 'Delete Invoice'),
                ['action' => 'delete', $invoice->id],
                [
                    'confirm' => __d('bookkeeping', 'Are you sure you want to delete # {0}?', $invoice->id),
                    'class' => 'side-nav-item',
                ],
            ) ?>
            <?= $this->AuthLink->link(
                __d('bookkeeping', 'List Invoices'),
                ['action' => 'index'],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->link(
                __d('bookkeeping', 'New Invoice'),
                ['action' => 'add'],
                ['class' => 'side-nav-item'],
            ) ?>
            <br>
            <?= $this->AuthLink->link(
                __d('bookkeeping', 'Download Invoice'),
                ['action' => 'download', $invoice->id],
                ['class' => 'side-nav-item', 'target' => '_blank'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="invoices view content">
            <h3><?= h($invoice->number) ?></h3>
            <div class="row">
                <div class="column">
                    <table>
                        <tr>
                            <th><?= __d('bookkeeping', 'Customer') ?></th>
                            <td><?= $invoice->__isset('customer') ? $this->Html->link(
                                $invoice->customer->name,
                                [
                                    'plugin' => null,
                                    'controller' => 'Customers',
                                    'action' => 'view',
                                    $invoice->customer->id,
                                ],
                            ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __d('bookkeeping', 'Customer Number') ?></th>
                            <td><?= $invoice->__isset('customer') ? h($invoice->customer->number) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __d('bookkeeping', 'Number') ?></th>
                            <td><?= h($invoice->number) ?></td>
                        </tr>
                        <tr>
                            <th><?= __d('bookkeeping', 'Variable Symbol') ?></th>
                            <td><?= h($invoice->variable_symbol) ?></td>
                        </tr>
                        <tr>
                            <th><?= __d('bookkeeping', 'Creation Date') ?></th>
                            <td><?= h($invoice->creation_date) ?></td>
                        </tr>
                        <tr>
                            <th><?= __d('bookkeeping', 'Due Date') ?></th>
                            <td><?= h($invoice->due_date) ?></td>
                        </tr>
                        <tr>
                            <th><?= __d('bookkeeping', 'Total') ?></th>
                            <td><?= $invoice->total === null ?
                                '' : $this->Number->currency($invoice->total->toString()) ?></td>
                        </tr>
                        <tr>
                            <th><?= __d('bookkeeping', 'Debt') ?></th>
                            <td><?= $invoice->debt === null ?
                                '' : $this->Number->currency($invoice->debt->toString()) ?></td>
                        </tr>
                        <tr>
                            <th><?= __d('bookkeeping', 'Payment Date') ?></th>
                            <td><?= h($invoice->payment_date) ?></td>
                        </tr>
                        <tr>
                            <th><?= __d('bookkeeping', 'Send By Email') ?></th>
                            <td><?= $invoice->send_by_email ?
                                __d('bookkeeping', 'Yes') : __d('bookkeeping', 'No'); ?></td>
                        </tr>
                        <tr>
                            <th><?= __d('bookkeeping', 'Email Sent') ?></th>
                            <td><?= h($invoice->email_sent) ?></td>
                        </tr>
                    </table>
                </div>
                <div class="column">
                    <?= $this->element('common/audit', ['entity' => $invoice]) ?>
                </div>
            </div>
            <div class="text">
                <strong><?= __d('bookkeeping', 'Text') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($invoice->text)); ?>
                </blockquote>
            </div>
        </div>
    </div>
</div>
