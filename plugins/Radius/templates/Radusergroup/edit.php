<?php
/**
 * @var \App\View\AppView $this
 * @var \Radius\Model\Entity\Radusergroup $radusergroup
 * @var \Cake\Collection\CollectionInterface|array<string> $accounts
 * @var \Cake\Collection\CollectionInterface|array<string> $groupnames
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __d('radius', 'Actions') ?></h4>
            <?= $this->AuthLink->postLink(
                __d('radius', 'Delete RADIUS User Group'),
                ['action' => 'delete', $radusergroup->id],
                [
                    'confirm' => __d('radius', 'Are you sure you want to delete # {0}?', $radusergroup->id),
                    'class' => 'side-nav-item',
                ]
            ) ?>
            <?= $this->AuthLink->link(
                __d('radius', 'List RADIUS User Groups'),
                ['action' => 'index'],
                ['class' => 'side-nav-item']
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="radusergroup form content">
            <?= $this->Form->create($radusergroup) ?>
            <fieldset>
                <legend><?= __d('radius', 'Edit RADIUS User Group') ?></legend>
                <?php
                    echo $this->Form->control('username', ['options' => $accounts]);
                    echo $this->Form->control('groupname', ['options' => $groupnames]);
                    echo $this->Form->control('priority');
                ?>
            </fieldset>
            <?= $this->Form->button(__d('radius', 'Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
