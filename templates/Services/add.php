<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Service $service
 * @var \Cake\Collection\CollectionInterface<string, string>|array<string> $serviceTypes
 * @var \Cake\Collection\CollectionInterface<string, string>|array<string> $connectionProfiles
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(__('List Services'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="services form content">
            <?= $this->Form->create($service) ?>
            <fieldset>
                <?= $this->legend(__('Add Service')) ?>
                <?php
                    echo $this->Form->control('name');
                    echo $this->Form->control('price');
                    echo $this->Form->control('service_type_id', ['options' => $serviceTypes, 'empty' => true]);
                    echo $this->Form->control('connection_profile_id', [
                        'options' => $connectionProfiles,
                        'empty' => true,
                    ]);
                    echo $this->Form->control('criticality_level');
                    echo $this->Form->control('accounting_product_code');
                    echo $this->Form->control('currently_offered');
                    ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
