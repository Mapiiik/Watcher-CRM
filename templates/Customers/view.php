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
            <?= $this->Html->link(__('Edit Customer'), ['action' => 'edit', $customer->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete Customer'), ['action' => 'delete', $customer->id], ['confirm' => __('Are you sure you want to delete # {0}?', $customer->id), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Customers'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Customer'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-80">
        <div class="customers view content">
            <h3><?= h($customer->name) ?></h3>
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
                            <td><?= h($customer->ic) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Dic') ?></th>
                            <td><?= h($customer->dic) ?></td>
                        </tr>
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
                            <th><?= __('Tax') ?></th>
                            <td><?= $customer->has('tax') ? $this->Html->link($customer->tax->name, ['controller' => 'Taxes', 'action' => 'view', $customer->tax->id]) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Termination Date') ?></th>
                            <td><?= h($customer->termination_date) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Dealer') ?></th>
                            <td><?= $this->Number->format($customer->dealer) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Invoice Delivery') ?></th>
                            <td><?= $this->Number->format($customer->invoice_delivery) ?></td>
                        </tr>
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
                            <td><?= $this->Number->format($customer->created_by) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Modified') ?></th>
                            <td><?= h($customer->modified) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Modified By') ?></th>
                            <td><?= $this->Number->format($customer->modified_by) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="text">
                <strong><?= __('Note') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($customer->note)); ?>
                </blockquote>
            </div>
            <div class="text">
                <strong><?= __('Internal Note') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($customer->internal_note)); ?>
                </blockquote>
            </div>
            <div class="related">
                <h4><?= __('Related Emails') ?></h4>
                <?php if (!empty($customer->emails)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <th><?= __('Email') ?></th>
                            <th><?= __('Use For Billing') ?></th>
                            <th><?= __('Use For Outages') ?></th>
                            <th><?= __('Use For Commercial') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($customer->emails as $emails) : ?>
                        <tr>
                            <td><?= h($emails->id) ?></td>
                            <td><?= h($emails->email) ?></td>
                            <td><?= h($emails->use_for_billing) ?></td>
                            <td><?= h($emails->use_for_outages) ?></td>
                            <td><?= h($emails->use_for_commercial) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(__('View'), ['controller' => 'Emails', 'action' => 'view', $emails->id]) ?>
                                <?= $this->Html->link(__('Edit'), ['controller' => 'Emails', 'action' => 'edit', $emails->id]) ?>
                                <?= $this->Form->postLink(__('Delete'), ['controller' => 'Emails', 'action' => 'delete', $emails->id], ['confirm' => __('Are you sure you want to delete # {0}?', $emails->id)]) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <div class="related">
                <h4><?= __('Related Phones') ?></h4>
                <?php if (!empty($customer->phones)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <th><?= __('Phone') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($customer->phones as $phones) : ?>
                        <tr>
                            <td><?= h($phones->id) ?></td>
                            <td><?= h($phones->phone) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(__('View'), ['controller' => 'Phones', 'action' => 'view', $phones->id]) ?>
                                <?= $this->Html->link(__('Edit'), ['controller' => 'Phones', 'action' => 'edit', $phones->id]) ?>
                                <?= $this->Form->postLink(__('Delete'), ['controller' => 'Phones', 'action' => 'delete', $phones->id], ['confirm' => __('Are you sure you want to delete # {0}?', $phones->id)]) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <div class="related">
                <h4><?= __('Related Addresses') ?></h4>
                <?php if (!empty($customer->addresses)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
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
                            <th><?= __('Country Id') ?></th>
                            <th><?= __('Ruian Gid') ?></th>
                            <th><?= __('Gpsx') ?></th>
                            <th><?= __('Gpsy') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($customer->addresses as $addresses) : ?>
                        <tr>
                            <td><?= h($addresses->id) ?></td>
                            <td><?= h($addresses->type) ?></td>
                            <td><?= h($addresses->company) ?></td>
                            <td><?= h($addresses->title) ?></td>
                            <td><?= h($addresses->first_name) ?></td>
                            <td><?= h($addresses->last_name) ?></td>
                            <td><?= h($addresses->suffix) ?></td>
                            <td><?= h($addresses->street) ?></td>
                            <td><?= h($addresses->number) ?></td>
                            <td><?= h($addresses->city) ?></td>
                            <td><?= h($addresses->zip) ?></td>
                            <td><?= h($addresses->country_id) ?></td>
                            <td><?= h($addresses->ruian_gid) ?></td>
                            <td><?= h($addresses->gpsx) ?></td>
                            <td><?= h($addresses->gpsy) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(__('View'), ['controller' => 'Addresses', 'action' => 'view', $addresses->id]) ?>
                                <?= $this->Html->link(__('Edit'), ['controller' => 'Addresses', 'action' => 'edit', $addresses->id]) ?>
                                <?= $this->Form->postLink(__('Delete'), ['controller' => 'Addresses', 'action' => 'delete', $addresses->id], ['confirm' => __('Are you sure you want to delete # {0}?', $addresses->id)]) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <div class="related">
                <h4><?= __('Related Billings') ?></h4>
                <?php if (!empty($customer->billings)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <th><?= __('Customer Id') ?></th>
                            <th><?= __('Text') ?></th>
                            <th><?= __('Price') ?></th>
                            <th><?= __('Billing From') ?></th>
                            <th><?= __('Note') ?></th>
                            <th><?= __('Active') ?></th>
                            <th><?= __('Modified By') ?></th>
                            <th><?= __('Modified') ?></th>
                            <th><?= __('Created By') ?></th>
                            <th><?= __('Created') ?></th>
                            <th><?= __('Billing Until') ?></th>
                            <th><?= __('Separate') ?></th>
                            <th><?= __('Service Id') ?></th>
                            <th><?= __('Quantity') ?></th>
                            <th><?= __('Contract Id') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($customer->billings as $billings) : ?>
                        <tr>
                            <td><?= h($billings->id) ?></td>
                            <td><?= h($billings->customer_id) ?></td>
                            <td><?= h($billings->text) ?></td>
                            <td><?= h($billings->price) ?></td>
                            <td><?= h($billings->billing_from) ?></td>
                            <td><?= h($billings->note) ?></td>
                            <td><?= h($billings->active) ?></td>
                            <td><?= h($billings->modified_by) ?></td>
                            <td><?= h($billings->modified) ?></td>
                            <td><?= h($billings->created_by) ?></td>
                            <td><?= h($billings->created) ?></td>
                            <td><?= h($billings->billing_until) ?></td>
                            <td><?= h($billings->separate) ?></td>
                            <td><?= h($billings->service_id) ?></td>
                            <td><?= h($billings->quantity) ?></td>
                            <td><?= h($billings->contract_id) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(__('View'), ['controller' => 'Billings', 'action' => 'view', $billings->id]) ?>
                                <?= $this->Html->link(__('Edit'), ['controller' => 'Billings', 'action' => 'edit', $billings->id]) ?>
                                <?= $this->Form->postLink(__('Delete'), ['controller' => 'Billings', 'action' => 'delete', $billings->id], ['confirm' => __('Are you sure you want to delete # {0}?', $billings->id)]) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <div class="related">
                <h4><?= __('Related Borrowed Equipments') ?></h4>
                <?php if (!empty($customer->borrowed_equipments)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <th><?= __('Customer Id') ?></th>
                            <th><?= __('Contract Id') ?></th>
                            <th><?= __('Equipment Type Id') ?></th>
                            <th><?= __('Serial Number') ?></th>
                            <th><?= __('Created') ?></th>
                            <th><?= __('Created By') ?></th>
                            <th><?= __('Modified') ?></th>
                            <th><?= __('Modified By') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($customer->borrowed_equipments as $borrowedEquipments) : ?>
                        <tr>
                            <td><?= h($borrowedEquipments->id) ?></td>
                            <td><?= h($borrowedEquipments->customer_id) ?></td>
                            <td><?= h($borrowedEquipments->contract_id) ?></td>
                            <td><?= h($borrowedEquipments->equipment_type_id) ?></td>
                            <td><?= h($borrowedEquipments->serial_number) ?></td>
                            <td><?= h($borrowedEquipments->created) ?></td>
                            <td><?= h($borrowedEquipments->created_by) ?></td>
                            <td><?= h($borrowedEquipments->modified) ?></td>
                            <td><?= h($borrowedEquipments->modified_by) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(__('View'), ['controller' => 'BorrowedEquipments', 'action' => 'view', $borrowedEquipments->id]) ?>
                                <?= $this->Html->link(__('Edit'), ['controller' => 'BorrowedEquipments', 'action' => 'edit', $borrowedEquipments->id]) ?>
                                <?= $this->Form->postLink(__('Delete'), ['controller' => 'BorrowedEquipments', 'action' => 'delete', $borrowedEquipments->id], ['confirm' => __('Are you sure you want to delete # {0}?', $borrowedEquipments->id)]) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <div class="related">
                <h4><?= __('Related Contracts') ?></h4>
                <?php if (!empty($customer->contracts)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <th><?= __('Customer Id') ?></th>
                            <th><?= __('Installation Address Id') ?></th>
                            <th><?= __('Number') ?></th>
                            <th><?= __('Service Type Id') ?></th>
                            <th><?= __('Created') ?></th>
                            <th><?= __('Created By') ?></th>
                            <th><?= __('Modified') ?></th>
                            <th><?= __('Modified By') ?></th>
                            <th><?= __('Note') ?></th>
                            <th><?= __('Obligation Until') ?></th>
                            <th><?= __('Vip') ?></th>
                            <th><?= __('Installation Technician Id') ?></th>
                            <th><?= __('Brokerage Id') ?></th>
                            <th><?= __('Installation Date') ?></th>
                            <th><?= __('Access Description') ?></th>
                            <th><?= __('Valid From') ?></th>
                            <th><?= __('Valid Until') ?></th>
                            <th><?= __('Conclusion Date') ?></th>
                            <th><?= __('Number Of Amendments') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($customer->contracts as $contracts) : ?>
                        <tr>
                            <td><?= h($contracts->id) ?></td>
                            <td><?= h($contracts->customer_id) ?></td>
                            <td><?= h($contracts->installation_address_id) ?></td>
                            <td><?= h($contracts->number) ?></td>
                            <td><?= h($contracts->service_type_id) ?></td>
                            <td><?= h($contracts->created) ?></td>
                            <td><?= h($contracts->created_by) ?></td>
                            <td><?= h($contracts->modified) ?></td>
                            <td><?= h($contracts->modified_by) ?></td>
                            <td><?= h($contracts->note) ?></td>
                            <td><?= h($contracts->obligation_until) ?></td>
                            <td><?= h($contracts->vip) ?></td>
                            <td><?= h($contracts->installation_technician_id) ?></td>
                            <td><?= h($contracts->brokerage_id) ?></td>
                            <td><?= h($contracts->installation_date) ?></td>
                            <td><?= h($contracts->access_description) ?></td>
                            <td><?= h($contracts->valid_from) ?></td>
                            <td><?= h($contracts->valid_until) ?></td>
                            <td><?= h($contracts->conclusion_date) ?></td>
                            <td><?= h($contracts->number_of_amendments) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(__('View'), ['controller' => 'Contracts', 'action' => 'view', $contracts->id]) ?>
                                <?= $this->Html->link(__('Edit'), ['controller' => 'Contracts', 'action' => 'edit', $contracts->id]) ?>
                                <?= $this->Form->postLink(__('Delete'), ['controller' => 'Contracts', 'action' => 'delete', $contracts->id], ['confirm' => __('Are you sure you want to delete # {0}?', $contracts->id)]) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <div class="related">
                <h4><?= __('Related Ips') ?></h4>
                <?php if (!empty($customer->ips)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Ip') ?></th>
                            <th><?= __('Customer Id') ?></th>
                            <th><?= __('Note') ?></th>
                            <th><?= __('Contract Id') ?></th>
                            <th><?= __('Id') ?></th>
                            <th><?= __('Created') ?></th>
                            <th><?= __('Created By') ?></th>
                            <th><?= __('Modified') ?></th>
                            <th><?= __('Modified By') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($customer->ips as $ips) : ?>
                        <tr>
                            <td><?= h($ips->ip) ?></td>
                            <td><?= h($ips->customer_id) ?></td>
                            <td><?= h($ips->note) ?></td>
                            <td><?= h($ips->contract_id) ?></td>
                            <td><?= h($ips->id) ?></td>
                            <td><?= h($ips->created) ?></td>
                            <td><?= h($ips->created_by) ?></td>
                            <td><?= h($ips->modified) ?></td>
                            <td><?= h($ips->modified_by) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(__('View'), ['controller' => 'Ips', 'action' => 'view', $ips->id]) ?>
                                <?= $this->Html->link(__('Edit'), ['controller' => 'Ips', 'action' => 'edit', $ips->id]) ?>
                                <?= $this->Form->postLink(__('Delete'), ['controller' => 'Ips', 'action' => 'delete', $ips->id], ['confirm' => __('Are you sure you want to delete # {0}?', $ips->ip)]) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <div class="related">
                <h4><?= __('Related Label Customers') ?></h4>
                <?php if (!empty($customer->label_customers)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Label Id') ?></th>
                            <th><?= __('Customer Id') ?></th>
                            <th><?= __('Created') ?></th>
                            <th><?= __('Note') ?></th>
                            <th><?= __('Id') ?></th>
                            <th><?= __('Created By') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($customer->label_customers as $labelCustomers) : ?>
                        <tr>
                            <td><?= h($labelCustomers->label_id) ?></td>
                            <td><?= h($labelCustomers->customer_id) ?></td>
                            <td><?= h($labelCustomers->created) ?></td>
                            <td><?= h($labelCustomers->note) ?></td>
                            <td><?= h($labelCustomers->id) ?></td>
                            <td><?= h($labelCustomers->created_by) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(__('View'), ['controller' => 'LabelCustomers', 'action' => 'view', $labelCustomers->id]) ?>
                                <?= $this->Html->link(__('Edit'), ['controller' => 'LabelCustomers', 'action' => 'edit', $labelCustomers->id]) ?>
                                <?= $this->Form->postLink(__('Delete'), ['controller' => 'LabelCustomers', 'action' => 'delete', $labelCustomers->id], ['confirm' => __('Are you sure you want to delete # {0}?', $labelCustomers->id)]) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <div class="related">
                <h4><?= __('Related Logins') ?></h4>
                <?php if (!empty($customer->logins)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <th><?= __('Customer Id') ?></th>
                            <th><?= __('Login') ?></th>
                            <th><?= __('Password') ?></th>
                            <th><?= __('Rights') ?></th>
                            <th><?= __('Locked') ?></th>
                            <th><?= __('Last Granted') ?></th>
                            <th><?= __('Last Granted Ip') ?></th>
                            <th><?= __('Last Denied') ?></th>
                            <th><?= __('Last Denied Ip') ?></th>
                            <th><?= __('Modified By') ?></th>
                            <th><?= __('Modified') ?></th>
                            <th><?= __('Created By') ?></th>
                            <th><?= __('Created') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($customer->logins as $logins) : ?>
                        <tr>
                            <td><?= h($logins->id) ?></td>
                            <td><?= h($logins->customer_id) ?></td>
                            <td><?= h($logins->login) ?></td>
                            <td><?= h($logins->password) ?></td>
                            <td><?= h($logins->rights) ?></td>
                            <td><?= h($logins->locked) ?></td>
                            <td><?= h($logins->last_granted) ?></td>
                            <td><?= h($logins->last_granted_ip) ?></td>
                            <td><?= h($logins->last_denied) ?></td>
                            <td><?= h($logins->last_denied_ip) ?></td>
                            <td><?= h($logins->modified_by) ?></td>
                            <td><?= h($logins->modified) ?></td>
                            <td><?= h($logins->created_by) ?></td>
                            <td><?= h($logins->created) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(__('View'), ['controller' => 'Logins', 'action' => 'view', $logins->id]) ?>
                                <?= $this->Html->link(__('Edit'), ['controller' => 'Logins', 'action' => 'edit', $logins->id]) ?>
                                <?= $this->Form->postLink(__('Delete'), ['controller' => 'Logins', 'action' => 'delete', $logins->id], ['confirm' => __('Are you sure you want to delete # {0}?', $logins->id)]) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <div class="related">
                <h4><?= __('Related Removed Ips') ?></h4>
                <?php if (!empty($customer->removed_ips)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <th><?= __('Removed By') ?></th>
                            <th><?= __('Removed') ?></th>
                            <th><?= __('Ip') ?></th>
                            <th><?= __('Customer Id') ?></th>
                            <th><?= __('Note') ?></th>
                            <th><?= __('Contract Id') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($customer->removed_ips as $removedIps) : ?>
                        <tr>
                            <td><?= h($removedIps->id) ?></td>
                            <td><?= h($removedIps->removed_by) ?></td>
                            <td><?= h($removedIps->removed) ?></td>
                            <td><?= h($removedIps->ip) ?></td>
                            <td><?= h($removedIps->customer_id) ?></td>
                            <td><?= h($removedIps->note) ?></td>
                            <td><?= h($removedIps->contract_id) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(__('View'), ['controller' => 'RemovedIps', 'action' => 'view', $removedIps->id]) ?>
                                <?= $this->Html->link(__('Edit'), ['controller' => 'RemovedIps', 'action' => 'edit', $removedIps->id]) ?>
                                <?= $this->Form->postLink(__('Delete'), ['controller' => 'RemovedIps', 'action' => 'delete', $removedIps->id], ['confirm' => __('Are you sure you want to delete # {0}?', $removedIps->id)]) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <div class="related">
                <h4><?= __('Related Sold Equipments') ?></h4>
                <?php if (!empty($customer->sold_equipments)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <th><?= __('Customer Id') ?></th>
                            <th><?= __('Contract Id') ?></th>
                            <th><?= __('Equipment Type Id') ?></th>
                            <th><?= __('Serial Number') ?></th>
                            <th><?= __('Created') ?></th>
                            <th><?= __('Created By') ?></th>
                            <th><?= __('Modified') ?></th>
                            <th><?= __('Modified By') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($customer->sold_equipments as $soldEquipments) : ?>
                        <tr>
                            <td><?= h($soldEquipments->id) ?></td>
                            <td><?= h($soldEquipments->customer_id) ?></td>
                            <td><?= h($soldEquipments->contract_id) ?></td>
                            <td><?= h($soldEquipments->equipment_type_id) ?></td>
                            <td><?= h($soldEquipments->serial_number) ?></td>
                            <td><?= h($soldEquipments->created) ?></td>
                            <td><?= h($soldEquipments->created_by) ?></td>
                            <td><?= h($soldEquipments->modified) ?></td>
                            <td><?= h($soldEquipments->modified_by) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(__('View'), ['controller' => 'SoldEquipments', 'action' => 'view', $soldEquipments->id]) ?>
                                <?= $this->Html->link(__('Edit'), ['controller' => 'SoldEquipments', 'action' => 'edit', $soldEquipments->id]) ?>
                                <?= $this->Form->postLink(__('Delete'), ['controller' => 'SoldEquipments', 'action' => 'delete', $soldEquipments->id], ['confirm' => __('Are you sure you want to delete # {0}?', $soldEquipments->id)]) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <div class="related">
                <h4><?= __('Related Tasks') ?></h4>
                <?php if (!empty($customer->tasks)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <th><?= __('Task Type Id') ?></th>
                            <th><?= __('Subject') ?></th>
                            <th><?= __('Text') ?></th>
                            <th><?= __('Priority') ?></th>
                            <th><?= __('Customer Id') ?></th>
                            <th><?= __('Dealer Id') ?></th>
                            <th><?= __('Modified By') ?></th>
                            <th><?= __('Modified') ?></th>
                            <th><?= __('Created By') ?></th>
                            <th><?= __('Created') ?></th>
                            <th><?= __('Email') ?></th>
                            <th><?= __('Phone') ?></th>
                            <th><?= __('Task State Id') ?></th>
                            <th><?= __('Finish Date') ?></th>
                            <th><?= __('Start Date') ?></th>
                            <th><?= __('Estimated Date') ?></th>
                            <th><?= __('Critical Date') ?></th>
                            <th><?= __('Router Id') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($customer->tasks as $tasks) : ?>
                        <tr>
                            <td><?= h($tasks->id) ?></td>
                            <td><?= h($tasks->task_type_id) ?></td>
                            <td><?= h($tasks->subject) ?></td>
                            <td><?= h($tasks->text) ?></td>
                            <td><?= h($tasks->priority) ?></td>
                            <td><?= h($tasks->customer_id) ?></td>
                            <td><?= h($tasks->dealer_id) ?></td>
                            <td><?= h($tasks->modified_by) ?></td>
                            <td><?= h($tasks->modified) ?></td>
                            <td><?= h($tasks->created_by) ?></td>
                            <td><?= h($tasks->created) ?></td>
                            <td><?= h($tasks->email) ?></td>
                            <td><?= h($tasks->phone) ?></td>
                            <td><?= h($tasks->task_state_id) ?></td>
                            <td><?= h($tasks->finish_date) ?></td>
                            <td><?= h($tasks->start_date) ?></td>
                            <td><?= h($tasks->estimated_date) ?></td>
                            <td><?= h($tasks->critical_date) ?></td>
                            <td><?= h($tasks->router_id) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(__('View'), ['controller' => 'Tasks', 'action' => 'view', $tasks->id]) ?>
                                <?= $this->Html->link(__('Edit'), ['controller' => 'Tasks', 'action' => 'edit', $tasks->id]) ?>
                                <?= $this->Form->postLink(__('Delete'), ['controller' => 'Tasks', 'action' => 'delete', $tasks->id], ['confirm' => __('Are you sure you want to delete # {0}?', $tasks->id)]) ?>
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
