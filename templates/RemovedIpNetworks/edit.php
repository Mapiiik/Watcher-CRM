<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\RemovedIpNetwork $removedIpNetwork
 * @var \Cake\Collection\CollectionInterface|array<string> $customers
 * @var \Cake\Collection\CollectionInterface|array<string> $contracts
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->postLink(
                __('Delete'),
                ['action' => 'delete', $removedIpNetwork->id],
                [
                    'confirm' => __('Are you sure you want to delete # {0}?', $removedIpNetwork->id),
                    'class' => 'side-nav-item',
                ]
            ) ?>
            <?= $this->AuthLink->link(
                __('List Removed IP Networks'),
                ['action' => 'index'],
                ['class' => 'side-nav-item']
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="removedIpNetworks form content">
            <?= $this->Form->create($removedIpNetwork) ?>
            <fieldset>
                <legend><?= __('Edit Removed IP Network') ?></legend>
                <?php
                if (!isset($customer_id)) {
                    echo $this->Form->control('customer_id', ['options' => $customers, 'empty' => true]);
                }
                if (!isset($contract_id)) {
                    echo $this->Form->control('contract_id', ['options' => $contracts, 'empty' => true]);
                }
                echo $this->Form->control('ip_network', ['label' => __('IP Network'), 'disabled' => true]);
                echo $this->Form->control('type_of_use');
                echo $this->Form->control('note');
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
