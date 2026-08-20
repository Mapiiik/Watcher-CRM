<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Address $address
 * @var \Cake\Collection\CollectionInterface<string, string>|array<string> $customers
 * @var \Cake\Collection\CollectionInterface<string, string>|array<string> $countries
 * @var string|null $searchCountryCode
 * @var list<array{key: string, label: string, seat: bool}> $businessRegisterAddresses
 */

$this->Html->script('addresses.js', ['block' => true]);
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(__('List Addresses'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="addresses form content">
            <?= $this->Form->create($address, [
                'valueSources' => ['context'],
            ]) ?>
            <fieldset>
                <legend><?= __('Address Register Search') ?></legend>
                <div class="row">
                    <div class="column">
                        <?= $this->element('Addresses/address_register_search', [
                            'searchCountryCode' => $searchCountryCode,
                            'businessRegisterAddresses' => $businessRegisterAddresses,
                        ]) ?>
                    </div>
                </div>
            </fieldset>
            <fieldset>
                <legend><?= __('Add Address') ?></legend>
                <div class="row">
                    <div class="column">
                        <?php
                        if (!isset($customer_id)) {
                            echo $this->Form->control('customer_id', ['options' => $customers]);
                        }
                        echo $this->Form->control('type', [
                            'empty' => true,
                        ]);
                        echo $this->Form->control('company');
                        echo $this->Form->control('title');
                        echo $this->Form->control('first_name');
                        echo $this->Form->control('last_name');
                        echo $this->Form->control('suffix');
                        ?>
                    </div>
                    <div class="column">
                        <?php
                        echo $this->Form->control('street');
                        ?>
                        <div class="row">
                            <div class="column">
                                <?= $this->Form->control('number'); ?>
                            </div>
                            <div class="column">
                                <?= $this->Form->control('entrance'); ?>
                            </div>
                            <div class="column">
                                <?= $this->Form->control('unit'); ?>
                            </div>
                        </div>
                        <?php
                        echo $this->Form->control('number_type');
                        echo $this->Form->control('city');
                        echo $this->Form->control('zip', ['pattern' => '[0-9]*']);
                        echo $this->Form->control('country_id', [
                            'options' => $countries,
                            'empty' => true,
                        ]);
                        ?>
                    </div>
                </div>
                <?php
                echo $this->Form->control('note');
                echo $this->Form->control('manual_coordinate_setting', [
                    'onchange' => $this::REFRESH_ON_CHANGE,
                ]);
                $this->Form->unlockField('refresh'); //disable form security check

                if ($address->manual_coordinate_setting) {
                    echo $this->Form->control('gps_y');
                    echo $this->Form->control('gps_x');
                    echo $this->element('Maps.Maps/point-picker', [
                        'lat' => $address->gps_y,
                        'lng' => $address->gps_x,
                        'country' => $searchCountryCode,
                    ]);
                }
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
