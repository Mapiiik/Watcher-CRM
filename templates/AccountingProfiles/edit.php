<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\AccountingProfile $accountingProfile
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->postLink(
                __('Delete'),
                ['action' => 'delete', $accountingProfile->id],
                [
                    'confirm' => __('Are you sure you want to delete # {0}?', $accountingProfile->id),
                    'class' => 'side-nav-item',
                ],
            ) ?>
            <?= $this->AuthLink->link(
                __('List Accounting Profiles'),
                ['action' => 'index'],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="accountingProfiles form content">
            <?= $this->Form->create($accountingProfile) ?>
            <fieldset>
                <legend><?= __('Edit Accounting Profile') ?></legend>
                <?php
                    echo $this->Form->control('name');
                    echo $this->Form->control('vat_rate');
                    echo $this->Form->control('reverse_charge');
                    echo $this->Form->control('accounting_assignment_code');
                    echo $this->Form->control('bank_account_code');
                    echo $this->Form->control('activity_code');
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
