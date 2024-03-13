<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\RemovedIpAddress $removedIpAddress
 * @var \Cake\Collection\CollectionInterface|array<string> $customers
 * @var \Cake\Collection\CollectionInterface|array<string> $contracts
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('List Removed IP Addresses'),
                ['action' => 'index'],
                ['class' => 'side-nav-item']
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="removedIpAddresses form content">
            <?= $this->Form->create($removedIpAddress) ?>
            <fieldset>
                <legend><?= __('Add Removed IP Address') ?></legend>
                <?php
                if (!isset($customer_id)) {
                    echo $this->Form->control('customer_id', ['options' => $customers, 'empty' => true]);
                }
                if (!isset($contract_id)) {
                    echo $this->Form->control('contract_id', ['options' => $contracts, 'empty' => true]);
                }
                echo $this->Form->control('ip_address', ['label' => __('IP Address')]);
                echo $this->Form->control('type_of_use');
                echo $this->Form->control('note');
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
