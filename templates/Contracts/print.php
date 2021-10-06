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
            <?= $this->Html->link(__('Edit Contract'), ['action' => 'edit', $contract->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Contracts'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-90">
        <div class="contracts form content">
            <?= $this->Form->create($contract, [
                'type' => 'get',
                'valueSources' => ['query', 'context'],
                'target' => '_blank',
                'url' => [
                    'action' => 'print',
                    $contract->id,
                    'contract-amendmentx',
                    '_ext' => 'pdf'
                ]
            ]) ?>
            <fieldset>
                <legend><?= __('Print Contract') ?></legend>
                <div class="row">
                    <div class="column-responsive">
                    <?php
                        echo $this->Form->control('conclusion_date', ['empty' => true, 'disabled' => true]);
                        echo $this->Form->control('number_of_amendments');
                        echo $this->Form->control('valid_from', ['empty' => true]);
                        echo $this->Form->control('valid_until', ['empty' => true]);
                        echo $this->Form->control('obligation_until', ['empty' => true]);
                    ?>
                    </div>
                    <div class="column-responsive">
                    <?php
                        echo $this->Form->control('installation_date', ['empty' => true]);
                        echo $this->Form->control('installation_technician_id', ['options' => $installationTechnicians, 'empty' => true]);
                        echo $this->Form->control('access_description');
                        echo $this->Form->control('brokerage_id', ['options' => $brokerages, 'empty' => true]);
                        echo $this->Form->control('vip');
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
