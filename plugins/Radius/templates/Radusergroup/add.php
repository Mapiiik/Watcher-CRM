<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\EntityInterface $radusergroup
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __d('radius', 'Actions') ?></h4>
            <?= $this->Html->link(
                __d('radius', 'List RADIUS User Groups'),
                ['action' => 'index'],
                ['class' => 'side-nav-item']
            ) ?>
        </div>
    </aside>
    <div class="column-responsive column-90">
        <div class="radusergroup form content">
            <?= $this->Form->create($radusergroup) ?>
            <fieldset>
                <legend><?= __d('radius', 'Add RADIUS User Group') ?></legend>
                <?php
                echo $this->Form->control('username', [
                    'options' => $accounts,
                    'empty' => true,
                    'default' => $this->request->getQuery('username'),
                    ]);
                echo $this->Form->control('groupname', [
                    'options' => $groupnames,
                    'empty' => true,
                ]);
                echo $this->Form->control('priority');
                ?>
            </fieldset>
            <?= $this->Form->button(__d('radius', 'Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
