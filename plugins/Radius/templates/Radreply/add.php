<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\EntityInterface $radreply
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __d('radius', 'Actions') ?></h4>
            <?= $this->AuthLink->link(
                __d('radius', 'List RADIUS Replies'),
                ['action' => 'index'],
                ['class' => 'side-nav-item']
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="radreply form content">
            <?= $this->Form->create($radreply) ?>
            <fieldset>
                <legend><?= __d('radius', 'Add RADIUS Reply') ?></legend>
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
