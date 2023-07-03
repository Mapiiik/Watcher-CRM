<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Customer $customer
 * @var \Cake\Collection\CollectionInterface|array<string> $documentTypes
 * @var \Cake\Collection\CollectionInterface|array<string> $invoice_delivery_types
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('View Customer'),
                ['action' => 'view', $customer->id],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->link(
                __('Edit Customer'),
                ['action' => 'edit', $customer->id],
                ['class' => 'side-nav-item']
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="contracts form content">
            <?= __('Customer No.') ?><h3><?= h($customer->number) ?></h3>
            <h5><?= h($customer->name) ?></h5>
            <div class="row">
                <div class="column">
                    <table>
                        <tr>
                            <th><?= __('Company') ?></th>
                            <td><?= h($customer->company) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Title') ?></th>
                            <td><?= h($customer->title) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('First Name') ?></th>
                            <td><?= h($customer->first_name) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Last Name') ?></th>
                            <td><?= h($customer->last_name) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Suffix') ?></th>
                            <td><?= h($customer->suffix) ?></td>
                        </tr>
                    </table>
                    <table>
                        <tr>
                            <th><?= __('Date Of Birth') ?></th>
                            <td><?= h($customer->date_of_birth) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Identity Card Number') ?></th>
                            <td><?= h($customer->identity_card_number) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Ic') ?></th>
                            <td><?= $customer->has('ic') ? (
                                h($customer->ic) . ' (' . ($customer->ic_verified ? __('OK') : __('Invalid')) . ')'
                            ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Dic') ?></th>
                            <td><?= h($customer->dic) ?></td>
                        </tr>
                    </table>
                    <table>
                        <tr>
                            <th><?= __('Www') ?></th>
                            <td><?= h($customer->www) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Bank Account') ?></th>
                            <td><?= h($customer->bank_account) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Bank Code') ?></th>
                            <td><?= h($customer->bank_code) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Bank Name') ?></th>
                            <td><?= h($customer->bank_name) ?></td>
                        </tr>
                    </table>
                </div>
                <div class="column">
                    <table>
                        <tr>
                            <th><?= __('Tax Rate') ?></th>
                            <td><?= $customer->has('tax_rate') ? $this->Html->link(
                                $customer->tax_rate->name,
                                ['controller' => 'TaxRates', 'action' => 'view', $customer->tax_rate->id]
                            ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Dealer') ?></th>
                            <td><?= $customer->getDealerState() ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Invoice Delivery Type') ?></th>
                            <td><?= h($invoice_delivery_types[$customer->invoice_delivery_type]) ?></td>
                        </tr>
                    </table>
                    <table>
                        <tr>
                            <th><?= __('Agrees to Processing of Personal Data') ?></th>
                            <td><?= $customer->agree_gdpr ? __('Yes') : __('No'); ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Agrees to Receive All Correspondence Related to Billing') ?></th>
                            <td><?= $customer->agree_mailing_billing ? __('Yes') : __('No'); ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Agrees to Receive Information About Outages And Malfunctions') ?></th>
                            <td><?= $customer->agree_mailing_outages ? __('Yes') : __('No'); ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Agrees to Receive Commercial Communications') ?></th>
                            <td><?= $customer->agree_mailing_commercial ? __('Yes') : __('No'); ?></td>
                        </tr>
                    </table>
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <td><?= $this->Number->format($customer->id) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Created') ?></th>
                            <td><?= h($customer->created) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Created By') ?></th>
                            <td><?= $customer->has('creator') ? $this->Html->link(
                                $customer->creator->username,
                                [
                                    'controller' => 'AppUsers',
                                    'action' => 'view',
                                    $customer->creator->id,
                                ]
                            ) : h($customer->created_by) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Modified') ?></th>
                            <td><?= h($customer->modified) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Modified By') ?></th>
                            <td><?= $customer->has('modifier') ? $this->Html->link(
                                $customer->modifier->username,
                                [
                                    'controller' => 'AppUsers',
                                    'action' => 'view',
                                    $customer->modifier->id,
                                ]
                            ) : h($customer->modified_by) ?></td>
                        </tr>

                    </table>
                </div>
            </div>
            <br />
            <?= $this->Form->create(null, [
                'type' => 'get',
                'valueSources' => ['query'],
                'target' => 'print',
                'url' => [
                    'action' => 'print',
                    $customer->id,
                    '_ext' => 'pdf',
                ],
            ]) ?>
            <fieldset>
                <legend><?= __('Print Documents') ?></legend>
                <div class="row">
                    <div class="column">
                        <?php
                        echo $this->Form->control('document_type', [
                            'label' => __('Document Type'),
                            'options' => $documentTypes,
                            'empty' => true,
                            'required' => true,
                        ]);
                        ?>
                    </div>
                    <div class="column">
                    </div>
                </div>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
