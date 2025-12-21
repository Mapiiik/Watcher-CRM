<?php
/**
 * @var \App\View\AppView $this
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __d('bookkeeping', 'Actions') ?></h4>
            <?= $this->AuthLink->link(
                __d('bookkeeping', 'List Invoices'),
                ['action' => 'index'],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="invoices form content">
            <?= $this->Form->create(null) ?>
            <fieldset>
                <legend><?= __d('bookkeeping', 'Send by email') ?></legend>
                <div class="row">
                    <div class="column">
                        <?php
                        echo $this->Form->control('creation_date', [
                            'label' => __d('bookkeeping', 'Creation Date'),
                            'type' => 'date',
                            'empty' => true,
                            'required' => true,
                        ]);
                        ?>
                    </div>
                    <div class="column">
                    </div>
                </div>
            </fieldset>
            <?= $this->Form->button(__d('bookkeeping', 'Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
