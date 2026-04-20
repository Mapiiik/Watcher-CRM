<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ContractVersion $contractVersion
 * @var \Cake\Collection\CollectionInterface<string, string>|array<string> $contracts
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
                ],
            ) ?>
            <?= $this->AuthLink->link(
                __('List Contract Versions'),
                ['action' => 'index'],
                ['class' => 'side-nav-item'],
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
                    'checked' => $contractVersion->valid_until !== null,
                    'type' => 'checkbox',
                    'templates' => [
                        'inputContainer' => '<div class="float-left">{{content}}&nbsp;</div>',
                    ],
                    'onclick' => 'document.getElementById("valid-until").disabled = !this.checked;',
                ]);
                echo $this->Form->hidden('valid_until', ['value' => '']); //return null if not enabled
                echo $this->Form->control('valid_until', [
                    'empty' => true,
                    'disabled' => !$contractVersion->valid_until !== null,
                ]);
                $this->Form->unlockField('valid_until'); //disable form security check

                echo $this->Form->control('enable_obligation_until', [
                    'label' => false,
                    'checked' => $contractVersion->obligation_until !== null,
                    'type' => 'checkbox',
                    'templates' => [
                        'inputContainer' => '<div class="float-left">{{content}}&nbsp;</div>',
                    ],
                    'onclick' => <<<TEXT
                        document.getElementById("obligation-until").disabled = !this.checked;
                        document.getElementById("obligations-settled").disabled = !this.checked;
                        TEXT,
                ]);

                echo $this->Form->hidden('obligation_until', ['value' => '']); //return null if not enabled
                echo $this->Form->control('obligation_until', [
                    'empty' => true,
                    'disabled' => !$contractVersion->obligation_until !== null,
                    'default' => $contractVersion->valid_from !== null ?
                        $contractVersion->valid_from->addMonths(24)->subDays(1) : null,
                ]);
                $this->Form->unlockField('obligation_until'); //disable form security check

                echo $this->Form->control('obligations_settled', [
                    'disabled' => !$contractVersion->obligation_until !== null,
                ]);
                $this->Form->unlockField('obligations_settled'); //disable form security check

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
