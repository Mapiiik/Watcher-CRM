<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Label $label
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('List Labels'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-80">
        <div class="labels form content">
            <?= $this->Form->create($label) ?>
            <fieldset>
                <legend><?= __('Add Label') ?></legend>
                <?php
                    echo $this->Form->control('name');
                    echo $this->Form->control('caption');
                    echo $this->Form->control('color');
                    echo $this->Form->control('validity');
                    echo $this->Form->control('dynamic');
                    echo $this->Form->control('dynamic_sql');
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
