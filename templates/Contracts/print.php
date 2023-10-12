<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Contract $contract
 * @var \Cake\Collection\CollectionInterface|array<string> $contractVersions
 * @var \Cake\Collection\CollectionInterface|array<string> $documentTypes
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('View Contract'),
                ['action' => 'view', $contract->id],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->link(
                __('Edit Contract'),
                ['action' => 'edit', $contract->id],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->link(
                __('List Contracts'),
                ['action' => 'index'],
                ['class' => 'side-nav-item']
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="contracts form content">
            <?= __('Contract No.') ?><h3><?= h($contract->number) ?></h3>
            <h5><?=
                ($contract->__isset('service_type') ? $contract->service_type->name : '') .
                ($contract->__isset('installation_address') ? ' - ' . $contract->installation_address->address : '')
            ?></h5>
            <div class="row">
                <div class="column">
                    <table style="<?= $contract->style ?>">
                        <tr>
                            <th><?= __('Customer') ?></th>
                            <td><?= $contract->__isset('customer') ? $this->Html->link(
                                $contract->customer->name,
                                ['controller' => 'Customers', 'action' => 'view', $contract->customer->id]
                            ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Customer Number') ?></th>
                            <td><?= $contract->__isset('customer') ? h($contract->customer->number) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Contract State') ?></th>
                            <td><?= $contract->__isset('contract_state') ? $this->Html->link(
                                $contract->contract_state->name,
                                ['controller' => 'ContractStates', 'action' => 'view', $contract->contract_state->id]
                            ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Service Type') ?></th>
                            <td><?= $contract->__isset('service_type') ? $this->Html->link(
                                $contract->service_type->name,
                                ['controller' => 'ServiceTypes', 'action' => 'view', $contract->service_type->id]
                            ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Number') ?></th>
                            <td><?= h($contract->number) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Subscriber Verification Code') ?></th>
                            <td><?= h($contract->subscriber_verification_code) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Installation Address') ?></th>
                            <td><?= $contract->__isset('installation_address') ? $this->Html->link(
                                $contract->installation_address->full_address,
                                ['controller' => 'Addresses', 'action' => 'view', $contract->installation_address->id]
                            ) . ($contract->installation_address->note ?
                                ' (' . h($contract->installation_address->note) . ')' : ''
                            ) : '' ?></td>
                        </tr>
                    </table>
                </div>
                <div class="column">
                    <table>
                        <tr>
                            <th><?= __('Access Point') ?></th>
                            <td><?= $contract->__isset('access_point') ? h($contract->access_point['name']) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Commission') ?></th>
                            <td><?= $contract->__isset('commission') ? $this->Html->link(
                                $contract->commission->name,
                                ['controller' => 'Commissions', 'action' => 'view', $contract->commission->id]
                            ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Vip') ?></th>
                            <td><?= $contract->vip ? __('Yes') : __('No'); ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Activation Fee') ?></th>
                            <td><?= h($contract->activation_fee) ?><?= $contract->__isset('service_type') ?
                                ' (' . h($contract->service_type->activation_fee) . ')' : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Activation Fee With Obligation') ?></th>
                            <td><?=
                                h($contract->activation_fee_with_obligation)
                            ?><?=
                                $contract->__isset('service_type') ?
                                    ' (' . h($contract->service_type->activation_fee_with_obligation) . ')' : '' ?></td>
                        </tr>
                    </table>
                </div>
            </div>
            <br>
            <div class="row">
                <div class="column">
                    <table>
                        <tr>
                            <th><?= __('Installation/Establishment Date') ?></th>
                            <td><?= h($contract->installation_date) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Installation Technician') ?></th>
                            <td><?= $contract->__isset('installation_technician') ? $this->Html->link(
                                $contract->installation_technician->name,
                                [
                                    'controller' => 'Customers',
                                    'action' => 'view',
                                    $contract->installation_technician->id,
                                ]
                            ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Uninstallation/Cancellation Date') ?></th>
                            <td><?= h($contract->uninstallation_date) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Uninstallation Technician') ?></th>
                            <td><?= $contract->__isset('uninstallation_technician') ? $this->Html->link(
                                $contract->uninstallation_technician->name,
                                [
                                    'controller' => 'Customers',
                                    'action' => 'view',
                                    $contract->uninstallation_technician->id,
                                ]
                            ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Date of Termination of Services') ?></th>
                            <td><?= h($contract->termination_date) ?></td>
                        </tr>
                    </table>
                </div>
                <div class="column">
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <td><?= $this->Number->format($contract->id) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Created') ?></th>
                            <td><?= h($contract->created) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Created By') ?></th>
                            <td><?= $contract->__isset('creator') ? $this->Html->link(
                                $contract->creator->username,
                                [
                                    'controller' => 'AppUsers',
                                    'action' => 'view',
                                    $contract->creator->id,
                                ]
                            ) : h($contract->created_by) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Modified') ?></th>
                            <td><?= h($contract->modified) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Modified By') ?></th>
                            <td><?= $contract->__isset('modifier') ? $this->Html->link(
                                $contract->modifier->username,
                                [
                                    'controller' => 'AppUsers',
                                    'action' => 'view',
                                    $contract->modifier->id,
                                ]
                            ) : h($contract->modified_by) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
            <?php if ($contract->__isset('service_type') && $contract->service_type->have_contract_versions) : ?>
            <div class="related">
                <?= $this->AuthLink->link(
                    __('New Contract Version'),
                    ['controller' => 'ContractVersions', 'action' => 'add'],
                    ['class' => 'button button-small float-right win-link']
                ) ?>
                <h4><?= __('Contract Versions') ?></h4>
                <?= $this->element('Contracts/ContractVersions', [
                    'contract_versions' => $contract->contract_versions,
                ]) ?>
            </div>
            <br>
            <?php endif; ?>
            <?= $this->Form->create(null, [
                'type' => 'get',
                'valueSources' => ['query'],
                'target' => 'print',
                'url' => [
                    'action' => 'print',
                    $contract->id,
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
                        echo $this->Form->control('contract_version_id', [
                            'label' => __('Contract Version'),
                            'options' => $contractVersions,
                            'empty' => true,
                            'required' => true,
                        ]);
                        echo $this->Form->control('own_equipment', [
                            'label' => __('The customer has his own equipment'),
                            'type' => 'checkbox',
                        ]);
                        echo $this->Form->control('signed', ['label' => __('Signed'), 'type' => 'checkbox']);
                        ?>
                    </div>
                    <div class="column">
                        <?php
                        echo $this->Form->control('effective_date_of_the_amendment', [
                            'label' => __('Effective date of the amendment'),
                            'empty' => true,
                            'type' => 'date',
                        ]);
                        echo $this->Form->control('contract_version_to_be_replaced_id', [
                            'label' => __('Contract Version To Be Replaced'),
                            'options' => $contractVersions,
                            'empty' => true,
                            'required' => false,
                        ]);
                        echo $this->Form->control('number_of_the_contract_to_be_terminated', [
                            'label' => __('The number of the contract to be terminated'),
                            'empty' => true,
                        ]);
                        echo $this->Form->control('access_point', ['empty' => true]);
                        echo $this->Form->control('radius_username', ['empty' => true]);
                        echo $this->Form->control('radius_password', ['empty' => true]);
                        ?>
                    </div>
                </div>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
