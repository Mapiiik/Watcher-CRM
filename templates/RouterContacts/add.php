<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\RouterContact $routerContact
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('List Router Contacts'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-80">
        <div class="routerContacts form content">
            <?= $this->Form->create($routerContact) ?>
            <fieldset>
                <legend><?= __('Add Router Contact') ?></legend>
                <?php
                    echo $this->Form->control('router_id', ['options' => $routers]);
                    echo $this->Form->control('customer_id', ['options' => $customers]);
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
