<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Customer $customer
 * @var string[]|\Cake\Collection\CollectionInterface $invoice_delivery_types
 * @var string[]|\Cake\Collection\CollectionInterface $address_types
 * @var string[]|\Cake\Collection\CollectionInterface $login_rights
 * @var string[]|\Cake\Collection\CollectionInterface $ip_address_types_of_use
 * @var string[]|\Cake\Collection\CollectionInterface $ip_network_types_of_use
 */

use Cake\I18n\Number;
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('Edit Customer'),
                ['action' => 'edit', $customer->id],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->postLink(
                __('Delete Customer'),
                ['action' => 'delete', $customer->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $customer->id), 'class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->link(__('List Customers'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->AuthLink->link(__('New Customer'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
            <br />
            <?= $this->AuthLink->link(
                __('Print to PDF'),
                ['action' => 'print', $customer->id],
                ['class' => 'side-nav-item']
            ) ?>
        </div>
        <br>
        <div class="side-labels">
            <h4 class="heading"><?= __('Labels') ?></h4>
            <?php foreach ($customer->customer_labels as $customer_label) : ?>
                <?= $this->Html->link(
                    $customer_label->label->name,
                    ['controller' => 'CustomerLabels', 'action' => 'view', $customer_label->id],
                    [
                        'class' => 'app-label win-link',
                        'title' => $customer_label->label->caption . PHP_EOL
                            . $customer_label->created . PHP_EOL
                            . $customer_label->note,
                        'style' => 'background-color: ' . $customer_label->label->color . ';',
                    ]
                ) ?>
            <?php endforeach ?>
        </div>
        <div class="side-nav">
            <?= $this->AuthLink->link(
                __('New Customer Label'),
                ['controller' => 'CustomerLabels', 'action' => 'add'],
                ['class' => 'side-nav-item win-link']
            ) ?>
        </div>
        <div class="side-nav" style="position: fixed; bottom: 1rem;">
            <h4 class="heading"><?= __('Sections') ?></h4>
            <?= $this->AuthLink->link(
                __('Customer'),
                ['action' => 'view', $customer->id, '#' => 'customer'],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->link(
                __('Logins'),
                ['action' => 'view', $customer->id, '#' => 'logins'],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->link(
                __('Addresses'),
                ['action' => 'view', $customer->id, '#' => 'addresses'],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->link(
                __('Contracts'),
                ['action' => 'view', $customer->id, '#' => 'contracts'],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->link(
                __('Billings'),
                ['action' => 'view', $customer->id, '#' => 'billings'],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->link(
                __('RADIUS Accounts'),
                ['action' => 'view', $customer->id, '#' => 'radius-accounts'],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->link(
                __('Equipments'),
                ['action' => 'view', $customer->id, '#' => 'borrowed-equipments'],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->link(
                __('IP Addresses'),
                ['action' => 'view', $customer->id, '#' => 'ips'],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->link(
                __('Invoices'),
                ['action' => 'view', $customer->id, '#' => 'invoices'],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->link(
                __('Tasks'),
                ['action' => 'view', $customer->id, '#' => 'tasks'],
                ['class' => 'side-nav-item']
            ) ?>
        </div>
    </aside>
    <div class="column-responsive column-90">
        <div class="customers view content">
            <?= $this->AuthLink->link(
                __('Print to PDF'),
                ['action' => 'print', $customer->id],
                ['class' => 'button float-right']
            ) ?>
            <a id="customer"></a>
            <?= __('Customer No.') ?><h3><?= h($customer->number) ?></h3>
            <h5><?= h($customer->name) ?></h5>
            <div class="row">
                <div class="column-responsive">
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
                <div class="column-responsive">
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
                            <th><?= __('Agree Gdpr') ?></th>
                            <td><?= $customer->agree_gdpr ? __('Yes') : __('No'); ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Agree Mailing Billing') ?></th>
                            <td><?= $customer->agree_mailing_billing ? __('Yes') : __('No'); ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Agree Mailing Outages') ?></th>
                            <td><?= $customer->agree_mailing_outages ? __('Yes') : __('No'); ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Agree Mailing Commercial') ?></th>
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
                                    'plugin' => 'CakeDC/Users',
                                    'controller' => 'Users',
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
                                    'plugin' => 'CakeDC/Users',
                                    'controller' => 'Users',
                                    'action' => 'view',
                                    $customer->modifier->id,
                                ]
                            ) : h($customer->modified_by) ?></td>
                        </tr>

                    </table>
                </div>
            </div>
            <div class="row">
                <div class="column-responsive">
                    <div class="text">
                        <strong><?= __('Note') ?></strong>
                        <blockquote>
                            <?= $this->Text->autoParagraph(h($customer->note)); ?>
                        </blockquote>
                    </div>
                </div>
                <div class="column-responsive">
                    <div class="text">
                        <strong><?= __('Internal Note') ?></strong>
                        <blockquote>
                            <?= $this->Text->autoParagraph(h($customer->internal_note)); ?>
                        </blockquote>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="column-responsive">
                    <div class="related">
                        <?= $this->AuthLink->link(
                            __('New Email'),
                            ['controller' => 'Emails', 'action' => 'add'],
                            ['class' => 'button button-small float-right win-link']
                        ) ?>
                        <h4><?= __('Emails') ?></h4>
                        <?php if (!empty($customer->emails)) : ?>
                        <div class="table-responsive">
                            <table>
                                <tr>
                                    <th><?= __('Email') ?></th>
                                    <th><?= __('Use For Billing') ?></th>
                                    <th><?= __('Use For Outages') ?></th>
                                    <th><?= __('Use For Commercial') ?></th>
                                    <th><?= __('Note') ?></th>
                                    <th class="actions"><?= __('Actions') ?></th>
                                </tr>
                                <?php foreach ($customer->emails as $email) : ?>
                                <tr>
                                    <td><?= $this->Html->link(h($email->email), 'mailto:' . $email->email) ?></td>
                                    <td><?= $email->use_for_billing ? __('Yes') : __('No'); ?></td>
                                    <td><?= $email->use_for_outages ? __('Yes') : __('No'); ?></td>
                                    <td><?= $email->use_for_commercial ? __('Yes') : __('No'); ?></td>
                                    <td><?= h($email->note) ?></td>
                                    <td class="actions">
                                        <?= $this->AuthLink->link(
                                            __('View'),
                                            ['controller' => 'Emails', 'action' => 'view', $email->id]
                                        ) ?>
                                        <?= $this->AuthLink->link(
                                            __('Edit'),
                                            ['controller' => 'Emails', 'action' => 'edit', $email->id],
                                            ['class' => 'win-link']
                                        ) ?>
                                        <?= $this->AuthLink->postLink(
                                            __('Delete'),
                                            ['controller' => 'Emails', 'action' => 'delete', $email->id],
                                            ['confirm' => __('Are you sure you want to delete # {0}?', $email->id)]
                                        ) ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="column-responsive">
                    <div class="related">
                        <?= $this->AuthLink->link(
                            __('New Phone'),
                            ['controller' => 'Phones', 'action' => 'add'],
                            ['class' => 'button button-small float-right win-link']
                        ) ?>
                        <h4><?= __('Phones') ?></h4>
                        <?php if (!empty($customer->phones)) : ?>
                        <div class="table-responsive">
                            <table>
                                <tr>
                                    <th><?= __('Phone') ?></th>
                                    <th><?= __('Use For Billing') ?></th>
                                    <th><?= __('Use For Outages') ?></th>
                                    <th><?= __('Use For Commercial') ?></th>
                                    <th><?= __('Note') ?></th>
                                    <th class="actions"><?= __('Actions') ?></th>
                                </tr>
                                <?php foreach ($customer->phones as $phone) : ?>
                                <tr>
                                    <td><?= h($phone->phone) ?></td>
                                    <td><?= $phone->use_for_billing ? __('Yes') : __('No'); ?></td>
                                    <td><?= $phone->use_for_outages ? __('Yes') : __('No'); ?></td>
                                    <td><?= $phone->use_for_commercial ? __('Yes') : __('No'); ?></td>
                                    <td><?= h($phone->note) ?></td>
                                    <td class="actions">
                                        <?= $this->AuthLink->link(
                                            __('View'),
                                            ['controller' => 'Phones', 'action' => 'view', $phone->id]
                                        ) ?>
                                        <?= $this->AuthLink->link(
                                            __('Edit'),
                                            ['controller' => 'Phones', 'action' => 'edit', $phone->id],
                                            ['class' => 'win-link']
                                        ) ?>
                                        <?= $this->AuthLink->postLink(
                                            __('Delete'),
                                            ['controller' => 'Phones', 'action' => 'delete', $phone->id],
                                            ['confirm' => __('Are you sure you want to delete # {0}?', $phone->id)]
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
            <div class="related">
                <?= $this->AuthLink->link(
                    __('New Login'),
                    ['controller' => 'Logins', 'action' => 'add'],
                    ['class' => 'button button-small float-right win-link']
                ) ?>
                <h4 id="logins"><?= __('Logins') ?></h4>
                <?php if (!empty($customer->logins)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Login') ?></th>
                            <th><?= __('Rights') ?></th>
                            <th><?= __('Locked') ?></th>
                            <th><?= __('Last Granted') ?></th>
                            <th><?= __('Last Granted IP Address') ?></th>
                            <th><?= __('Last Denied') ?></th>
                            <th><?= __('Last Denied IP Address') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($customer->logins as $login) : ?>
                        <tr>
                            <td><?= h($login->login) ?></td>
                            <td><?= $login_rights[$login->rights] ?></td>
                            <td><?= $login->locked ? __('Yes') : __('No'); ?></td>
                            <td><?= h($login->last_granted) ?></td>
                            <td><?= h($login->last_granted_ip) ?></td>
                            <td><?= h($login->last_denied) ?></td>
                            <td><?= h($login->last_denied_ip) ?></td>
                            <td class="actions">
                                <?= $this->AuthLink->link(
                                    __('View'),
                                    ['controller' => 'Logins', 'action' => 'view', $login->id]
                                ) ?>
                                <?= $this->AuthLink->link(
                                    __('Edit'),
                                    ['controller' => 'Logins', 'action' => 'edit', $login->id],
                                    ['class' => 'win-link']
                                ) ?>
                                <?= $this->AuthLink->postLink(
                                    __('Delete'),
                                    ['controller' => 'Logins', 'action' => 'delete', $login->id],
                                    ['confirm' => __('Are you sure you want to delete # {0}?', $login->id)]
                                ) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <div class="related">
                <?= $this->AuthLink->link(
                    __('New Address'),
                    ['controller' => 'Addresses', 'action' => 'add'],
                    ['class' => 'button button-small float-right win-link']
                ) ?>
                <h4 id="addresses"><?= __('Addresses') ?></h4>
                <?php if (!empty($customer->addresses)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Type') ?></th>
                            <th><?= __('Company') ?></th>
                            <th><?= __('Title') ?></th>
                            <th><?= __('First Name') ?></th>
                            <th><?= __('Last Name') ?></th>
                            <th><?= __('Suffix') ?></th>
                            <th><?= __('Street') ?></th>
                            <th><?= __('Number') ?></th>
                            <th><?= __('City') ?></th>
                            <th><?= __('Zip') ?></th>
                            <th><?= __('Country') ?></th>
                            <th><?= __('RÚIAN') ?></th>
                            <th class="actions"><?= __('Map location') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($customer->addresses as $address) : ?>
                        <tr>
                            <td><?= h($address_types[$address->type]) ?></td>
                            <td><?= h($address->company) ?></td>
                            <td><?= h($address->title) ?></td>
                            <td><?= h($address->first_name) ?></td>
                            <td><?= h($address->last_name) ?></td>
                            <td><?= h($address->suffix) ?></td>
                            <td><?= h($address->street) ?></td>
                            <td><?= h($address->number) ?></td>
                            <td><?= h($address->city) ?></td>
                            <td><?= h($address->zip) ?></td>
                            <td><?= $address->has('country') ? h($address->country->name) : '' ?></td>
                            <td><?= $address->has('ruian_gid') ?
                                $this->Number->format($address->ruian_gid) :
                                '<span style="color: red;">' . __('unknown') . '</span>'
                            ?></td>
                            <td class="actions">
                                <?= $address->has('gps_x') && $address->has('gps_y') ?
                                    '' : '<span style="color: red;">' . __('unknown') . '</span>' ?>
                                <?= $address->has('gps_x') && $address->has('gps_y') ? $this->Html->link(
                                    __('Google Maps'),
                                    'https://maps.google.com/maps?q='
                                        . h("{$address->gps_y},{$address->gps_x}"),
                                    ['target' => '_blank']
                                ) : '' ?>
                                <?= $address->has('gps_x') && $address->has('gps_y') ? $this->Html->link(
                                    __('Mapy.cz'),
                                    'https://mapy.cz/zakladni?source=coor&id='
                                        . h("{$address->gps_x},{$address->gps_y}"),
                                    ['target' => '_blank']
                                ) : ''?>
                            </td>
                            <td class="actions">
                                <?= $this->AuthLink->link(
                                    __('View'),
                                    ['controller' => 'Addresses', 'action' => 'view', $address->id]
                                ) ?>
                                <?= $this->AuthLink->link(
                                    __('Edit'),
                                    ['controller' => 'Addresses', 'action' => 'edit', $address->id],
                                    ['class' => 'win-link']
                                ) ?>
                                <?= $this->AuthLink->postLink(
                                    __('Delete'),
                                    ['controller' => 'Addresses', 'action' => 'delete', $address->id],
                                    ['confirm' => __('Are you sure you want to delete # {0}?', $address->id)]
                                ) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <div class="related">
                <?= $this->AuthLink->link(
                    __('New Contract'),
                    ['controller' => 'Contracts', 'action' => 'add'],
                    ['class' => 'button button-small float-right win-link']
                ) ?>
                <h4 id="contracts"><?= __('Contracts') ?></h4>
                <?php if (!empty($customer->contracts)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Number') ?></th>
                            <th><?= __('Contract State') ?></th>
                            <th><?= __('Service Type') ?></th>
                            <th><?= __('Installation Address') ?></th>
                            <th><?= __('Vip') ?></th>
                            <th><?= __('Access Point') ?></th>
                            <th><?= __('Installation Date') ?></th>
                            <th><?= __('Uninstallation Date') ?></th>
                            <th><?= __('Termination Date') ?></th>
                            <th><?= __('Note') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($customer->contracts as $contract) : ?>
                        <tr style="<?= $contract->style ?>">
                            <td><?= h($contract->number) ?></td>
                            <td><?=
                                $contract->has('contract_state') ? h($contract->contract_state->name) : '' ?></td>
                            <td><?= $contract->has('service_type') ? h($contract->service_type->name) : '' ?></td>
                            <td><?= $contract->has('installation_address') ?
                                h($contract->installation_address->full_address) : '' ?></td>
                            <td><?= $contract->vip ? __('Yes') : __('No'); ?></td>
                            <td><?= $contract->has('access_point') ? h($contract->access_point['name']) : '' ?></td>
                            <td><?= h($contract->installation_date) ?></td>
                            <td><?= h($contract->uninstallation_date) ?></td>
                            <td><?= h($contract->termination_date) ?></td>
                            <td><?= h($contract->note) ?></td>
                            <td class="actions">
                                <?= $this->AuthLink->link(
                                    __('View'),
                                    ['controller' => 'Contracts', 'action' => 'view', $contract->id]
                                ) ?>
                                <?= $this->AuthLink->link(
                                    __('Edit'),
                                    ['controller' => 'Contracts', 'action' => 'edit', $contract->id],
                                    ['class' => 'win-link']
                                ) ?>
                                <?= $this->AuthLink->postLink(
                                    __('Delete'),
                                    ['controller' => 'Contracts', 'action' => 'delete', $contract->id],
                                    ['confirm' => __('Are you sure you want to delete # {0}?', $contract->id)]
                                ) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <hr />
            <div class="related">
                <h4 id="billings"><?= __('Related Billings') ?></h4>
                <?php if (!empty($customer->billings)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Contract') ?></th>
                            <th><?= __('Service') ?></th>
                            <th><?= __('Text') ?></th>
                            <th><?= __('Quantity') ?></th>
                            <th><?= __('Price') ?></th>
                            <th><?= __('Fixed Discount') ?></th>
                            <th><?= __('Percentage Discount') ?></th>
                            <th><?= __('Total Price') ?></th>
                            <th><?= __('Billing From') ?></th>
                            <th><?= __('Billing Until') ?></th>
                            <th><?= __('Active') ?></th>
                            <th><?= __('Separate Invoice') ?></th>
                            <th><?= __('Note') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($customer->billings as $billing) : ?>
                        <tr style="<?= $billing->style ?>">
                            <td><?= $billing->has('contract') ?
                                $this->Html->link(
                                    $billing->contract->number,
                                    ['controller' => 'Contracts', 'action' => 'view', $billing->contract->id]
                                ) : '' ?></td>
                            <td><?= $billing->has('service') ? h($billing->service->name) : '' ?></td>
                            <td><?= h($billing->text) ?></td>
                            <td><?= h($billing->quantity) ?></td>
                            <td><?= h($billing->price) ?><?= $billing->has('service') ?
                                ' (' . h($billing->service->price) . ')' : '' ?></td>
                            <td><?= h($billing->fixed_discount) ?></td>
                            <td><?= h($billing->percentage_discount) ?></td>
                            <td><?= Number::currency($billing->total_price) ?></td>
                            <td><?= h($billing->billing_from) ?></td>
                            <td><?= h($billing->billing_until) ?></td>
                            <td><?= $billing->active ? __('Yes') : __('No'); ?></td>
                            <td><?= $billing->separate_invoice ? __('Yes') : __('No'); ?></td>
                            <td><?= h($billing->note) ?></td>
                            <td class="actions">
                                <?= $this->AuthLink->link(
                                    __('View'),
                                    ['controller' => 'Billings', 'action' => 'view', $billing->id]
                                ) ?>
                                <?= $this->AuthLink->link(
                                    __('Edit'),
                                    ['controller' => 'Billings', 'action' => 'edit', $billing->id],
                                    ['class' => 'win-link']
                                ) ?>
                                <?= $this->AuthLink->postLink(
                                    __('Delete'),
                                    ['controller' => 'Billings', 'action' => 'delete', $billing->id],
                                    ['confirm' => __('Are you sure you want to delete # {0}?', $billing->id)]
                                ) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <div class="related">
                <h4 id="radius-accounts"><?= __('Related RADIUS Accounts') ?></h4>
                <?= $this->cell(
                    'Radius.Accounts',
                    [['Accounts.customer_id' => $customer->id]]
                ) ?>
            </div>
            <div class="row">
                <div class="column-responsive">
                    <div class="related">
                        <h4 id="borrowed-equipments"><?= __('Related Borrowed Equipments') ?></h4>
                        <?php if (!empty($customer->borrowed_equipments)) : ?>
                        <div class="table-responsive">
                            <table>
                                <tr>
                                    <th><?= __('Contract') ?></th>
                                    <th><?= __('Equipment Type') ?></th>
                                    <th><?= __('Serial Number') ?></th>
                                    <th><?= __('Borrowed From') ?></th>
                                    <th><?= __('Borrowed Until') ?></th>
                                    <th class="actions"><?= __('Actions') ?></th>
                                </tr>
                                <?php foreach ($customer->borrowed_equipments as $borrowedEquipment) : ?>
                                <tr style="<?= $borrowedEquipment->style ?>">
                                    <td><?= $borrowedEquipment->has('contract') ?
                                        $this->Html->link(
                                            $borrowedEquipment->contract->number,
                                            [
                                                'controller' => 'Contracts',
                                                'action' => 'view',
                                                $borrowedEquipment->contract->id,
                                            ]
                                        ) : '' ?></td>
                                    <td><?= $borrowedEquipment->has('equipment_type') ?
                                        h($borrowedEquipment->equipment_type->name) : ''
                                    ?></td>
                                    <td><?= h($borrowedEquipment->serial_number) ?></td>
                                    <td><?= h($borrowedEquipment->borrowed_from) ?></td>
                                    <td><?= h($borrowedEquipment->borrowed_until) ?></td>
                                    <td class="actions">
                                        <?= $this->AuthLink->link(
                                            __('View'),
                                            [
                                                'controller' => 'BorrowedEquipments',
                                                'action' => 'view',
                                                $borrowedEquipment->id,
                                            ]
                                        ) ?>
                                        <?= $this->AuthLink->link(
                                            __('Edit'),
                                            [
                                                'controller' => 'BorrowedEquipments',
                                                'action' => 'edit',
                                                $borrowedEquipment->id,
                                            ],
                                            ['class' => 'win-link']
                                        ) ?>
                                        <?= $this->AuthLink->postLink(
                                            __('Delete'),
                                            [
                                                'controller' => 'BorrowedEquipments',
                                                'action' => 'delete',
                                                $borrowedEquipment->id,
                                            ],
                                            [
                                                'confirm' => __(
                                                    'Are you sure you want to delete # {0}?',
                                                    $borrowedEquipment->id
                                                ),
                                            ]
                                        ) ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="column-responsive">
                    <div class="related">
                        <h4><?= __('Related Sold Equipments') ?></h4>
                        <?php if (!empty($customer->sold_equipments)) : ?>
                        <div class="table-responsive">
                            <table>
                                <tr>
                                    <th><?= __('Contract') ?></th>
                                    <th><?= __('Equipment Type') ?></th>
                                    <th><?= __('Serial Number') ?></th>
                                    <th><?= __('Date Of Sale') ?></th>
                                    <th class="actions"><?= __('Actions') ?></th>
                                </tr>
                                <?php foreach ($customer->sold_equipments as $soldEquipment) : ?>
                                <tr style="<?= $soldEquipment->style ?>">
                                    <td><?= $soldEquipment->has('contract') ?
                                        $this->Html->link(
                                            $soldEquipment->contract->number,
                                            [
                                                'controller' => 'Contracts',
                                                'action' => 'view',
                                                $soldEquipment->contract->id,
                                            ]
                                        ) : '' ?></td>
                                    <td><?= $soldEquipment->has('equipment_type') ?
                                        $this->Html->link(
                                            $soldEquipment->equipment_type->name,
                                            [
                                                'controller' => 'EquipmentTypes',
                                                'action' => 'view',
                                                $soldEquipment->equipment_type->id,
                                            ]
                                        ) : '' ?></td>
                                    <td><?= h($soldEquipment->serial_number) ?></td>
                                    <td><?= h($soldEquipment->date_of_sale) ?></td>
                                    <td class="actions">
                                        <?= $this->AuthLink->link(
                                            __('View'),
                                            ['controller' => 'SoldEquipments', 'action' => 'view', $soldEquipment->id]
                                        ) ?>
                                        <?= $this->AuthLink->link(
                                            __('Edit'),
                                            ['controller' => 'SoldEquipments', 'action' => 'edit', $soldEquipment->id],
                                            ['class' => 'win-link']
                                        ) ?>
                                        <?= $this->AuthLink->postLink(
                                            __('Delete'),
                                            [
                                                'controller' => 'SoldEquipments',
                                                'action' => 'delete',
                                                $soldEquipment->id,
                                            ],
                                            [
                                                'confirm' => __(
                                                    'Are you sure you want to delete # {0}?',
                                                    $soldEquipment->id
                                                ),
                                            ]
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
            <div class="row">
                <div class="column-responsive">
                    <div class="related">
                        <h4 id="ips"><?= __('Related IP Addresses') ?></h4>
                        <?php if (!empty($customer->ips)) : ?>
                        <div class="table-responsive">
                            <table>
                                <tr>
                                    <th><?= __('Contract') ?></th>
                                    <th><?= __('IP Address') ?></th>
                                    <th><?= __('Type Of Use') ?></th>
                                    <th><?= __('Note') ?></th>
                                    <th><?= __('Status') ?></th>
                                    <th><?= __('Device') ?></th>
                                    <th><?= __('IP Address Range') ?></th>
                                    <th class="actions"><?= __('Actions') ?></th>
                                </tr>
                                <?php foreach ($customer->ips as $ip) : ?>
                                <tr style="<?= $ip->style ?>">
                                    <td><?= $ip->has('contract') ?
                                        $this->Html->link(
                                            $ip->contract->number,
                                            ['controller' => 'Contracts', 'action' => 'view', $ip->contract->id]
                                        ) : '' ?></td>
                                    <td><?= h($ip->ip) ?></td>
                                    <td><?= h($ip_address_types_of_use[$ip->type_of_use]) ?></td>
                                    <td><?= h($ip->note) ?></td>
                                    <td class="to-center"><?=
                                        $this->Html->image('ping/status.png.php?host=' . h($ip->ip), [
                                            'class' => 'ping-status',
                                            'onclick' => 'this.src = this.src',
                                        ]) ?></td>
                                    <td><?php
                                    if (isset($ip->routeros_devices)) {
                                        $device = $ip->routeros_devices->first();
                                        echo isset($device['id']) ?
                                            $this->Html->link(
                                                $device['system_description'],
                                                env('WATCHER_NMS_URL') . '/routeros-devices/view/' . $device['id'],
                                                ['target' => '_blank']
                                            ) . '<br>' : '';
                                        unset($device);
                                    }
                                    ?></td>
                                    <td><?php
                                    if (isset($ip->ip_address_ranges)) {
                                        $range = $ip->ip_address_ranges->first();
                                        echo isset($range['access_point']['id']) ?
                                            __('Access Point') . ': ' . $this->Html->link(
                                                $range['access_point']['name'],
                                                env('WATCHER_NMS_URL')
                                                    . '/access-points/view/' . $range['access_point']['id'],
                                                ['target' => '_blank']
                                            ) . '<br>' : '';
                                        echo isset($range['id']) ?
                                            __('Range') . ': ' . $this->Html->link(
                                                $range['name'],
                                                env('WATCHER_NMS_URL') . '/ip-address-ranges/view/' . $range['id'],
                                                ['target' => '_blank']
                                            ) . '<br>' : '';
                                            unset($range);
                                    }
                                    ?></td>
                                    <td class="actions">
                                        <?= $this->AuthLink->link(
                                            __('View'),
                                            ['controller' => 'Ips', 'action' => 'view', $ip->id]
                                        ) ?>
                                        <?= $this->AuthLink->link(
                                            __('Edit'),
                                            ['controller' => 'Ips', 'action' => 'edit', $ip->id],
                                            ['class' => 'win-link']
                                        ) ?>
                                        <?= $this->AuthLink->postLink(
                                            __('Delete'),
                                            ['controller' => 'Ips', 'action' => 'delete', $ip->id],
                                            ['confirm' => __('Are you sure you want to delete # {0}?', $ip->ip)]
                                        ) ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="column-responsive">
                    <div class="related">
                        <h4><?= __('Related IP Networks') ?></h4>
                        <?php if (!empty($customer->ip_networks)) : ?>
                        <div class="table-responsive">
                            <table>
                                <tr>
                                    <th><?= __('Contract') ?></th>
                                    <th><?= __('IP Network') ?></th>
                                    <th><?= __('Type Of Use') ?></th>
                                    <th><?= __('Note') ?></th>
                                    <th><?= __('IP Address Range') ?></th>
                                    <th class="actions"><?= __('Actions') ?></th>
                                </tr>
                                <?php foreach ($customer->ip_networks as $ipNetwork) : ?>
                                <tr style="<?= $ipNetwork->style ?>">
                                    <td><?= $ipNetwork->has('contract') ?
                                        $this->Html->link(
                                            $ipNetwork->contract->number,
                                            ['controller' => 'Contracts', 'action' => 'view', $ipNetwork->contract->id]
                                        ) : '' ?></td>
                                    <td><?= h($ipNetwork->ip_network) ?></td>
                                    <td><?= h($ip_network_types_of_use[$ipNetwork->type_of_use]) ?></td>
                                    <td><?= h($ipNetwork->note) ?></td>
                                    <td><?php
                                    if (isset($ipNetwork->ip_address_ranges)) {
                                        $range = $ipNetwork->ip_address_ranges->first();
                                        echo isset($range['access_point']['id']) ?
                                            __('Access Point') . ': ' . $this->Html->link(
                                                $range['access_point']['name'],
                                                env('WATCHER_NMS_URL')
                                                    . '/access-points/view/' . $range['access_point']['id'],
                                                ['target' => '_blank']
                                            ) . '<br>' : '';
                                        echo isset($range['id']) ?
                                            __('Range') . ': ' . $this->Html->link(
                                                $range['name'],
                                                env('WATCHER_NMS_URL') . '/ip-address-ranges/view/' . $range['id'],
                                                ['target' => '_blank']
                                            ) . '<br>' : '';
                                        unset($range);
                                    }
                                    ?></td>
                                    <td class="actions">
                                        <?= $this->AuthLink->link(
                                            __('View'),
                                            ['controller' => 'IpNetworks', 'action' => 'view', $ipNetwork->id]
                                        ) ?>
                                        <?= $this->AuthLink->link(
                                            __('Edit'),
                                            ['controller' => 'IpNetworks', 'action' => 'edit', $ipNetwork->id],
                                            ['class' => 'win-link']
                                        ) ?>
                                        <?= $this->AuthLink->postLink(
                                            __('Delete'),
                                            ['controller' => 'IpNetworks', 'action' => 'delete', $ipNetwork->id],
                                            [
                                                'confirm' => __(
                                                    'Are you sure you want to delete # {0}?',
                                                    $ipNetwork->ip_network
                                                ),
                                            ]
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
            <div class="row">
                <div class="column-responsive">
                    <div class="related">
                        <h4><?= __('Related Removed IP Addresses') ?></h4>
                        <?php if (!empty($customer->removed_ips)) : ?>
                        <div class="table-responsive">
                            <table>
                                <tr>
                                    <th><?= __('Contract') ?></th>
                                    <th><?= __('IP Address') ?></th>
                                    <th><?= __('Type Of Use') ?></th>
                                    <th><?= __('Note') ?></th>
                                    <th><?= __('Removed') ?></th>
                                    <th class="actions"><?= __('Actions') ?></th>
                                </tr>
                                <?php foreach ($customer->removed_ips as $removedIp) : ?>
                                <tr style="<?= $removedIp->style ?>">
                                    <td><?= $removedIp->has('contract') ?
                                        $this->Html->link(
                                            $removedIp->contract->number,
                                            ['controller' => 'Contracts', 'action' => 'view', $removedIp->contract->id]
                                        ) : '' ?></td>
                                    <td><?= h($removedIp->ip) ?></td>
                                    <td><?= h($ip_address_types_of_use[$removedIp->type_of_use]) ?></td>
                                    <td><?= h($removedIp->note) ?></td>
                                    <td><?= h($removedIp->removed) ?></td>
                                    <td class="actions">
                                        <?= $this->AuthLink->link(
                                            __('View'),
                                            ['controller' => 'RemovedIps', 'action' => 'view', $removedIp->id]
                                        ) ?>
                                        <?= $this->AuthLink->link(
                                            __('Edit'),
                                            ['controller' => 'RemovedIps', 'action' => 'edit', $removedIp->id],
                                            ['class' => 'win-link']
                                        ) ?>
                                        <?= $this->AuthLink->postLink(
                                            __('Delete'),
                                            ['controller' => 'RemovedIps', 'action' => 'delete', $removedIp->id],
                                            ['confirm' => __('Are you sure you want to delete # {0}?', $removedIp->id)]
                                        ) ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="column-responsive">
                    <div class="related">
                        <h4><?= __('Related Removed IP Networks') ?></h4>
                        <?php if (!empty($customer->removed_ip_networks)) : ?>
                        <div class="table-responsive">
                            <table>
                                <tr>
                                    <th><?= __('Contract') ?></th>
                                    <th><?= __('IP Network') ?></th>
                                    <th><?= __('Type Of Use') ?></th>
                                    <th><?= __('Note') ?></th>
                                    <th><?= __('Removed') ?></th>
                                    <th class="actions"><?= __('Actions') ?></th>
                                </tr>
                                <?php foreach ($customer->removed_ip_networks as $removedIpNetwork) : ?>
                                <tr style="<?= $removedIpNetwork->style ?>">
                                    <td><?= $removedIpNetwork->has('contract') ?
                                        $this->Html->link(
                                            $removedIpNetwork->contract->number,
                                            [
                                                'controller' => 'Contracts',
                                                'action' => 'view',
                                                $removedIpNetwork->contract->id,
                                            ]
                                        ) : '' ?></td>
                                    <td><?= h($removedIpNetwork->ip_network) ?></td>
                                    <td><?= h($ip_network_types_of_use[$removedIpNetwork->type_of_use]) ?></td>
                                    <td><?= h($removedIpNetwork->note) ?></td>
                                    <td><?= h($removedIpNetwork->removed) ?></td>
                                    <td class="actions">
                                        <?= $this->AuthLink->link(
                                            __('View'),
                                            [
                                                'controller' => 'RemovedIpNetworks',
                                                'action' => 'view',
                                                $removedIpNetwork->id,
                                            ]
                                        ) ?>
                                        <?= $this->AuthLink->link(
                                            __('Edit'),
                                            [
                                                'controller' => 'RemovedIpNetworks',
                                                'action' => 'edit',
                                                $removedIpNetwork->id,
                                            ],
                                            ['class' => 'win-link']
                                        ) ?>
                                        <?= $this->AuthLink->postLink(
                                            __('Delete'),
                                            [
                                                'controller' => 'RemovedIpNetworks',
                                                'action' => 'delete',
                                                $removedIpNetwork->id,
                                            ],
                                            [
                                                'confirm' => __(
                                                    'Are you sure you want to delete # {0}?',
                                                    $removedIpNetwork->id
                                                ),
                                            ]
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
            <hr />
            <div class="related">
                <?= $this->AuthLink->postLink(
                    __('Unblock Debtor'),
                    [
                        'plugin' => 'BookkeepingPohoda',
                        'controller' => 'Debtors',
                        'action' => 'unblock',
                        $customer->id,
                    ],
                    [
                        'class' => 'button button-small float-right',
                        'confirm' => __('Are you sure you want to unblock # {0}?', $customer->id),
                    ]
                ) ?>
                <?= $this->AuthLink->postLink(
                    __('Block Debtor'),
                    [
                        'plugin' => 'BookkeepingPohoda',
                        'controller' => 'Debtors',
                        'action' => 'block',
                        $customer->id,
                    ],
                    [
                        'class' => 'button button-small float-right',
                        'confirm' => __('Are you sure you want to block # {0}?', $customer->id),
                    ]
                ) ?>
                <h4 id="invoices"><?= __('Invoices') ?></h4>
                <?= $this->cell(
                    'BookkeepingPohoda.Invoices',
                    [['Invoices.customer_id' => $customer->id]],
                    ['show_customers' => false]
                ) ?>
            </div>
            <div class="related">
                <?= $this->AuthLink->link(
                    __('New Task'),
                    ['controller' => 'Tasks', 'action' => 'add'],
                    ['class' => 'button button-small float-right win-link']
                ) ?>
                <h4 id="tasks"><?= __('Tasks') ?></h4>
                <?php if (!empty($customer->tasks)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Task Type') ?></th>
                            <th><?= __('Task State') ?></th>
                            <th><?= __('Subject') ?></th>
                            <th><?= __('Text') ?></th>
                            <th><?= __('Contract') ?></th>
                            <th><?= __('Dealer') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($customer->tasks as $task) : ?>
                        <tr style="<?= $task->style ?>">
                            <td><?= $task->has('task_type') ? h($task->task_type->name) : '' ?></td>
                            <td><?= $task->has('task_state') ? h($task->task_state->name) : '' ?></td>
                            <td><?= h($task->subject) ?></td>
                            <td><?= nl2br($task->text ?? '') ?></td>
                            <td><?=
                                $task->has('contract') ? $this->Html->link(
                                    $task->contract->name,
                                    [
                                        'controller' => 'Contracts',
                                        'action' => 'view',
                                        $task->contract->id,
                                        'customer_id' => $task->contract->customer_id,
                                    ]
                                ) : '' ?>
                            </td>
                            <td><?= $task->has('dealer') ? h($task->dealer->name) : '' ?></td>
                            <td class="actions">
                                <?= $this->AuthLink->link(
                                    __('View'),
                                    ['controller' => 'Tasks', 'action' => 'view', $task->id]
                                ) ?>
                                <?= $this->AuthLink->link(
                                    __('Edit'),
                                    ['controller' => 'Tasks', 'action' => 'edit', $task->id],
                                    ['class' => 'win-link']
                                ) ?>
                                <?= $this->AuthLink->postLink(
                                    __('Delete'),
                                    ['controller' => 'Tasks', 'action' => 'delete', $task->id],
                                    ['confirm' => __('Are you sure you want to delete # {0}?', $task->id)]
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
