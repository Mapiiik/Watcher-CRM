<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Queue $queue
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(__('List Queues'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="queues form content">
            <?= $this->Form->create($queue) ?>
            <fieldset>
                <legend><?= __('Add Queue') ?></legend>
                <?php
                    echo $this->Form->control('name');
                    echo $this->Form->control('caption');
                    echo $this->Form->control('fup');
                    echo $this->Form->control('limit');
                    echo $this->Form->control('overlimit_fragment');
                    echo $this->Form->control('overlimit_cost');
                    echo $this->Form->control('speed_up');
                    echo $this->Form->control('speed_down');
                    echo $this->Form->control('cto_category');
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
