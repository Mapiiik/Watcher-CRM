<?php
/**
 * Looks a company up in a public business register and fills the form in from what it says.
 *
 * Picking a suggestion submits the form with a `refresh` field, which the controller answers by
 * filling the entity in instead of saving it - the same round trip the address search makes.
 * Choosing who a company is represented by takes that road a second time, which is why the
 * company already picked is rendered back into the search field: the script fills that field in
 * as it is searched, so a form rendered again would otherwise arrive without it.
 *
 * @var \App\View\AppView $this
 * @var array<string, string> $businessRegisterSources
 * @var string|null $businessRegisterDefaultSource
 * @var array{key: ?string, label: ?string, officer: ?string, officers: list<\App\BusinessRegister\Dto\Officer>} $businessRegisterSelection
 */

use Cake\I18n\Date;

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
    'options' => $businessRegisterSelection['key'] === null
        ? []
        : [$businessRegisterSelection['key'] => $businessRegisterSelection['label']],
    'value' => $businessRegisterSelection['key'],
]);

// One person sitting in the statutory body is already filled in, with nothing to choose; several
// are offered, because which of them a company is represented by is not the register's to say.
if (count($businessRegisterSelection['officers']) > 1) {
    $officerOptions = [];
    foreach ($businessRegisterSelection['officers'] as $officer) {
        $name = implode(' ', array_filter([
            $officer->title,
            $officer->firstName,
            $officer->lastName,
        ]));
        if ($officer->suffix !== null) {
            $name .= ', ' . $officer->suffix;
        }
        // the date tells two people of one name apart, and is stored with them anyway
        if ($officer->dateOfBirth !== null) {
            $name .= ' (' . h(Date::parseDate($officer->dateOfBirth, 'yyyy-MM-dd')) . ')';
        }

        $officerOptions[$officer->key] = $name;
    }

    echo $this->Form->control('business_register_officer', [
        'type' => 'select',
        'id' => 'business-register-officer',
        'label' => __('Represented By'),
        'options' => $officerOptions,
        'value' => $businessRegisterSelection['officer'],
        'empty' => true,
    ]);
}
