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
                            <td><?= $customer->dealer ? __('Yes') : __('No'); ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Invoice Delivery Type') ?></th>
                            <td><?= h($invoice_delivery_types[$customer->invoice_delivery_type]) ?></td>
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
                <?= $this->Html->link(__('New Email'), ['controller' => 'Emails', 'action' => 'add'], ['class' => 'button button-small float-right']) ?>
                <h4><?= __('Emails') ?></h4>
                <?php if (!empty($customer->emails)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Email') ?></th>
                            <th><?= __('Use For Billing') ?></th>
                            <th><?= __('Use For Outages') ?></th>
                            <th><?= __('Use For Commercial') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($customer->emails as $emails) : ?>
                        <tr>
                            <td><?= h($emails->email) ?></td>
                            <td><?= $emails->use_for_billing ? __('Yes') : __('No'); ?></td>
                            <td><?= $emails->use_for_outages ? __('Yes') : __('No'); ?></td>
                            <td><?= $emails->use_for_commercial ? __('Yes') : __('No'); ?></td>
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
                <?= $this->Html->link(__('New Phone'), ['controller' => 'Phones', 'action' => 'add'], ['class' => 'button button-small float-right']) ?>
                <h4><?= __('Phones') ?></h4>
                <?php if (!empty($customer->phones)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Phone') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($customer->phones as $phones) : ?>
                        <tr>
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
                <?= $this->Html->link(__('New Address'), ['controller' => 'Addresses', 'action' => 'add'], ['class' => 'button button-small float-right']) ?>
                <h4><?= __('Addresses') ?></h4>
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
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($customer->addresses as $addresses) : ?>
                        <tr>
                            <td><?= h($address_types[$addresses->type]) ?></td>
                            <td><?= h($addresses->company) ?></td>
                            <td><?= h($addresses->title) ?></td>
                            <td><?= h($addresses->first_name) ?></td>
                            <td><?= h($addresses->last_name) ?></td>
                            <td><?= h($addresses->suffix) ?></td>
                            <td><?= h($addresses->street) ?></td>
                            <td><?= h($addresses->number) ?></td>
                            <td><?= h($addresses->city) ?></td>
                            <td><?= h($addresses->zip) ?></td>
                            <td><?= $addresses->has('country') ? h($addresses->country->name) : '' ?></td>
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
                <?= $this->Html->link(__('New Login'), ['controller' => 'Logins', 'action' => 'add'], ['class' => 'button button-small float-right']) ?>
                <h4><?= __('Logins') ?></h4>
                <?php if (!empty($customer->logins)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Login') ?></th>
                            <th><?= __('Rights') ?></th>
                            <th><?= __('Locked') ?></th>
                            <th><?= __('Last Granted') ?></th>
                            <th><?= __('Last Granted Ip') ?></th>
                            <th><?= __('Last Denied') ?></th>
                            <th><?= __('Last Denied Ip') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($customer->logins as $logins) : ?>
                        <tr>
                            <td><?= h($logins->login) ?></td>
                            <td><?= $login_rights[$logins->rights] ?></td>
                            <td><?= $logins->locked ? __('Yes') : __('No'); ?></td>
                            <td><?= h($logins->last_granted) ?></td>
                            <td><?= h($logins->last_granted_ip) ?></td>
                            <td><?= h($logins->last_denied) ?></td>
                            <td><?= h($logins->last_denied_ip) ?></td>
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
                <?= $this->Html->link(__('New Contract'), ['controller' => 'Contracts', 'action' => 'add'], ['class' => 'button button-small float-right']) ?>
                <h4><?= __('Contracts') ?></h4>
                <?php if (!empty($customer->contracts)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Service Type') ?></th>
                            <th><?= __('Number') ?></th>
                            <th><?= __('Installation Address') ?></th>
                            <th><?= __('Conclusion Date') ?></th>
                            <th><?= __('Number Of Amendments') ?></th>
                            <th><?= __('Valid From') ?></th>
                            <th><?= __('Valid Until') ?></th>
                            <th><?= __('Obligation Until') ?></th>
                            <th><?= __('Vip') ?></th>
                            <th><?= __('Installation Date') ?></th>
                            <th><?= __('Note') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($customer->contracts as $contracts) : ?>
                        <tr>
                            <td><?= $contracts->has('service_type') ? h($contracts->service_type->name) : '' ?></td>
                            <td><?= h($contracts->number) ?></td>
                            <td><?= $contracts->has('installation_address') ? h($contracts->installation_address->address) : '' ?></td>
                            <td><?= h($contracts->conclusion_date) ?></td>
                            <td><?= h($contracts->number_of_amendments) ?></td>
                            <td><?= h($contracts->valid_from) ?></td>
                            <td><?= h($contracts->valid_until) ?></td>
                            <td><?= h($contracts->obligation_until) ?></td>
                            <td><?= $contracts->vip ? __('Yes') : __('No'); ?></td>
                            <td><?= h($contracts->installation_date) ?></td>
                            <td><?= h($contracts->note) ?></td>
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
                <?= $this->Html->link(__('New Task'), ['controller' => 'Tasks', 'action' => 'add'], ['class' => 'button button-small float-right']) ?>
                <h4><?= __('Related Tasks') ?></h4>
                <?php if (!empty($customer->tasks)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Task Type Id') ?></th>
                            <th><?= __('Task State Id') ?></th>
                            <th><?= __('Priority') ?></th>
                            <th><?= __('Subject') ?></th>
                            <th><?= __('Text') ?></th>
                            <th><?= __('Dealer Id') ?></th>
                            <th><?= __('Router Id') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($customer->tasks as $tasks) : ?>
                        <tr>
                            <td><?= h($tasks->task_type_id) ?></td>
                            <td><?= h($tasks->task_state_id) ?></td>
                            <td><?= h($tasks->priority) ?></td>
                            <td><?= h($tasks->subject) ?></td>
                            <td><?= nl2br($tasks->text) ?></td>
                            <td><?= h($tasks->dealer_id) ?></td>
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
            <div class="related">
                <h4><?= __('Related Billings') ?></h4>
                <?php if (!empty($customer->billings)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Contract Id') ?></th>
                            <th><?= __('Service Id') ?></th>
                            <th><?= __('Text') ?></th>
                            <th><?= __('Quantity') ?></th>
                            <th><?= __('Price') ?></th>
                            <th><?= __('Billing From') ?></th>
                            <th><?= __('Billing Until') ?></th>
                            <th><?= __('Active') ?></th>
                            <th><?= __('Separate') ?></th>
                            <th><?= __('Note') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($customer->billings as $billings) : ?>
                        <tr>
                            <td><?= h($billings->contract_id) ?></td>
                            <td><?= h($billings->service_id) ?></td>
                            <td><?= h($billings->text) ?></td>
                            <td><?= h($billings->quantity) ?></td>
                            <td><?= h($billings->price) ?></td>
                            <td><?= h($billings->billing_from) ?></td>
                            <td><?= h($billings->billing_until) ?></td>
                            <td><?= h($billings->active) ?></td>
                            <td><?= h($billings->separate) ?></td>
                            <td><?= h($billings->note) ?></td>
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
                            <th><?= __('Contract Id') ?></th>
                            <th><?= __('Equipment Type Id') ?></th>
                            <th><?= __('Serial Number') ?></th>
                            <th><?= __('Borrowed From') ?></th>
                            <th><?= __('Borrowed Until') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($customer->borrowed_equipments as $borrowedEquipments) : ?>
                        <tr>
                            <td><?= h($borrowedEquipments->contract_id) ?></td>
                            <td><?= h($borrowedEquipments->equipment_type_id) ?></td>
                            <td><?= h($borrowedEquipments->serial_number) ?></td>
                            <td><?= h($borrowedEquipments->borrowed_from) ?></td>
                            <td><?= h($borrowedEquipments->borrowed_until) ?></td>
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
                <h4><?= __('Related Sold Equipments') ?></h4>
                <?php if (!empty($customer->sold_equipments)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Contract Id') ?></th>
                            <th><?= __('Equipment Type Id') ?></th>
                            <th><?= __('Serial Number') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($customer->sold_equipments as $soldEquipments) : ?>
                        <tr>
                            <td><?= h($soldEquipments->contract_id) ?></td>
                            <td><?= h($soldEquipments->equipment_type_id) ?></td>
                            <td><?= h($soldEquipments->serial_number) ?></td>
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
                <h4><?= __('Related Ips') ?></h4>
                <?php if (!empty($customer->ips)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Contract Id') ?></th>
                            <th><?= __('Ip') ?></th>
                            <th><?= __('Note') ?></th>
                            <th><?= __('Created') ?></th>
                            <th><?= __('Created By') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($customer->ips as $ips) : ?>
                        <tr>
                            <td><?= h($ips->contract_id) ?></td>
                            <td><?= h($ips->ip) ?></td>
                            <td><?= h($ips->note) ?></td>
                            <td><?= h($ips->created) ?></td>
                            <td><?= h($ips->created_by) ?></td>
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
                <h4><?= __('Related Removed Ips') ?></h4>
                <?php if (!empty($customer->removed_ips)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Contract Id') ?></th>
                            <th><?= __('Ip') ?></th>
                            <th><?= __('Note') ?></th>
                            <th><?= __('Removed') ?></th>
                            <th><?= __('Removed By') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($customer->removed_ips as $removedIps) : ?>
                        <tr>
                            <td><?= h($removedIps->contract_id) ?></td>
                            <td><?= h($removedIps->ip) ?></td>
                            <td><?= h($removedIps->note) ?></td>
                            <td><?= h($removedIps->removed) ?></td>
                            <td><?= h($removedIps->removed_by) ?></td>
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
        </div>
    </div>
</div>
