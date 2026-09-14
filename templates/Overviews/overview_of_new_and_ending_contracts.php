<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\ORM\ResultSet<int, \App\Model\Entity\Contract> $starting
 * @var \Cake\ORM\ResultSet<int, \App\Model\Entity\Contract> $ending
 * @var \App\Model\Enum\ContractPeriodSource $source
 * @var \Cake\I18n\Date $from
 * @var \Cake\I18n\Date $to
 * @var \Cake\Collection\CollectionInterface<string, string>|array<string> $installationCities
 */

use App\Model\Enum\ContractPeriodSource;

?>
<?= $this->Form->create(null, ['type' => 'get', 'valueSources' => ['query', 'context']]) ?>
<div class="row">
    <div class="column">
        <?= $this->Form->control('source', [
            'type' => 'radio',
            'options' => ContractPeriodSource::options(),
            // Said outright: with nothing in the query string neither value source has an
            // answer, and the form would open with no button pressed and empty date boxes
            // while the tables below were already answering by the versions, about this month.
            'value' => $source->value,
            'label' => __('Read the Period From'),
            'onchange' => $this::SUBMIT_ON_CHANGE,
        ]) ?>
    </div>
    <div class="column">
        <?= $this->Form->control('from', [
            'type' => 'date',
            'label' => __('From'),
            'value' => $from->toDateString(),
            'onchange' => $this::SUBMIT_ON_CHANGE,
        ]) ?>
        <?= $this->Form->control('to', [
            'type' => 'date',
            'label' => __('To'),
            'value' => $to->toDateString(),
            'onchange' => $this::SUBMIT_ON_CHANGE,
        ]) ?>
    </div>
    <div class="column">
        <?= $this->Form->control('service_type_id', [
            'empty' => true,
            'onchange' => $this::SUBMIT_ON_CHANGE,
        ]) ?>
        <?= $this->Form->control('contract_state_id', [
            'empty' => true,
            'onchange' => $this::SUBMIT_ON_CHANGE,
        ]) ?>
    </div>
    <div class="column">
        <?= $this->Form->control('cities', [
            'label' => __('Installation City'),
            'options' => $installationCities,
            'multiple' => 'multiple',
            'style' => 'height: 100px;',
            'onchange' => $this::SUBMIT_ON_CHANGE,
        ]) ?>
    </div>
</div>
<?= $this->Form->end() ?>

<div class="overviews index content">
    <?= $this->AuthLink->link(__('List Overviews'), ['action' => 'index'], ['class' => 'button float-right']) ?>
    <?= $this->heading(__('Overview of New and Ending Contracts')
        . ' - '
        . $from->i18nFormat('d. M. yyyy')
        . ' - '
        . $to->i18nFormat('d. M. yyyy')) ?>

    <div>
        <?= __('Starting') . ': ' . $this->Number->format($starting->count()) ?><br>
        <?= __('Ending') . ': ' . $this->Number->format($ending->count()) ?><br>
        <?= __('Net Change') . ': ' . $this->Number->format($starting->count() - $ending->count()) ?><br>
        <small class="hint"><?= __(
            'A contract that begins and ends within the period is in both listings.',
        ) ?></small>
    </div>

    <div class="related">
        <h4><?= __('Starting') ?></h4>
        <?= $this->element('Overviews/contracts_in_period', [
            'contracts' => $starting,
            'date_field' => 'starts_on',
            'date_column' => __('Starts On'),
        ]) ?>
    </div>

    <div class="related">
        <h4><?= __('Ending') ?></h4>
        <?= $this->element('Overviews/contracts_in_period', [
            'contracts' => $ending,
            'date_field' => 'ends_on',
            'date_column' => __('Ends On'),
        ]) ?>
    </div>
</div>
