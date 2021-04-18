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
            <?= $this->Html->link(__('List Contracts'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-80">
        <div class="contracts form content">
            <?= $this->Form->create($contract) ?>
            <fieldset>
                <legend><?= __('Add Contract') ?></legend>
                <?php
                    if (!isset($customer_id)) echo $this->Form->control('customer_id', ['options' => $customers]);
                    echo $this->Form->control('service_type_id', ['options' => $serviceTypes]);
                    echo $this->Form->control('installation_address_id', ['options' => $installationAddresses, 'empty' => true]);
                    echo $this->Form->control('valid_from', ['empty' => true]);
                    echo $this->Form->control('valid_until', ['empty' => true]);
                    echo $this->Form->control('obligation_until', ['empty' => true]);
                    echo $this->Form->control('note');
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
