<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Contract $contract
 * @var \Cake\Collection\CollectionInterface|array<string> $contractStates
 * @var \Cake\Collection\CollectionInterface|array<string> $customers
 * @var \Cake\Collection\CollectionInterface|array<string> $serviceTypes
 * @var \Cake\Collection\CollectionInterface|array<string> $installationAddresses
 * @var \Cake\Collection\CollectionInterface|array<string> $accessPoints
 * @var \Cake\Collection\CollectionInterface|array<string> $commissions
 */

?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(__('List Contracts'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="contracts form content">
            <?= $this->Form->create($contract) ?>
            <fieldset>
                <legend><?= __('Add Contract') ?></legend>
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
                        echo $this->Form->control('service_type_id', ['options' => $serviceTypes, 'empty' => true]);
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
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
