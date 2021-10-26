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
            <?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $contract->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $contract->id), 'class' => 'side-nav-item']
            ) ?>
            <?= $this->Html->link(__('List Contracts'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-90">
        <div class="contracts form content">
            <?= $this->Form->create($contract) ?>
            <fieldset>
                <legend><?= __('Edit Contract') ?></legend>
                <div class="row">
                    <div class="column-responsive">
                    <datalist id="access-descriptions">
                        <option value="žebřík není potřeba">
                        <option value="skládačka 3,6m">
                        <option value="žebřík 6m">
                        <option value="žebřík 8m">
                        <option value="žebřík 11m">
                    </datalist>                        
                    <?php
                        if (!isset($customer_id)) echo $this->Form->control('customer_id', ['options' => $customers]);
                        echo $this->Form->control('service_type_id', ['options' => $serviceTypes, 'disabled' => true]);
                        echo $this->Form->control('number');
                        echo $this->Form->control('installation_address_id', ['options' => $installationAddresses, 'empty' => true]);
                        echo $this->Form->control('conclusion_date', ['empty' => true]);
                        echo $this->Form->control('number_of_amendments');
                        echo $this->Form->control('valid_from', ['empty' => true]);
                        
                        echo $this->Form->control('enable_valid_until', [
                            'label' => false,
                            'checked' => $contract->has('valid_until'),
                            'type' => 'checkbox',
                            'templates' => [
                                'inputContainer' => '<div class="float-left">{{content}}&nbsp;</div>'
                            ],
                            'onclick' => 'document.getElementById("valid-until").disabled = !this.checked;']
                        );
                        echo $this->Form->hidden('valid_until', ['value' => null]); //return null if not enabled
                        echo $this->Form->control('valid_until', ['empty' => true, 'disabled' => !$contract->has('valid_until')]);
                        $this->Form->unlockField('valid_until'); //disable form security check

                        echo $this->Form->control('enable_obligation_until', [
                            'label' => false,
                            'checked' => $contract->has('obligation_until'),
                            'type' => 'checkbox',
                            'templates' => [
                                'inputContainer' => '<div class="float-left">{{content}}&nbsp;</div>'
                            ],
                            'onclick' => 'document.getElementById("obligation-until").disabled = !this.checked;']
                        );
                        echo $this->Form->hidden('obligation_until', ['value' => null]); //return null if not enabled
                        echo $this->Form->control('obligation_until', ['empty' => true, 'disabled' => !$contract->has('obligation_until'), 'default' => $contract->has('valid_from') ? $contract->valid_from->addMonth(24)->subDay(1) : null]);
                        $this->Form->unlockField('obligation_until'); //disable form security check
                    ?>
                    </div>
                    <div class="column-responsive">
                    <?php
                        echo $this->Form->control('installation_date', ['empty' => true]);
                        echo $this->Form->control('installation_technician_id', ['options' => $installationTechnicians, 'empty' => true]);
                        echo $this->Form->control('access_description', ['type' => 'text', 'list' => 'access-descriptions']);
                        echo $this->Form->control('brokerage_id', ['options' => $brokerages, 'empty' => true]);
                        echo $this->Form->control('vip');
                        echo $this->Form->control('activation_fee', ['empty' => true]);
                        echo $this->Form->control('activation_fee_with_obligation', ['empty' => true]);
                        echo $this->Form->control('note');
                    ?>
                    </div>
                </div>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
