<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Brokerage $brokerage
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $brokerage->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $brokerage->id), 'class' => 'side-nav-item']
            ) ?>
            <?= $this->Html->link(__('List Brokerages'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-90">
        <div class="brokerages form content">
            <?= $this->Form->create($brokerage) ?>
            <fieldset>
                <legend><?= __('Edit Brokerage') ?></legend>
                <?php
                    echo $this->Form->control('name');
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
