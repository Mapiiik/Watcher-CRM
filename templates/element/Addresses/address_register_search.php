<?php
/**
 * Looks an address up in the national address registry and fills the form in from what it says.
 *
 * Picking a suggestion submits the form with a `refresh` field, which the controller answers by
 * filling the entity in instead of saving it. The registered seat of the customer takes the same
 * road, only with the entry already settled - a business register named it.
 *
 * @var \App\View\AppView $this
 * @var string|null $searchCountryCode Lowercase country code the registry is asked for, null when
 *      the address is in a country it does not cover.
 * @var string|null $registeredSeatKey The customer's registered seat as the registry knows it,
 *      null when no business register named one.
 */

// the field is added by the script, so the form security check knows nothing about it
$this->Form->unlockField('refresh');

// the seat is offered only where a business register named one - it is rarely where the service
// is installed, so it is asked for and never assumed
if ($registeredSeatKey !== null) {
    echo $this->Form->button(__('Fill In the Registered Seat'), [
        'type' => 'submit',
        'name' => 'registered_seat',
        'value' => '1',
        'class' => 'button button-small button-outline float-right',
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
