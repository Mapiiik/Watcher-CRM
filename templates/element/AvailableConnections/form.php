<?php
/**
 * The form an available connection is entered and changed in.
 *
 * The address comes only from the registry: the search sends the form back to be filled in, and
 * what it filled in travels on in hidden fields until the form is saved.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\AvailableConnection $availableConnection
 * @var array<string, string> $registrySources
 * @var string $searchCountryCode
 * @var array<array-key, mixed> $accessPoints
 * @var string $legend
 */

use App\Model\Enum\AccessTechnology;

$this->Html->script('addresses.js', ['block' => true]);
?>
<?= $this->Form->create($availableConnection, ['valueSources' => ['context']]) ?>
<fieldset>
    <legend><?= __('Address Register Search') ?></legend>
    <div class="row">
        <div class="column column-20">
            <?= $this->Form->control('address_registry_source', [
                'label' => __('Country'),
                'options' => $registrySources,
            ]) ?>
        </div>
        <div class="column">
            <?= $this->element('Addresses/address_register_search', [
                'searchCountryCode' => $searchCountryCode,
                'businessRegisterAddresses' => [],
            ]) ?>
        </div>
    </div>
    <?= $this->Form->hidden('address_registry_reference') ?>
    <?= $this->Form->hidden('address_label') ?>
    <?= $this->Form->hidden('gps_y') ?>
    <?= $this->Form->hidden('gps_x') ?>
    <p>
        <strong><?= __('Address') ?>:</strong>
        <?= $availableConnection->address_registry_reference === null
            ? __('none picked yet')
            : h($availableConnection->address_label)
                . ' (' . h($availableConnection->address_registry_reference) . ')' ?>
    </p>
    <?= $this->Form->error('address_registry_reference') ?>
</fieldset>
<fieldset>
    <?= $this->legend($legend) ?>
    <div class="row">
        <div class="column">
            <?= $this->Form->control('access_technology', [
                'options' => AccessTechnology::groupedOptions(),
                'empty' => true,
            ]) ?>
            <?= $this->Form->control('access_point_id', [
                'options' => $accessPoints,
                'empty' => true,
            ]) ?>
        </div>
        <div class="column">
            <?= $this->Form->control('speed_down_max', [
                'label' => __('Maximum Download Speed (kbps)'),
                'help' => __('What the line can carry, not what the tariff sells.'),
            ]) ?>
            <?= $this->Form->control('speed_up_max', [
                'label' => __('Maximum Upload Speed (kbps)'),
            ]) ?>
        </div>
    </div>
    <?= $this->Form->control('retired', [
        'empty' => true,
        'help' => __('From this day on the connection is no longer there to be had.'),
    ]) ?>
    <?= $this->Form->control('note') ?>
</fieldset>
<?= $this->Form->button(__('Submit')) ?>
<?= $this->Form->end() ?>
