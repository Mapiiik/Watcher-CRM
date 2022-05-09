<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Billing $billing
 */

use Cake\I18n\Number;
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('Edit Billing'),
                ['action' => 'edit', $billing->id],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->postLink(
                __('Delete Billing'),
                ['action' => 'delete', $billing->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $billing->id), 'class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->link(__('List Billings'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->AuthLink->link(__('New Billing'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-90">
        <div class="billings view content">
            <h3><?= h($billing->id) ?></h3>
            <table>
                <tr>
                    <th><?= __('Customer') ?></th>
                    <td><?= $billing->has('customer') ? $this->Html->link(
                        $billing->customer->name,
                        ['controller' => 'Customers', 'action' => 'view', $billing->customer->id]
                    ) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Contract') ?></th>
                    <td><?= $billing->has('contract') ? $this->Html->link(
                        $billing->contract->number,
                        ['controller' => 'Contracts', 'action' => 'view', $billing->contract->id]
                    ) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Service') ?></th>
                    <td><?= $billing->has('service') ? $this->Html->link(
                        $billing->service->name,
                        ['controller' => 'Services', 'action' => 'view', $billing->service->id]
                    ) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Text') ?></th>
                    <td><?= h($billing->text) ?></td>
                </tr>
                <tr>
                    <th><?= __('Quantity') ?></th>
                    <td><?= $this->Number->format($billing->quantity) ?></td>
                </tr>
                <tr>
                    <th><?= __('Price') ?></th>
                    <td><?= h($billing->price) ?><?= $billing->has('service') ?
                        ' (' . h($billing->service->price) . ')' : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Fixed Discount') ?></th>
                    <td><?= h($billing->fixed_discount) ?></td>
                </tr>
                <tr>
                    <th><?= __('Percentage Discount') ?></th>
                    <td><?= h($billing->percentage_discount) ?></td>
                </tr>
                <tr>
                    <th><?= __('Total Price') ?></th>
                    <td><?= Number::currency($billing->total_price) ?></td>
                </tr>
                <tr>
                    <th><?= __('Billing From') ?></th>
                    <td><?= h($billing->billing_from) ?></td>
                </tr>
                <tr>
                    <th><?= __('Billing Until') ?></th>
                    <td><?= h($billing->billing_until) ?></td>
                </tr>
                <tr>
                    <th><?= __('Active') ?></th>
                    <td><?= $billing->active ? __('Yes') : __('No'); ?></td>
                </tr>
                <tr>
                    <th><?= __('Separate Invoice') ?></th>
                    <td><?= $billing->separate_invoice ? __('Yes') : __('No'); ?></td>
                </tr>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= $this->Number->format($billing->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Created') ?></th>
                    <td><?= h($billing->created) ?></td>
                </tr>
                <tr>
                    <th><?= __('Created By') ?></th>
                    <td><?= $this->Number->format($billing->created_by) ?></td>
                </tr>
                <tr>
                    <th><?= __('Modified') ?></th>
                    <td><?= h($billing->modified) ?></td>
                </tr>
                <tr>
                    <th><?= __('Modified By') ?></th>
                    <td><?= $this->Number->format($billing->modified_by) ?></td>
                </tr>
            </table>
            <div class="text">
                <strong><?= __('Note') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($billing->note)); ?>
                </blockquote>
            </div>
            <?php
            // show three billing calculations from start and from end
            $bill_dates = [
                $billing->billing_from->day(1),
                $billing->billing_from->day(1)->addMonth(1),
                $billing->billing_from->day(1)->addMonth(2),
            ];
            if (isset($billing->billing_until)) {
                $bill_dates[] = $billing->billing_until->day(1);
                $bill_dates[] = $billing->billing_until->day(1)->addMonth(1);
                $bill_dates[] = $billing->billing_until->day(1)->addMonth(2);
            }
            ?>
            <div class="related">
                <h4><?= __('Billing Overview') ?></h4>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Billing Date') ?></th>
                            <th><?= __('Period From') ?></th>
                            <th><?= __('Period Until') ?></th>
                            <th><?= __('Price') ?></th>
                        </tr>
                        <?php foreach ($bill_dates as $bill_date) : ?>
                        <tr>
                            <td><?= $bill_date->subDay(1) ?></td>
                            <td><?= $bill_date->subMonth(1) ?></td>
                            <td><?= $bill_date->subDay(1) ?></td>
                            <td><?= Number::currency($billing->periodTotal(
                                $bill_date->subMonth(1),
                                $bill_date->subDay(1)
                            )) ?></td>
                        </tr>
                        <?php endforeach ?>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
