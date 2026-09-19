<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\ORM\ResultSet<int, \App\Model\Entity\Billing> $starting
 * @var \Cake\ORM\ResultSet<int, \App\Model\Entity\Billing> $ending
 * @var \PhpCollective\DecimalObject\Decimal $startingTotal
 * @var \PhpCollective\DecimalObject\Decimal $endingTotal
 * @var \Cake\I18n\Date $from
 * @var \Cake\I18n\Date $to
 * @var \Cake\Collection\CollectionInterface<string, string>|array<string> $installationCities
 */
?>
<?= $this->Form->create(null, ['type' => 'get', 'valueSources' => ['query', 'context']]) ?>
<div class="row">
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
        <?= $this->Form->control('service_id', [
            'empty' => true,
            'onchange' => $this::SUBMIT_ON_CHANGE,
        ]) ?>
    </div>
    <div class="column">
        <?= $this->Form->control('contract_state_id', [
            'empty' => true,
            'onchange' => $this::SUBMIT_ON_CHANGE,
        ]) ?>
        <?= $this->Form->control('separate_invoice', [
            'type' => 'select',
            'options' => ['1' => __('Yes'), '0' => __('No')],
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
    <?= $this->heading(__('Overview of New and Ending Billings')
        . ' - '
        . $from->i18nFormat('d. M. yyyy')
        . ' - '
        . $to->i18nFormat('d. M. yyyy')) ?>

    <div>
        <?= __('Starting') . ': ' . $this->Number->format($starting->count())
            . ' (' . $this->Number->currency($startingTotal->toString()) . ')' ?><br>
        <?= __('Ending') . ': ' . $this->Number->format($ending->count())
            . ' (' . $this->Number->currency($endingTotal->toString()) . ')' ?><br>
        <?= __('Net Change') . ': '
            . $this->Number->currency($startingTotal->subtract($endingTotal)->toString()) ?><br>
        <small class="hint"><?= __(
            'A billing that begins and ends within the period is in both listings.',
        ) ?></small>
    </div>

    <div class="related">
        <h4><?= __('Starting') ?></h4>
        <?= $this->element('Overviews/billings_in_period', [
            'billings' => $starting,
            'total' => $startingTotal,
            'date_field' => 'billing_from',
            'date_column' => __('Billing From'),
        ]) ?>
    </div>

    <div class="related">
        <h4><?= __('Ending') ?></h4>
        <?= $this->element('Overviews/billings_in_period', [
            'billings' => $ending,
            'total' => $endingTotal,
            'date_field' => 'billing_until',
            'date_column' => __('Billing Until'),
        ]) ?>
    </div>
</div>
