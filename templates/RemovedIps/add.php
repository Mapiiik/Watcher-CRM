<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\RemovedIp $removedIp
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('List Removed Ips'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-80">
        <div class="removedIps form content">
            <?= $this->Form->create($removedIp) ?>
            <fieldset>
                <legend><?= __('Add Removed Ip') ?></legend>
                <?php
                    if (!isset($customer_id)) echo $this->Form->control('customer_id', ['options' => $customers, 'empty' => true]);
                    if (!isset($contract_id)) echo $this->Form->control('contract_id', ['options' => $contracts, 'empty' => true]);
                    echo $this->Form->control('ip');
                    echo $this->Form->control('note');
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
