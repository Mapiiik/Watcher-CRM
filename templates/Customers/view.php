<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Customer $customer
 */
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
            <br>
            <?= $this->AuthLink->link(
                __('Print to PDF'),
                ['action' => 'print', $customer->id],
                ['class' => 'side-nav-item']
            ) ?>
            <br>
            <?= $this->AuthLink->link(
                __('List Access Credentials'),
                ['controller' => 'AccessCredentials', 'action' => 'index'],
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
        <?php if (!($this->getRequest()->getQuery('win-link') == 'true')) : ?>
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
        <?php endif; ?>
    </aside>
    <div class="column column-90">
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
                            <td><?= $customer->__isset('ic') ? (
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
                            <td><?= $customer->__isset('tax_rate') ? $this->Html->link(
                                $customer->tax_rate->name,
                                ['controller' => 'TaxRates', 'action' => 'view', $customer->tax_rate->id]
                            ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Dealer') ?></th>
                            <td><?= h($customer->getDealerStateName()) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Invoice Delivery Type') ?></th>
                            <td><?= h($customer->getInvoiceDeliveryTypeName()) ?></td>
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
                            <td><?= h($customer->id) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Created') ?></th>
                            <td><?= h($customer->created) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Created By') ?></th>
                            <td><?= $customer->__isset('creator') ? $this->Html->link(
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
                            <td><?= $customer->__isset('modifier') ? $this->Html->link(
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
            <div class="row">
                <div class="column">
                    <div class="text">
                        <strong><?= __('Note') ?></strong>
                        <blockquote>
                            <?= $this->Text->autoParagraph(h($customer->note)); ?>
                        </blockquote>
                    </div>
                </div>
                <div class="column">
                    <div class="text">
                        <strong><?= __('Internal Note') ?></strong>
                        <blockquote>
                            <?= $this->Text->autoParagraph(h($customer->internal_note)); ?>
                        </blockquote>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="column">
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
                <div class="column">
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
                            <td><?= h($login->getRightsName()) ?></td>
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
                            <th><?= __('Note') ?></th>
                            <th><?= __('RÚIAN') ?></th>
                            <th class="actions"><?= __('Map location') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($customer->addresses as $address) : ?>
                        <tr>
                            <td><?= h($address->getTypeName()) ?></td>
                            <td><?= h($address->company) ?></td>
                            <td><?= h($address->title) ?></td>
                            <td><?= h($address->first_name) ?></td>
                            <td><?= h($address->last_name) ?></td>
                            <td><?= h($address->suffix) ?></td>
                            <td><?= h($address->street) ?></td>
                            <td><?= h($address->number) ?></td>
                            <td><?= h($address->city) ?></td>
                            <td><?= h($address->zip) ?></td>
                            <td><?= $address->__isset('country') ? h($address->country->name) : '' ?></td>
                            <td><?= h($address->note) ?></td>
                            <td><?= $address->ruian_gid === null ?
                                '<span style="color: red;">' . __('unknown') . '</span>' :
                                $this->Number->format($address->ruian_gid)
                            ?></td>
                            <td class="actions">
                                <?= $address->__isset('gps_x') && $address->__isset('gps_y') ?
                                    '' : '<span style="color: red;">' . __('unknown') . '</span>' ?>
                                <?= $address->__isset('gps_x') && $address->__isset('gps_y') ? $this->Html->link(
                                    __('Google Maps'),
                                    'https://maps.google.com/maps?q='
                                        . h("{$address->gps_y},{$address->gps_x}"),
                                    ['target' => '_blank']
                                ) : '' ?>
                                <?= $address->__isset('gps_x') && $address->__isset('gps_y') ? $this->Html->link(
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
                    ['class' => 'button button-small float-right']
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
                            <th><?= __('Installation/Establishment Date') ?></th>
                            <th><?= __('Uninstallation/Cancellation Date') ?></th>
                            <th><?= __('Date of Termination of Services') ?></th>
                            <th><?= __('Subscriber Verification Code') ?></th>
                            <th><?= __('Note') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($customer->contracts as $contract) : ?>
                        <tr style="<?= $contract->style ?>">
                            <td><?= h($contract->number) ?></td>
                            <td><?=
                                $contract->__isset('contract_state') ? h($contract->contract_state->name) : '' ?></td>
                            <td><?= $contract->__isset('service_type') ? h($contract->service_type->name) : '' ?></td>
                            <td><?= $contract->__isset('installation_address') ?
                                h($contract->installation_address->full_address) : '' ?></td>
                            <td><?= $contract->vip ? __('Yes') : __('No'); ?></td>
                            <td><?= $contract->__isset('access_point') ? h($contract->access_point['name']) : '' ?></td>
                            <td><?= h($contract->installation_date) ?></td>
                            <td><?= h($contract->uninstallation_date) ?></td>
                            <td><?= h($contract->termination_date) ?></td>
                            <td><?= h($contract->subscriber_verification_code) ?></td>
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
                <?= $this->AuthLink->link(
                    __('New Billing'),
                    ['controller' => 'Billings', 'action' => 'add'],
                    ['class' => 'button button-small float-right win-link']
                ) ?>
                <h4 id="billings"><?= __('Related Billings') ?></h4>
                <?= $this->element('Contracts/Billings', [
                    'billings' => $customer->billings,
                    'contract_column' => true,
                ]) ?>
            </div>
            <div class="related">
                <?= $this->AuthLink->link(
                    __('New RADIUS Account'),
                    ['plugin' => 'Radius', 'controller' => 'Accounts', 'action' => 'add'],
                    ['class' => 'button button-small float-right win-link']
                ) ?>
                <h4 id="radius-accounts"><?= __('Related RADIUS Accounts') ?></h4>
                <?= $this->cell(
                    'Radius.Accounts',
                    [['Accounts.customer_id' => $customer->id]]
                ) ?>
            </div>
            <div class="row">
                <div class="column">
                    <div class="related">
                        <?= $this->AuthLink->link(
                            __('New Borrowed Equipment'),
                            ['controller' => 'BorrowedEquipments', 'action' => 'add'],
                            ['class' => 'button button-small float-right win-link']
                        ) ?>
                        <h4 id="borrowed-equipments"><?= __('Related Borrowed Equipments') ?></h4>
                        <?= $this->element('Contracts/BorrowedEquipments', [
                            'borrowed_equipments' => $customer->borrowed_equipments,
                            'contract_column' => true,
                        ]) ?>
                    </div>
                </div>
                <div class="column">
                    <div class="related">
                        <?= $this->AuthLink->link(
                            __('New Sold Equipment'),
                            ['controller' => 'SoldEquipments', 'action' => 'add'],
                            ['class' => 'button button-small float-right win-link']
                        ) ?>
                        <h4><?= __('Related Sold Equipments') ?></h4>
                        <?= $this->element('Contracts/SoldEquipments', [
                            'sold_equipments' => $customer->sold_equipments,
                            'contract_column' => true,
                        ]) ?>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="column">
                    <div class="related">
                        <?= $this->AuthLink->link(
                            __('New IP Address'),
                            ['controller' => 'Ips', 'action' => 'add'],
                            ['class' => 'button button-small float-right win-link']
                        ) ?>
                        <?= $this->AuthLink->link(
                            __('New IP Address From Range'),
                            ['controller' => 'Ips', 'action' => 'addFromRange'],
                            ['class' => 'button button-small float-right win-link']
                        ) ?>
                        <h4 id="ips"><?= __('Related IP Addresses') ?></h4>
                        <?= $this->element('Contracts/IpAddresses', [
                            'ip_addresses' => $customer->ips,
                            'contract_column' => true,
                        ]) ?>
                    </div>
                </div>
                <div class="column">
                    <div class="related">
                        <?= $this->AuthLink->link(
                            __('New IP Network'),
                            ['controller' => 'IpNetworks', 'action' => 'add'],
                            ['class' => 'button button-small float-right win-link']
                        ) ?>
                        <h4><?= __('Related IP Networks') ?></h4>
                        <?= $this->element('Contracts/IpNetworks', [
                            'ip_networks' => $customer->ip_networks,
                            'contract_column' => true,
                        ]) ?>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="column">
                    <div class="related">
                        <?= $this->AuthLink->link(
                            __('New Removed IP Address'),
                            ['controller' => 'RemovedIps', 'action' => 'add'],
                            ['class' => 'button button-small float-right win-link']
                        ) ?>
                        <h4><?= __('Related Removed IP Addresses') ?></h4>
                        <?= $this->element('Contracts/RemovedIpAddresses', [
                            'removed_ip_addresses' => $customer->removed_ips,
                            'contract_column' => true,
                        ]) ?>
                </div>
                </div>
                <div class="column">
                    <div class="related">
                        <?= $this->AuthLink->link(
                            __('New Removed IP Network'),
                            ['controller' => 'RemovedIpNetworks', 'action' => 'add'],
                            ['class' => 'button button-small float-right win-link']
                        ) ?>
                        <h4><?= __('Related Removed IP Networks') ?></h4>
                        <?= $this->element('Contracts/RemovedIpNetworks', [
                            'removed_ip_networks' => $customer->removed_ip_networks,
                            'contract_column' => true,
                        ]) ?>
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
                            <th><?= __('Number') ?></th>
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
                            <td><?= h($task->number) ?></td>
                            <td><?= $task->__isset('task_type') ? h($task->task_type->name) : '' ?></td>
                            <td><?= $task->__isset('task_state') ? h($task->task_state->name) : '' ?></td>
                            <td><?= h($task->subject) ?></td>
                            <td style="overflow-wrap: break-word; max-width: 600px;">
                                <?= nl2br($task->text ?? '') ?>
                            </td>
                            <td><?=
                                $task->__isset('contract') ? $this->Html->link(
                                    $task->contract->name,
                                    [
                                        'controller' => 'Contracts',
                                        'action' => 'view',
                                        $task->contract->id,
                                        'customer_id' => $task->contract->customer_id,
                                    ]
                                ) : '' ?>
                            </td>
                            <td><?= $task->__isset('dealer') ? h($task->dealer->name) : '' ?></td>
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
                                    ['confirm' => __('Are you sure you want to delete # {0}?', $task->number)]
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
