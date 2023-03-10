<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ContractState $contractState
 * @var \Cake\Collection\CollectionInterface|string[] $creators
 * @var \Cake\Collection\CollectionInterface|string[] $modifiers
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('List Contract States'),
                ['action' => 'index'],
                ['class' => 'side-nav-item']
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="contractStates form content">
            <?= $this->Form->create($contractState) ?>
            <fieldset>
                <legend><?= __('Add Contract State') ?></legend>
                <?php
                    echo $this->Form->control('name');
                    echo $this->Form->control('color', ['type' => 'color']);
                    echo $this->Form->control('active_services');
                    echo $this->Form->control('billed');
                    echo $this->Form->control('blocked');
                    echo $this->Form->control('note');
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
