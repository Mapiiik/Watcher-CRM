<?php
/**
 * @var \App\View\AppView $this
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __d('bookkeeping_pohoda', 'Actions') ?></h4>
            <?= $this->Html->link(
                __d('bookkeeping_pohoda', 'List Invoices'),
                ['action' => 'index'],
                ['class' => 'side-nav-item']
            ) ?>
        </div>
    </aside>
    <div class="column-responsive column-90">
        <div class="invoices form content">
            <?= $this->Form->create(null) ?>
            <fieldset>
                <legend><?= __d('bookkeeping_pohoda', 'Send by email') ?></legend>
                <div class="row">
                    <div class="column-responsive">
                        <?php
                        echo $this->Form->control('creation_date', [
                            'label' => __d('bookkeeping_pohoda', 'Creation Date'),
                            'type' => 'date',
                            'empty' => true,
                            'required' => true,
                        ]);
                        ?>
                    </div>
                    <div class="column-responsive">
                    </div>
                </div>
            </fieldset>
            <?= $this->Form->button(__d('bookkeeping_pohoda', 'Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
