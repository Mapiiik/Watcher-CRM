<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\EntityInterface $radpostauth
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $radpostauth->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $radpostauth->id), 'class' => 'side-nav-item']
            ) ?>
            <?= $this->Html->link(__('List Radpostauth'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-90">
        <div class="radpostauth form content">
            <?= $this->Form->create($radpostauth) ?>
            <fieldset>
                <legend><?= __('Edit Radpostauth') ?></legend>
                <?php
                    echo $this->Form->control('username', ['options' => $accounts]);
                    echo $this->Form->control('pass');
                    echo $this->Form->control('reply');
                    echo $this->Form->control('calledstationid');
                    echo $this->Form->control('callingstationid');
                    echo $this->Form->control('authdate');
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
