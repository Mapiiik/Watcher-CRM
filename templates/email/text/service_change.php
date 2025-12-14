<?php

use App\Utility\Settings;
use Cake\I18n\Date;

/**
 * @var \Cake\View\View $this
 * @var array<string|int|float> $data
 *
 * phpcs:disable Generic.WhiteSpace.ScopeIndent, Squiz.WhiteSpace.ControlStructureSpacing
 */

$billing_date = (new Date((string)$data['new_billing_from']))->lastOfMonth();
?>

<?= Settings::getString('core.emails.service_change.greeting') ?>


<?= strtr(Settings::getString('core.emails.service_change.intro'), [
    '{new_billing_from}' => $data['new_billing_from'],
    '{contract_number}' => $data['contract_number'],
    '{installation_address_info}' => $data['installation_address']
        ? strtr(Settings::getString('core.emails.service_change.installation_address_info'), [
            '{installation_address}' => $data['installation_address'],
        ])
        : '',
]) ?>


<?= Settings::getString('core.emails.service_change.current_tariff_label') ?>

<?php if ($data['original_billing_sum'] > $data['original_billing_total_price']) : ?>
    <?= strtr(Settings::getString('core.emails.service_change.current_tariff_discounted'), [
        '{original_billing_name}' => $data['original_billing_name'],
        '{original_billing_total_price}' => $this->Number->currency($data['original_billing_total_price']),
    ]) ?>
<?php else : ?>
    <?= strtr(Settings::getString('core.emails.service_change.current_tariff'), [
        '{original_billing_name}' => $data['original_billing_name'],
        '{original_billing_total_price}' => $this->Number->currency($data['original_billing_total_price']),
    ]) ?>
<?php endif; ?>


<?= Settings::getString('core.emails.service_change.new_tariff_label') ?>

<?php if ($data['new_billing_sum'] > $data['new_billing_total_price']) : ?>
    <?= strtr(Settings::getString('core.emails.service_change.new_tariff_discounted'), [
        '{new_billing_name}' => $data['new_billing_name'],
        '{new_billing_total_price}' => $this->Number->currency($data['new_billing_total_price']),
        '{new_billing_sum}' => $this->Number->currency($data['new_billing_sum']),
    ]) ?>
<?php else : ?>
    <?= strtr(Settings::getString('core.emails.service_change.new_tariff'), [
        '{new_billing_name}' => $data['new_billing_name'],
        '{new_billing_total_price}' => $this->Number->currency($data['new_billing_total_price']),
    ]) ?>
<?php endif; ?>


<?php if (
    ($data['original_billing_sum'] > $data['original_billing_total_price'])
    && ($data['new_billing_sum'] > $data['new_billing_total_price'])
) : ?>
<?= Settings::getString('core.emails.service_change.historical_discount') ?>


<?php endif; ?>

<?php if ($data['original_billing_total_price'] == $data['new_billing_total_price']) : ?>
<?= Settings::getString('core.emails.service_change.no_change') ?>
<?php else : ?>
<?= strtr(Settings::getString('core.emails.service_change.first_payment'), [
    '{billing_date}' => $billing_date,
    '{billing_date_plus_10}' => $billing_date->addDays(10),
]) ?>
<?php endif; ?>

<?php if (!$data['version_without_legislative_information']) : ?>

<?= strtr(Settings::getString('core.emails.service_change.price_list'), [
    '{price_list_url}' => Settings::getString('core.company.price_list_url'),
]) ?>


<?= Settings::getString('core.emails.service_change.legislative_information') ?>

<?php endif; ?>

<?= Settings::getString('core.emails.service_change.closing') ?>


<?= Settings::getString('core.company.name') ?>

<?= Settings::getString('core.company.address') ?>

<?= __('Email') ?>: <?= Settings::getString('core.company.contracts.email') ?>

<?= __('Phone') ?>: <?= Settings::getString('core.company.contracts.phone') ?>
