<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Task $task
 */
?>
<div class="reports index content">
    <h3><?= __('Reports') ?></h3>
    <div class="table-responsive">
        <?= $this->AuthLink->link(
            __('Overview of active services'),
            ['action' => 'overviewOfActiveServices'],
            ['class' => 'side-nav-item']
        ) ?>
        <?= $this->AuthLink->link(
            __('Overview of connection points'),
            ['action' => 'overviewOfConnectionPoints'],
            ['class' => 'side-nav-item']
        ) ?>
    </div>
</div>
