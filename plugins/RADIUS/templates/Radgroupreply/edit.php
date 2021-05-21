<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\EntityInterface $radgroupreply
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $radgroupreply->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $radgroupreply->id), 'class' => 'side-nav-item']
            ) ?>
            <?= $this->Html->link(__('List Radgroupreply'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-80">
        <div class="radgroupreply form content">
            <?= $this->Form->create($radgroupreply) ?>
            <fieldset>
                <legend><?= __('Edit Radgroupreply') ?></legend>
                <?php
                    echo $this->Form->control('groupname');
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
