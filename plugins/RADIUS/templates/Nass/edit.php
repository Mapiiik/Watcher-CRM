<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\EntityInterface $nas
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $nas->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $nas->id), 'class' => 'side-nav-item']
            ) ?>
            <?= $this->Html->link(__('List Nass'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-80">
        <div class="nass form content">
            <?= $this->Form->create($nas) ?>
            <fieldset>
                <legend><?= __('Edit Nas') ?></legend>
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
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
