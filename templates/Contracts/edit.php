<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Contract $contract
 * @var \Cake\Collection\CollectionInterface|array<string> $customers
 * @var \Cake\Collection\CollectionInterface|array<string> $serviceTypes
 * @var \Cake\Collection\CollectionInterface|array<string> $installationAddresses
 * @var \Cake\Collection\CollectionInterface|array<string> $accessPoints
 * @var \Cake\Collection\CollectionInterface|array<string> $installationTechnicians
 * @var \Cake\Collection\CollectionInterface|array<string> $uninstallationTechnicians
 * @var \Cake\Collection\CollectionInterface|array<string> $commissions
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->postLink(
                __('Delete'),
                ['action' => 'delete', $contract->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $contract->id), 'class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->link(__('List Contracts'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="contracts form content">
            <?= $this->Form->create($contract) ?>
            <fieldset>
                <legend><?= __('Edit Contract') ?></legend>
                <div class="row">
                    <div class="column">
                        <datalist id="access-descriptions">
                            <option value="žebřík není potřeba">
                            <option value="skládačka 3,6m">
                            <option value="žebřík 6m">
                            <option value="žebřík 8m">
                            <option value="žebřík 11m">
                        </datalist>                        
                        <?php
                        if (!isset($customer_id)) {
                            echo $this->Form->control('customer_id', ['options' => $customers]);
                        }
                        echo $this->Form->control('contract_state_id', ['options' => $contractStates, 'empty' => true]);
                        echo $this->Form->control('service_type_id', ['options' => $serviceTypes, 'disabled' => true]);
                        echo $this->Form->control('number');
                        echo $this->Form->control('installation_address_id', [
                            'options' => $installationAddresses,
                            'empty' => true,
                        ]);
                        echo $this->Form->control('commission_id', ['options' => $commissions, 'empty' => true]);
                        echo $this->Form->control('vip');
                        ?>
                    </div>
                    <div class="column">
                        <?php
                        echo $this->Form->control('access_point_id', ['options' => $accessPoints, 'empty' => true]);
                        echo $this->Form->control('access_description', [
                            'type' => 'text',
                            'list' => 'access-descriptions',
                        ]);
                        echo $this->Form->control('activation_fee', ['empty' => true]);
                        echo $this->Form->control('activation_fee_with_obligation', ['empty' => true]);
                        echo $this->Form->control('note');
                        ?>
                    </div>
                </div>
                <div class="row">
                    <div class="column">
                        <?php
                        echo $this->Form->control('installation_date', [
                            'label' => __('Installation/Establishment Date'),
                            'empty' => true,
                        ]);
                        echo $this->Form->control('installation_technician_id', [
                            'options' => $installationTechnicians,
                            'empty' => true,
                        ]);
                        ?>
                    </div>
                    <div class="column">
                        <?php
                        echo $this->Form->control('enable_uninstallation', [
                            'label' => false,
                            'checked' => $contract->__isset('uninstallation_date'),
                            'type' => 'checkbox',
                            'templates' => [
                                'inputContainer' => '<div class="float-left">{{content}}&nbsp;</div>',
                            ],
                            'onclick' => '
                                document.getElementById("uninstallation-date").disabled = !this.checked;
                                document.getElementById("uninstallation-technician-id").disabled = !this.checked;
                            ',
                        ]);

                        echo $this->Form->hidden('uninstallation_date', ['value' => '']); //return null if not enabled
                        echo $this->Form->control('uninstallation_date', [
                            'label' => __('Uninstallation/Cancellation Date'),
                            'empty' => true,
                            'disabled' => !$contract->__isset('uninstallation_date'),
                        ]);
                        $this->Form->unlockField('uninstallation_date'); //disable form security check

                        echo $this->Form->hidden('uninstallation_technician_id', ['value' => '']); //return null if not enabled
                        echo $this->Form->control('uninstallation_technician_id', [
                            'options' => $uninstallationTechnicians,
                            'empty' => true,
                            'disabled' => !$contract->__isset('uninstallation_date'),
                        ]);
                        $this->Form->unlockField('uninstallation_technician_id'); //disable form security check

                        echo $this->Form->control('enable_termination', [
                            'label' => false,
                            'checked' => $contract->__isset('termination_date'),
                            'type' => 'checkbox',
                            'templates' => [
                                'inputContainer' => '<div class="float-left">{{content}}&nbsp;</div>',
                            ],
                            'onclick' => 'document.getElementById("termination-date").disabled = !this.checked;',
                        ]);

                        echo $this->Form->hidden('termination_date', ['value' => '']); //return null if not enabled
                        echo $this->Form->control('termination_date', [
                            'label' => __('Date of Termination of Services'),
                            'empty' => true,
                            'disabled' => !$contract->__isset('termination_date'),
                        ]);
                        $this->Form->unlockField('termination_date'); //disable form security check
                        ?>
                    </div>
                </div>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
