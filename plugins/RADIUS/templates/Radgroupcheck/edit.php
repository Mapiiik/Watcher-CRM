<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\EntityInterface $radgroupcheck
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __d('radius', 'Actions') ?></h4>
            <?= $this->Form->postLink(
                __d('radius', 'Delete'),
                ['action' => 'delete', $radgroupcheck->id],
                [
                    'confirm' => __d('radius', 'Are you sure you want to delete # {0}?', $radgroupcheck->id),
                    'class' => 'side-nav-item',
                ]
            ) ?>
            <?= $this->Html->link(
                __d('radius', 'List Radgroupcheck'),
                ['action' => 'index'],
                ['class' => 'side-nav-item']
            ) ?>
        </div>
    </aside>
    <div class="column-responsive column-90">
        <div class="radgroupcheck form content">
            <?= $this->Form->create($radgroupcheck) ?>
            <fieldset>
                <legend><?= __d('radius', 'Edit Radgroupcheck') ?></legend>
                <?php
                    echo $this->Form->control('groupname');
                    echo $this->Form->control('attribute');
                    echo $this->Form->control('op');
                    echo $this->Form->control('value');
                ?>
            </fieldset>
            <?= $this->Form->button(__d('radius', 'Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
