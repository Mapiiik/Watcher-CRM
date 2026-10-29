<?php
use App\Contracts\TheUsualTerm;

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
                <?= $this->legend(__('Add Contract Version')) ?>
                <?php
                if (!isset($contract_id)) {
                    echo $this->Form->control('contract_id', ['options' => $contracts, 'empty' => true]);
                }
                // The usual term is counted from this day, so saying it draws the form again.
                echo $this->Form->control('valid_from', ['onblur' => $this::REFRESH_ON_LEAVING]);
                $this->Form->unlockField('refresh');

                // The boxes say whether the days are on, so a form drawn again keeps a box ticked
                // even while its day is still empty.
                $validUntilNamed = (bool)($this->Form->getSourceValue('enable_valid_until')
                    ?? $contractVersion->valid_until !== null);
                $obligationNamed = (bool)($this->Form->getSourceValue('enable_obligation_until')
                    ?? $contractVersion->obligation_until !== null);

                echo $this->Form->control('enable_valid_until', [
                    'label' => false,
                    'checked' => $validUntilNamed,
                    'type' => 'checkbox',
                    'templates' => [
                        'inputContainer' => '<div class="float-left">{{content}}&nbsp;</div>',
                    ],
                    'onclick' => 'document.getElementById("valid-until").disabled = !this.checked;',
                ]);
                echo $this->Form->hidden('valid_until', ['value' => '']); //return null if not enabled
                echo $this->Form->control('valid_until', [
                    'empty' => true,
                    'disabled' => !$validUntilNamed,
                ]);
                $this->Form->unlockField('valid_until'); //disable form security check

                echo $this->Form->control('enable_obligation_until', [
                    'label' => false,
                    'checked' => $obligationNamed,
                    'type' => 'checkbox',
                    'templates' => [
                        'inputContainer' => '<div class="float-left">{{content}}&nbsp;</div>',
                    ],
                    'onclick' => <<<TEXT
                        document.getElementById("obligation-until").disabled = !this.checked;
                        document.getElementById("obligations-settled").disabled = !this.checked;
                        TEXT,
                ]);

                // Counted from the day the version starts, which is the day the term is agreed from.
                $offer = $contractVersion->valid_from === null ? '' : $this->element('Contracts/usual_term_offer', [
                    'offered' => TheUsualTerm::from($contractVersion->valid_from),
                    'box' => 'enable-obligation-until',
                    'day' => 'obligation-until',
                    'alsoEnabled' => ['obligations-settled'],
                ]);

                echo $this->Form->hidden('obligation_until', ['value' => '']); //return null if not enabled
                echo $this->Form->control('obligation_until', [
                    'label' => ['text' => h(__('Obligation Until')) . $offer, 'escape' => false],
                    'empty' => true,
                    'disabled' => !$obligationNamed,
                ]);
                $this->Form->unlockField('obligation_until'); //disable form security check

                echo $this->Form->control('obligations_settled', [
                    'disabled' => !$obligationNamed,
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
