<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Address $address
 * @var string[]|\Cake\Collection\CollectionInterface $customers
 * @var string[]|\Cake\Collection\CollectionInterface $number_types
 * @var string[]|\Cake\Collection\CollectionInterface $countries
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(__('List Addresses'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-90">
        <div class="addresses form content">
            <?= $this->Form->create($address) ?>
            <fieldset>
                <legend><?= __('Add Address') ?></legend>
                <div class="row">
                    <div class="column-responsive">
                        <?php
                        if (!isset($customer_id)) {
                            echo $this->Form->control('customer_id', ['options' => $customers]);
                        }
                        echo $this->Form->control('type', ['empty' => true]);
                        echo $this->Form->control('company');
                        echo $this->Form->control('title');
                        echo $this->Form->control('first_name');
                        echo $this->Form->control('last_name');
                        echo $this->Form->control('suffix');
                        ?>
                    </div>
                    <div class="column-responsive">
                        <?php
                        echo $this->Form->control('street');
                        echo $this->Form->control('number');
                        echo $this->Form->control('number_type', ['options' => $number_types]);
                        echo $this->Form->control('city');
                        echo $this->Form->control('zip');
                        echo $this->Form->control('country_id', ['options' => $countries]);
                        ?>
                    </div>
                </div>
                <?php
                echo $this->Form->control('note');
                echo $this->Form->control('manual_coordinate_setting', [
                    'onchange' => '
                        var refresh = document.createElement("input");
                        refresh.type = "hidden";
                        refresh.name = "refresh";
                        refresh.value = "refresh";
                        this.form.appendChild(refresh);
                        this.form.submit();
                    ',
                ]);
                $this->Form->unlockField('refresh'); //disable form security check

                if ($address->manual_coordinate_setting) {
                    echo $this->Form->control('gps_y');
                    echo $this->Form->control('gps_x');
                }
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
