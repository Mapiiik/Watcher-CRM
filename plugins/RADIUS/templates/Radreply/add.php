<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\EntityInterface $radreply
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('List Radreply'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-80">
        <div class="radreply form content">
            <?= $this->Form->create($radreply) ?>
            <fieldset>
                <legend><?= __('Add Radreply') ?></legend>
                <?php
                    echo $this->Form->control('username');
                    echo $this->Form->control('attribute');
                    echo $this->Form->control('op');
                    echo $this->Form->control('value');
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
