<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\EntityInterface $radcheck
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __d('radius', 'Actions') ?></h4>
            <?= $this->Form->postLink(
                __d('radius', 'Delete RADIUS Check'),
                ['action' => 'delete', $radcheck->id],
                [
                    'confirm' => __d('radius', 'Are you sure you want to delete # {0}?', $radcheck->id),
                    'class' => 'side-nav-item',
                ]
            ) ?>
            <?= $this->Html->link(
                __d('radius', 'List RADIUS Checks'),
                ['action' => 'index'],
                ['class' => 'side-nav-item']
            ) ?>
        </div>
    </aside>
    <div class="column-responsive column-90">
        <div class="radcheck form content">
            <?= $this->Form->create($radcheck) ?>
            <fieldset>
                <legend><?= __d('radius', 'Edit RADIUS Check') ?></legend>
                <?php
                    echo $this->Form->control('username', ['options' => $accounts]);
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
