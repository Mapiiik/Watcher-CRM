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
                            <th><?= __('Task Type') ?></th>
                            <th><?= __('Task State') ?></th>
                            <th><?= __('Subject') ?></th>
                            <th><?= __('Text') ?></th>
                            <th><?= __('Dealer') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($customer->tasks as $task) : ?>
                        <tr>
                            <td><?= $task->has('task_type') ? h($task->task_type->name) : '' ?></td>
                            <td><?= $task->has('task_state') ? h($task->task_state->name) : '' ?></td>
                            <td><?= h($task->subject) ?></td>
                            <td><?= nl2br($task->text) ?></td>
                            <td><?= $task->has('dealer') ? h($task->dealer->name) : '' ?></td>
                            <td class="actions">
                                <?= $this->Html->link(__('View'), ['controller' => 'Tasks', 'action' => 'view', $task->id]) ?>
                                <?= $this->Html->link(__('Edit'), ['controller' => 'Tasks', 'action' => 'edit', $task->id]) ?>
                                <?= $this->Form->postLink(__('Delete'), ['controller' => 'Tasks', 'action' => 'delete', $task->id], ['confirm' => __('Are you sure you want to delete # {0}?', $task->id)]) ?>
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
                            <th><?= __('Contract') ?></th>
                            <th><?= __('Service') ?></th>
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
                        <?php foreach ($customer->billings as $billing) : ?>
                        <tr>
                            <td><?= $billing->has('contract') ? $this->Html->link($billing->contract->number, ['controller' => 'Contracts', 'action' => 'view', $billing->contract->id]) : '' ?></td>
                            <td><?= $billing->has('service') ? $this->Html->link($billing->service->name, ['controller' => 'Services', 'action' => 'view', $billing->service->id]) : '' ?></td>
                            <td><?= h($billing->text) ?></td>
                            <td><?= $this->Number->format($billing->quantity) ?></td>
                            <td><?= $this->Number->format($billing->price) ?></td>
                            <td><?= h($billing->billing_from) ?></td>
                            <td><?= h($billing->billing_until) ?></td>
                            <td><?= $billing->active ? __('Yes') : __('No'); ?></td>
                            <td><?= $billing->separate ? __('Yes') : __('No'); ?></td>
                            <td><?= h($billing->note) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(__('View'), ['controller' => 'Billings', 'action' => 'view', $billing->id]) ?>
                                <?= $this->Html->link(__('Edit'), ['controller' => 'Billings', 'action' => 'edit', $billing->id]) ?>
                                <?= $this->Form->postLink(__('Delete'), ['controller' => 'Billings', 'action' => 'delete', $billing->id], ['confirm' => __('Are you sure you want to delete # {0}?', $billing->id)]) ?>
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
                            <th><?= __('Contract') ?></th>
                            <th><?= __('Equipment Type') ?></th>
                            <th><?= __('Serial Number') ?></th>
                            <th><?= __('Borrowed From') ?></th>
                            <th><?= __('Borrowed Until') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($customer->borrowed_equipments as $borrowedEquipment) : ?>
                        <tr>
                            <td><?= $borrowedEquipment->has('contract') ? $this->Html->link($borrowedEquipment->contract->number, ['controller' => 'Contracts', 'action' => 'view', $borrowedEquipment->contract->id]) : '' ?></td>
                            <td><?= $borrowedEquipment->has('equipment_type') ? $this->Html->link($borrowedEquipment->equipment_type->name, ['controller' => 'EquipmentTypes', 'action' => 'view', $borrowedEquipment->equipment_type->id]) : '' ?></td>
                            <td><?= h($borrowedEquipment->serial_number) ?></td>
                            <td><?= h($borrowedEquipment->borrowed_from) ?></td>
                            <td><?= h($borrowedEquipment->borrowed_until) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(__('View'), ['controller' => 'BorrowedEquipments', 'action' => 'view', $borrowedEquipment->id]) ?>
                                <?= $this->Html->link(__('Edit'), ['controller' => 'BorrowedEquipments', 'action' => 'edit', $borrowedEquipment->id]) ?>
                                <?= $this->Form->postLink(__('Delete'), ['controller' => 'BorrowedEquipments', 'action' => 'delete', $borrowedEquipment->id], ['confirm' => __('Are you sure you want to delete # {0}?', $borrowedEquipment->id)]) ?>
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
                            <th><?= __('Contract') ?></th>
                            <th><?= __('Equipment Type') ?></th>
                            <th><?= __('Serial Number') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($customer->sold_equipments as $soldEquipment) : ?>
                        <tr>
                            <td><?= $soldEquipment->has('contract') ? $this->Html->link($soldEquipment->contract->number, ['controller' => 'Contracts', 'action' => 'view', $soldEquipment->contract->id]) : '' ?></td>
                            <td><?= $soldEquipment->has('equipment_type') ? $this->Html->link($soldEquipment->equipment_type->name, ['controller' => 'EquipmentTypes', 'action' => 'view', $soldEquipment->equipment_type->id]) : '' ?></td>
                            <td><?= h($soldEquipment->serial_number) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(__('View'), ['controller' => 'SoldEquipments', 'action' => 'view', $soldEquipment->id]) ?>
                                <?= $this->Html->link(__('Edit'), ['controller' => 'SoldEquipments', 'action' => 'edit', $soldEquipment->id]) ?>
                                <?= $this->Form->postLink(__('Delete'), ['controller' => 'SoldEquipments', 'action' => 'delete', $soldEquipment->id], ['confirm' => __('Are you sure you want to delete # {0}?', $soldEquipment->id)]) ?>
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
                            <th><?= __('Contract') ?></th>
                            <th><?= __('Ip') ?></th>
                            <th><?= __('Note') ?></th>
                            <th><?= __('Created') ?></th>
                            <th><?= __('Created By') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($customer->ips as $ip) : ?>
                        <tr>
                            <td><?= $ip->has('contract') ? $this->Html->link($ip->contract->number, ['controller' => 'Contracts', 'action' => 'view', $ip->contract->id]) : '' ?></td>
                            <td><?= h($ip->ip) ?></td>
                            <td><?= h($ip->note) ?></td>
                            <td><?= h($ip->created) ?></td>
                            <td><?= h($ip->created_by) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(__('View'), ['controller' => 'Ips', 'action' => 'view', $ip->id]) ?>
                                <?= $this->Html->link(__('Edit'), ['controller' => 'Ips', 'action' => 'edit', $ip->id]) ?>
                                <?= $this->Form->postLink(__('Delete'), ['controller' => 'Ips', 'action' => 'delete', $ip->id], ['confirm' => __('Are you sure you want to delete # {0}?', $ip->ip)]) ?>
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
                            <th><?= __('Contract') ?></th>
                            <th><?= __('Ip') ?></th>
                            <th><?= __('Note') ?></th>
                            <th><?= __('Removed') ?></th>
                            <th><?= __('Removed By') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($customer->removed_ips as $removedIp) : ?>
                        <tr>
                            <td><?= $removedIp->has('contract') ? $this->Html->link($removedIp->contract->number, ['controller' => 'Contracts', 'action' => 'view', $removedIp->contract->id]) : '' ?></td>
                            <td><?= h($removedIp->ip) ?></td>
                            <td><?= h($removedIp->note) ?></td>
                            <td><?= h($removedIp->removed) ?></td>
                            <td><?= h($removedIp->removed_by) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(__('View'), ['controller' => 'RemovedIps', 'action' => 'view', $removedIp->id]) ?>
                                <?= $this->Html->link(__('Edit'), ['controller' => 'RemovedIps', 'action' => 'edit', $removedIp->id]) ?>
                                <?= $this->Form->postLink(__('Delete'), ['controller' => 'RemovedIps', 'action' => 'delete', $removedIp->id], ['confirm' => __('Are you sure you want to delete # {0}?', $removedIp->id)]) ?>
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
