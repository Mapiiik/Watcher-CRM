<?php
/**
 * @var \App\View\AppView $this
 * @var \Radius\Model\Entity\Radpostauth $radpostauth
 * @var \Cake\Collection\CollectionInterface|array<string> $accounts
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __d('radius', 'Actions') ?></h4>
            <?= $this->AuthLink->link(
                __d('radius', 'List RADIUS Post Authentications'),
                ['action' => 'index'],
                ['class' => 'side-nav-item']
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="radpostauth form content">
            <?= $this->Form->create($radpostauth) ?>
            <fieldset>
                <legend><?= __d('radius', 'Add RADIUS Post Authentication') ?></legend>
                <?php
                    echo $this->Form->control('username', ['options' => $accounts]);
                    echo $this->Form->control('pass');
                    echo $this->Form->control('reply');
                    echo $this->Form->control('calledstationid');
                    echo $this->Form->control('callingstationid');
                    echo $this->Form->control('authdate');
                ?>
            </fieldset>
            <?= $this->Form->button(__d('radius', 'Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
