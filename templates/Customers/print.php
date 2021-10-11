<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Contract $contract
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('View Customer'), ['action' => 'view', $customer->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('Edit Customer'), ['action' => 'edit', $customer->id], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-90">
        <div class="contracts form content">
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
                            <td><?= $customer->has('ic') ? h($customer->ic) . ' (' . ($customer->ic_verified ?  __('OK') : __('Invalid')) . ')' : '' ?></td>
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
            <br />
            <?= $this->Form->create($customer, [
                'type' => 'get',
                'valueSources' => ['query', 'context'],
                'target' => 'print',
                'url' => [
                    'action' => 'print',
                    $customer->id,
                    '_ext' => 'pdf'
                ]
            ]) ?>
            <fieldset>
                <legend><?= __('Print Documents') ?></legend>
                <div class="row">
                    <div class="column-responsive">
                    <?php
                        echo $this->Form->control('document_type', ['options' => $documentTypes, 'empty' => true]);
                        echo $this->Form->control('signed', ['type' => 'checkbox']);
                    ?>
                    </div>
                    <div class="column-responsive">
                    <?php
/*                    
                        echo $this->Form->control('effective_date_of_the_amendment', ['empty' => true, 'type' => 'date']);
                        echo $this->Form->control('ssid', ['empty' => true]);
                        echo $this->Form->control('radius_username', ['empty' => true]);
                        echo $this->Form->control('radius_password', ['empty' => true]);
*/
                    ?>
                    </div>
                </div>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
