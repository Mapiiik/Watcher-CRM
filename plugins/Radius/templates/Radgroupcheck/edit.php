<?php
/**
 * @var \App\View\AppView $this
 * @var \Radius\Model\Entity\Radgroupcheck $radgroupcheck
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __d('radius', 'Actions') ?></h4>
            <?= $this->AuthLink->postLink(
                __d('radius', 'Delete RADIUS Group Check'),
                ['action' => 'delete', $radgroupcheck->id],
                [
                    'confirm' => __d('radius', 'Are you sure you want to delete # {0}?', $radgroupcheck->id),
                    'class' => 'side-nav-item',
                ]
            ) ?>
            <?= $this->AuthLink->link(
                __d('radius', 'List RADIUS Group Checks'),
                ['action' => 'index'],
                ['class' => 'side-nav-item']
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="radgroupcheck form content">
            <?= $this->Form->create($radgroupcheck) ?>
            <fieldset>
                <legend><?= __d('radius', 'Edit RADIUS Group Check') ?></legend>
                <?php
                    echo $this->Form->control('groupname');
                    echo $this->Form->control('attribute');
                    echo $this->Form->control('op');
                    echo $this->Form->control('value');
                ?>
            </fieldset>
            <?= $this->Form->button(__d('radius', 'Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
