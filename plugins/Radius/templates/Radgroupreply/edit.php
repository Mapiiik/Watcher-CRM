<?php
/**
 * @var \App\View\AppView $this
 * @var \Radius\Model\Entity\Radgroupreply $radgroupreply
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __d('radius', 'Actions') ?></h4>
            <?= $this->AuthLink->postLink(
                __d('radius', 'Delete RADIUS Group Reply'),
                ['action' => 'delete', $radgroupreply->id],
                [
                    'confirm' => __d('radius', 'Are you sure you want to delete # {0}?', $radgroupreply->id),
                    'class' => 'side-nav-item',
                ]
            ) ?>
            <?= $this->AuthLink->link(
                __d('radius', 'List RADIUS Group Replies'),
                ['action' => 'index'],
                ['class' => 'side-nav-item']
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="radgroupreply form content">
            <?= $this->Form->create($radgroupreply) ?>
            <fieldset>
                <legend><?= __d('radius', 'Edit RADIUS Group Reply') ?></legend>
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
