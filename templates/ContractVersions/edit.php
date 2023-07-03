<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ContractVersion $contractVersion
 * @var \Cake\Collection\CollectionInterface|array<string> $contracts
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->postLink(
                __('Delete'),
                ['action' => 'delete', $contractVersion->id],
                [
                    'confirm' => __('Are you sure you want to delete # {0}?', $contractVersion->id),
                    'class' => 'side-nav-item',
                ]
            ) ?>
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
                <legend><?= __('Edit Contract Version') ?></legend>
                <?php
                if (!isset($contract_id)) {
                    echo $this->Form->control('contract_id', ['options' => $contracts, 'empty' => true]);
                }
                echo $this->Form->control('valid_from');
                echo $this->Form->control('enable_valid_until', [
                    'label' => false,
                    'checked' => $contractVersion->__isset('valid_until'),
                    'type' => 'checkbox',
                    'templates' => [
                        'inputContainer' => '<div class="float-left">{{content}}&nbsp;</div>',
                    ],
                    'onclick' => 'document.getElementById("valid-until").disabled = !this.checked;',
                ]);
                echo $this->Form->hidden('valid_until', ['value' => '']); //return null if not enabled
                echo $this->Form->control('valid_until', [
                    'empty' => true,
                    'disabled' => !$contractVersion->__isset('valid_until'),
                ]);
                $this->Form->unlockField('valid_until'); //disable form security check

                echo $this->Form->control('enable_obligation_until', [
                    'label' => false,
                    'checked' => $contractVersion->__isset('obligation_until'),
                    'type' => 'checkbox',
                    'templates' => [
                        'inputContainer' => '<div class="float-left">{{content}}&nbsp;</div>',
                    ],
                    'onclick' => 'document.getElementById("obligation-until").disabled = !this.checked;',
                ]);
                echo $this->Form->hidden('obligation_until', ['value' => '']); //return null if not enabled
                echo $this->Form->control('obligation_until', [
                    'empty' => true,
                    'disabled' => !$contractVersion->__isset('obligation_until'),
                    'default' => $contractVersion->__isset('valid_from') ?
                        $contractVersion->valid_from->addMonths(24)->subDays(1) : null,
                ]);
                $this->Form->unlockField('obligation_until'); //disable form security check

                echo $this->Form->control('conclusion_date', ['empty' => true, 'max' => date('Y-m-d')]);
                echo $this->Form->control('number_of_amendments');
                echo $this->Form->control('note');
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
