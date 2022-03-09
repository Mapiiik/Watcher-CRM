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
            <?= $this->Html->link(
                __d('radius', 'List Radcheck'),
                ['action' => 'index'],
                ['class' => 'side-nav-item']
            ) ?>
        </div>
    </aside>
    <div class="column-responsive column-90">
        <div class="radcheck form content">
            <?= $this->Form->create($radcheck) ?>
            <fieldset>
                <legend><?= __d('radius', 'Add Radcheck') ?></legend>
                <?php
                echo $this->Form->control('username', [
                    'options' => $accounts,
                    'empty' => true,
                    'default' => $this->request->getQuery('username'),
                ]);
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
