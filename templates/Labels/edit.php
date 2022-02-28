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
            <?= $this->AuthLink->postLink(
                __('Delete'),
                ['action' => 'delete', $label->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $label->id), 'class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->link(__('List Labels'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-90">
        <div class="labels form content">
            <?= $this->Form->create($label) ?>
            <fieldset>
                <legend><?= __('Edit Label') ?></legend>
                <?php
                    echo $this->Form->control('name');
                    echo $this->Form->control('caption');
                    echo $this->Form->control('color', ['type' => 'color']);
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
