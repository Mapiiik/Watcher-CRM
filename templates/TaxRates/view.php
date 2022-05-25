<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\TaxRate $taxRate
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(
                __('Edit Tax Rate'),
                ['action' => 'edit', $taxRate->id],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->Form->postLink(
                __('Delete Tax Rate'),
                ['action' => 'delete', $taxRate->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $taxRate->id), 'class' => 'side-nav-item']
            ) ?>
            <?= $this->Html->link(__('List Tax Rates'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Tax Rate'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-90">
        <div class="taxRates view content">
            <h3><?= h($taxRate->name) ?></h3>
            <table>
                <tr>
                    <th><?= __('Name') ?></th>
                    <td><?= h($taxRate->name) ?></td>
                </tr>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= $this->Number->format($taxRate->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Vat Rate') ?></th>
                    <td><?= $this->Number->format($taxRate->vat_rate) ?></td>
                </tr>
                <tr>
                    <th><?= __('Reverse Charge') ?></th>
                    <td><?= $taxRate->reverse_charge ? __('Yes') : __('No'); ?></td>
                </tr>
            </table>
            <div class="related">
                <h4><?= __('Related Customers') ?></h4>
                <?php if (!empty($taxRate->customers)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <th><?= __('Dealer') ?></th>
                            <th><?= __('Title') ?></th>
                            <th><?= __('First Name') ?></th>
                            <th><?= __('Last Name') ?></th>
                            <th><?= __('Suffix') ?></th>
                            <th><?= __('Company') ?></th>
                            <th><?= __('Tax Rate Id') ?></th>
                            <th><?= __('Bank Name') ?></th>
                            <th><?= __('Bank Account') ?></th>
                            <th><?= __('Bank Code') ?></th>
                            <th><?= __('Modified By') ?></th>
                            <th><?= __('Modified') ?></th>
                            <th><?= __('Created By') ?></th>
                            <th><?= __('Created') ?></th>
                            <th><?= __('Ic') ?></th>
                            <th><?= __('Dic') ?></th>
                            <th><?= __('Www') ?></th>
                            <th><?= __('Internal Note') ?></th>
                            <th><?= __('Invoice Delivery Type') ?></th>
                            <th><?= __('Note') ?></th>
                            <th><?= __('Identity Card Number') ?></th>
                            <th><?= __('Date Of Birth') ?></th>
                            <th><?= __('Termination Date') ?></th>
                            <th><?= __('Agree Gdpr') ?></th>
                            <th><?= __('Agree Mailing Outages') ?></th>
                            <th><?= __('Agree Mailing Commercial') ?></th>
                            <th><?= __('Agree Mailing Billing') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($taxRate->customers as $customers) : ?>
                        <tr>
                            <td><?= h($customers->id) ?></td>
                            <td><?= h($customers->dealer) ?></td>
                            <td><?= h($customers->title) ?></td>
                            <td><?= h($customers->first_name) ?></td>
                            <td><?= h($customers->last_name) ?></td>
                            <td><?= h($customers->suffix) ?></td>
                            <td><?= h($customers->company) ?></td>
                            <td><?= h($customers->tax_rate_id) ?></td>
                            <td><?= h($customers->bank_name) ?></td>
                            <td><?= h($customers->bank_account) ?></td>
                            <td><?= h($customers->bank_code) ?></td>
                            <td><?= h($customers->modified_by) ?></td>
                            <td><?= h($customers->modified) ?></td>
                            <td><?= h($customers->created_by) ?></td>
                            <td><?= h($customers->created) ?></td>
                            <td><?= h($customers->ic) ?></td>
                            <td><?= h($customers->dic) ?></td>
                            <td><?= h($customers->www) ?></td>
                            <td><?= h($customers->internal_note) ?></td>
                            <td><?= h($customers->invoice_delivery_type) ?></td>
                            <td><?= h($customers->note) ?></td>
                            <td><?= h($customers->identity_card_number) ?></td>
                            <td><?= h($customers->date_of_birth) ?></td>
                            <td><?= h($customers->termination_date) ?></td>
                            <td><?= h($customers->agree_gdpr) ?></td>
                            <td><?= h($customers->agree_mailing_outages) ?></td>
                            <td><?= h($customers->agree_mailing_commercial) ?></td>
                            <td><?= h($customers->agree_mailing_billing) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(
                                    __('View'),
                                    ['controller' => 'Customers', 'action' => 'view', $customers->id]
                                ) ?>
                                <?= $this->Html->link(
                                    __('Edit'),
                                    ['controller' => 'Customers', 'action' => 'edit', $customers->id]
                                ) ?>
                                <?= $this->Form->postLink(
                                    __('Delete'),
                                    ['controller' => 'Customers', 'action' => 'delete', $customers->id],
                                    ['confirm' => __('Are you sure you want to delete # {0}?', $customers->id)]
                                ) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
