<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Billing $billing
 * @var \Cake\Collection\CollectionInterface|array<string> $services
 */
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
    <div class="column column-90">
        <div class="billings view content">
            <h3><?= h($billing->name) ?></h3>
            <div class="row">
                <div class="column">
                    <table>
                        <tr>
                            <th><?= __('Customer') ?></th>
                            <td><?= $billing->__isset('customer') ? $this->Html->link(
                                $billing->customer->name,
                                ['controller' => 'Customers', 'action' => 'view', $billing->customer->id]
                            ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Customer Number') ?></th>
                            <td><?= $billing->__isset('customer') ? h($billing->customer->number) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Contract') ?></th>
                            <td><?= $billing->__isset('contract') ? $this->Html->link(
                                $billing->contract->number ?? '--',
                                ['controller' => 'Contracts', 'action' => 'view', $billing->contract->id]
                            ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Service') ?></th>
                            <td><?= $billing->__isset('service') ? $this->Html->link(
                                $billing->service->name,
                                ['controller' => 'Services', 'action' => 'view', $billing->service->id]
                            ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Text') ?></th>
                            <td><?= h($billing->text) ?></td>
                        </tr>
                    </table>
                    <table>
                        <tr>
                            <th><?= __('Billing From') ?></th>
                            <td><?= h($billing->billing_from) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Billing Until') ?></th>
                            <td><?= h($billing->billing_until) ?></td>
                        </tr>
                    </table>
                    <table>
                        <tr>
                            <th><?= __('Active') ?></th>
                            <td><?= $billing->active ? __('Yes') : __('No'); ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Separate Invoice') ?></th>
                            <td><?= $billing->separate_invoice ? __('Yes') : __('No'); ?></td>
                        </tr>
                    </table>
                </div>
                <div class="column">
                    <table>
                        <tr>
                            <th><?= __('Quantity') ?></th>
                            <td><?= $this->Number->format($billing->quantity) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Price') ?></th>
                            <td><?= h($billing->price) ?><?= $billing->__isset('service') ?
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
                            <td><?= $this->Number->currency($billing->total_price->toString()) ?></td>
                        </tr>
                    </table>
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <td><?= h($billing->id) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Created') ?></th>
                            <td><?= h($billing->created) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Created By') ?></th>
                            <td><?= $billing->__isset('creator') ? $this->Html->link(
                                $billing->creator->username,
                                [
                                    'controller' => 'AppUsers',
                                    'action' => 'view',
                                    $billing->creator->id,
                                ]
                            ) : h($billing->created_by) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Modified') ?></th>
                            <td><?= h($billing->modified) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Modified By') ?></th>
                            <td><?= $billing->__isset('modifier') ? $this->Html->link(
                                $billing->modifier->username,
                                [
                                    'controller' => 'AppUsers',
                                    'action' => 'view',
                                    $billing->modifier->id,
                                ]
                            ) : h($billing->modified_by) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="text">
                <strong><?= __('Note') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($billing->note)); ?>
                </blockquote>
            </div>
        </div>
        <hr />
        <div class="billings form content">
            <?= $this->Form->create($billing, ['method' => 'post']) ?>
            <fieldset>
                <legend><?= __('New Service') ?></legend>
                <?php
                echo $this->Form->control('service_id', [
                    'label' => __('Service'),
                    'options' => $services,
                    'empty' => true,
                    'required' => true,
                ]);
                echo $this->Form->control('billing_from', [
                    'empty' => true,
                    'value' => '',
                    'required' => true,
                ]);
                ?>
            </fieldset>
            <fieldset>
                <?= $this->Form->control('price', [
                    'label' => __('Price'),
                    'type' => 'number',
                ]) ?>
                <?= $this->Form->control('fixed_discount', [
                    'label' => __('Fixed Discount'),
                    'type' => 'number',
                ]) ?>
                <?= $this->Form->control('percentage_discount', [
                    'label' => __('Percentage Discount'),
                    'type' => 'number',
                ]) ?>
            </fieldset>
            <?= $this->Form->button(
                __('Submit'),
                [
                    'confirm' => __(
                        'Do you really want to change the original service to the new service'
                        . ' for the billing listed above from the date set?'
                    ),
                ]
            ) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
