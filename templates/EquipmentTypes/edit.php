<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\EquipmentType $equipmentType
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->postLink(
                __('Delete'),
                ['action' => 'delete', $equipmentType->id],
                [
                    'confirm' => __('Are you sure you want to delete # {0}?', $equipmentType->id),
                    'class' => 'side-nav-item',
                ]
            ) ?>
            <?= $this->AuthLink->link(
                __('List Equipment Types'),
                ['action' => 'index'],
                ['class' => 'side-nav-item']
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="equipmentTypes form content">
            <?= $this->Form->create($equipmentType) ?>
            <fieldset>
                <legend><?= __('Edit Equipment Type') ?></legend>
                <?php
                    echo $this->Form->control('name');
                    echo $this->Form->control('price');
                    echo $this->Form->control('price_with_obligation');
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
