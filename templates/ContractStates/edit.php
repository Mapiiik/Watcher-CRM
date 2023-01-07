<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ContractState $contractState
 * @var string[]|\Cake\Collection\CollectionInterface $creators
 * @var string[]|\Cake\Collection\CollectionInterface $modifiers
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $contractState->id],
                [
                    'confirm' => __('Are you sure you want to delete # {0}?', $contractState->id),
                    'class' => 'side-nav-item',
                ]
            ) ?>
            <?= $this->Html->link(__('List Contract States'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-90">
        <div class="contractStates form content">
            <?= $this->Form->create($contractState) ?>
            <fieldset>
                <legend><?= __('Edit Contract State') ?></legend>
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
