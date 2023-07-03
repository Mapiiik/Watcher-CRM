<?php
/**
 * @var \App\View\AppView $this
 * @var \BookkeepingPohoda\Model\Entity\Invoice $invoice
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __d('bookkeeping_pohoda', 'Actions') ?></h4>
            <?= $this->AuthLink->link(
                __d('bookkeeping_pohoda', 'Edit Invoice'),
                ['action' => 'edit', $invoice->id],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->postLink(
                __d('bookkeeping_pohoda', 'Delete Invoice'),
                ['action' => 'delete', $invoice->id],
                [
                    'confirm' => __d('bookkeeping_pohoda', 'Are you sure you want to delete # {0}?', $invoice->id),
                    'class' => 'side-nav-item',
                ]
            ) ?>
            <?= $this->AuthLink->link(
                __d('bookkeeping_pohoda', 'List Invoices'),
                ['action' => 'index'],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->link(
                __d('bookkeeping_pohoda', 'New Invoice'),
                ['action' => 'add'],
                ['class' => 'side-nav-item']
            ) ?>
            <br />
            <?= $this->AuthLink->link(
                __d('bookkeeping_pohoda', 'Download Invoice'),
                ['action' => 'download', $invoice->id],
                ['class' => 'side-nav-item', 'target' => '_blank']
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
                            <th><?= __d('bookkeeping_pohoda', 'Customer') ?></th>
                            <td><?= $invoice->__isset('customer') ? $this->Html->link(
                                $invoice->customer->name,
                                [
                                    'plugin' => null,
                                    'controller' => 'Customers',
                                    'action' => 'view',
                                    $invoice->customer->id,
                                ]
                            ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __d('bookkeeping_pohoda', 'Customer Number') ?></th>
                            <td><?= $invoice->__isset('customer') ? h($invoice->customer->number) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __d('bookkeeping_pohoda', 'Number') ?></th>
                            <td><?= $this->Number->format($invoice->number) ?></td>
                        </tr>
                        <tr>
                            <th><?= __d('bookkeeping_pohoda', 'Variable Symbol') ?></th>
                            <td><?= h($invoice->variable_symbol) ?></td>
                        </tr>
                        <tr>
                            <th><?= __d('bookkeeping_pohoda', 'Creation Date') ?></th>
                            <td><?= h($invoice->creation_date) ?></td>
                        </tr>
                        <tr>
                            <th><?= __d('bookkeeping_pohoda', 'Due Date') ?></th>
                            <td><?= h($invoice->due_date) ?></td>
                        </tr>
                        <tr>
                            <th><?= __d('bookkeeping_pohoda', 'Total') ?></th>
                            <td><?= $this->Number->currency($invoice->total) ?></td>
                        </tr>
                        <tr>
                            <th><?= __d('bookkeeping_pohoda', 'Debt') ?></th>
                            <td><?= $this->Number->currency($invoice->debt) ?></td>
                        </tr>
                        <tr>
                            <th><?= __d('bookkeeping_pohoda', 'Payment Date') ?></th>
                            <td><?= h($invoice->payment_date) ?></td>
                        </tr>
                        <tr>
                            <th><?= __d('bookkeeping_pohoda', 'Send By Email') ?></th>
                            <td><?= $invoice->send_by_email ?
                                __d('bookkeeping_pohoda', 'Yes') : __d('bookkeeping_pohoda', 'No'); ?></td>
                        </tr>
                        <tr>
                            <th><?= __d('bookkeeping_pohoda', 'Email Sent') ?></th>
                            <td><?= h($invoice->email_sent) ?></td>
                        </tr>
                    </table>
                </div>
                <div class="column">
                    <table>
                        <tr>
                            <th><?= __d('bookkeeping_pohoda', 'Id') ?></th>
                            <td><?= $this->Number->format($invoice->id) ?></td>
                        </tr>
                        <tr>
                            <th><?= __d('bookkeeping_pohoda', 'Created') ?></th>
                            <td><?= h($invoice->created) ?></td>
                        </tr>
                        <tr>
                            <th><?= __d('bookkeeping_pohoda', 'Created By') ?></th>
                            <td><?= $invoice->__isset('creator') ? $this->Html->link(
                                $invoice->creator->username,
                                [
                                    'controller' => 'AppUsers',
                                    'action' => 'view',
                                    $invoice->creator->id,
                                ]
                            ) : h($invoice->created_by) ?></td>
                        </tr>
                        <tr>
                            <th><?= __d('bookkeeping_pohoda', 'Modified') ?></th>
                            <td><?= h($invoice->modified) ?></td>
                        </tr>
                        <tr>
                            <th><?= __d('bookkeeping_pohoda', 'Modified By') ?></th>
                            <td><?= $invoice->__isset('modifier') ? $this->Html->link(
                                $invoice->modifier->username,
                                [
                                    'controller' => 'AppUsers',
                                    'action' => 'view',
                                    $invoice->modifier->id,
                                ]
                            ) : h($invoice->modified_by) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="text">
                <strong><?= __d('bookkeeping_pohoda', 'Text') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($invoice->text)); ?>
                </blockquote>
            </div>
        </div>
    </div>
</div>
