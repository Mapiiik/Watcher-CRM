<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\EntityInterface $nas
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __d('radius', 'Actions') ?></h4>
            <?= $this->Html->link(__d('radius', 'List RADIUS NAS'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-90">
        <div class="Nas form content">
            <?= $this->Form->create($nas) ?>
            <fieldset>
                <legend><?= __d('radius', 'Add RADIUS NAS') ?></legend>
                <?php
                    echo $this->Form->control('nasname');
                    echo $this->Form->control('shortname');
                    echo $this->Form->control('type');
                    echo $this->Form->control('ports');
                    echo $this->Form->control('secret');
                    echo $this->Form->control('server');
                    echo $this->Form->control('community');
                    echo $this->Form->control('description');
                ?>
            </fieldset>
            <?= $this->Form->button(__d('radius', 'Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
