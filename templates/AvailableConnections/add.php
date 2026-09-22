<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\AvailableConnection $availableConnection
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('List Available Connections'),
                ['action' => 'index'],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="availableConnections form content">
            <?= $this->element('AvailableConnections/form', ['legend' => __('Add Available Connection')]) ?>
        </div>
    </div>
</div>
