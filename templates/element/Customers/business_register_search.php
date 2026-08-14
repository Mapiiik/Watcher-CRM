<?php
/**
 * Looks a company up in a public business register and fills the form in from what it says.
 *
 * Picking a suggestion submits the form with a `refresh` field, which the controller answers by
 * filling the entity in instead of saving it - the same round trip the address search makes.
 *
 * @var \App\View\AppView $this
 * @var array<string, string> $businessRegisterSources
 * @var string|null $businessRegisterDefaultSource
 */

// nothing to offer when no register is configured
if ($businessRegisterSources === []) {
    return;
}

// the field is added by the script, so the form security check knows nothing about it
$this->Form->unlockField('refresh');

echo $this->Form->control('business_register_source', [
    'type' => 'select',
    'id' => 'business-register-source',
    'label' => __('Register'),
    'options' => $businessRegisterSources,
    'value' => $businessRegisterDefaultSource,
]);
echo $this->Form->control('business_register_search', [
    'type' => 'select',
    'id' => 'business-register-search',
    'label' => __('Search Business Register'),
    'data-placeholder' => __('Search by name or identification number…'),
]);
