<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ContractVersion $contractVersion
 * @var \Cake\Collection\CollectionInterface|string[] $contracts
 */

use Cake\I18n\Date;

?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('List Contract Versions'),
                ['action' => 'index'],
                ['class' => 'side-nav-item']
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="contractVersions form content">
            <?= $this->Form->create($contractVersion) ?>
            <fieldset>
                <legend><?= __('Add Contract Version') ?></legend>
                <?php
                if (!isset($contract_id)) {
                    echo $this->Form->control('contract_id', ['options' => $contracts, 'empty' => true]);
                }
                echo $this->Form->control('valid_from');

                echo $this->Form->control('enable_valid_until', [
                    'label' => false,
                    'type' => 'checkbox',
                    'templates' => [
                        'inputContainer' => '<div class="float-left">{{content}}&nbsp;</div>',
                    ],
                    'onclick' => 'document.getElementById("valid-until").disabled = !this.checked;',
                ]);
                echo $this->Form->hidden('valid_until', ['value' => '']); //return null if not enabled
                echo $this->Form->control('valid_until', [
                    'empty' => true,
                    'disabled' => !$contractVersion->has('valid_until'),
                ]);
                $this->Form->unlockField('valid_until'); //disable form security check

                echo $this->Form->control('enable_obligation_until', [
                    'label' => false,
                    'type' => 'checkbox',
                    'templates' => [
                        'inputContainer' => '<div class="float-left">{{content}}&nbsp;</div>',
                    ],
                    'onclick' => 'document.getElementById("obligation-until").disabled = !this.checked;',
                ]);
                echo $this->Form->hidden('obligation_until', ['value' => '']); //return null if not enabled
                echo $this->Form->control('obligation_until', [
                    'empty' => true,
                    'disabled' => !$contractVersion->has('obligation_until'),
                    'default' => Date::now()->addMonths(24)->lastOfMonth(),
                ]);
                $this->Form->unlockField('obligation_until'); //disable form security check

                echo $this->Form->control('conclusion_date', ['empty' => true]);
                echo $this->Form->control('number_of_amendments');
                echo $this->Form->control('note');
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
