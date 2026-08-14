<?php
/**
 * Looks an address up in the national address registry and fills the form in from what it says.
 *
 * Picking a suggestion submits the form with a `refresh` field, which the controller answers by
 * filling the entity in instead of saving it. The addresses a business register knows the customer
 * by take the same road, only with the entry already settled - the register named it.
 *
 * @var \App\View\AppView $this
 * @var string|null $searchCountryCode Lowercase country code the registry is asked for, null when
 *      the address is in a country it does not cover.
 * @var list<array{key: string, label: string, seat: bool}> $businessRegisterAddresses Where a
 *      business register says the customer is registered, empty when none named anywhere.
 */

// the field is added by the script, so the form security check knows nothing about it
$this->Form->unlockField('refresh');

// A company is registered at its seat and does business wherever it has an establishment, and any
// of them may be the one being written down here. None is assumed: the seat is rarely where the
// service is installed, and neither is any one establishment.
if ($businessRegisterAddresses !== []) {
    $addressOptions = [];
    foreach ($businessRegisterAddresses as $registerAddress) {
        $addressOptions[$registerAddress['key']] = ($registerAddress['seat']
            ? __('Registered seat')
            : __('Establishment')) . ': ' . $registerAddress['label'];
    }

    echo $this->Form->control('business_register_address', [
        'type' => 'select',
        'id' => 'business-register-address',
        'label' => __('Address From the Business Register'),
        'options' => $addressOptions,
        'empty' => true,
    ]);
}

echo $this->Form->control('address_registry_search', [
    'type' => 'select',
    'id' => 'address-registry-search',
    'label' => __('Search Address'),
    'disabled' => $searchCountryCode === null, // disable search if no country code is available
    'data-country-code' => $searchCountryCode,
    'data-placeholder' => __('Search address…'),
]);
