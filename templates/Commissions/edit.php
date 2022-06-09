<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Commission $commission
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->postLink(
                __('Delete'),
                ['action' => 'delete', $commission->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $commission->id), 'class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->link(__('List Commissions'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-90">
        <div class="commissions form content">
            <?= $this->Form->create($commission) ?>
            <fieldset>
                <legend><?= __('Edit Commission') ?></legend>
                <?php
                    echo $this->Form->control('name');
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
