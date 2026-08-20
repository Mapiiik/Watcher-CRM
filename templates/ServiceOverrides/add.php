<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ServiceOverride $serviceOverride
 * @var \Cake\Collection\CollectionInterface<string, string>|array<string> $contracts
 * @var \Cake\Collection\CollectionInterface<string, string>|array<string> $services
 */

use Cake\I18n\Date;

?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('List Service Overrides'),
                ['action' => 'index'],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="serviceOverrides form content">
            <?= $this->Form->create($serviceOverride) ?>
            <fieldset>
                <legend><?= __('Add Service Override') ?></legend>
                <?php
                if (!isset($contract_id)) {
                    echo $this->Form->control('contract_id', [
                        'options' => $contracts,
                        'empty' => true,
                        'onchange' => $this::REFRESH_ON_CHANGE,
                    ]);
                    $this->Form->unlockField('refresh'); //disable form security check
                }
                echo $this->Form->control('service_id', ['options' => $services, 'empty' => true]);
                echo $this->Form->control('valid_from', [
                    'default' => Date::now(),
                ]);
                echo $this->Form->control('valid_until');
                echo $this->Form->control('reason');
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
