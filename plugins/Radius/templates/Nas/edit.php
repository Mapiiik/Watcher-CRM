<?php
/**
 * @var \App\View\AppView $this
 * @var \Radius\Model\Entity\Nas $nas
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __d('radius', 'Actions') ?></h4>
            <?= $this->AuthLink->postLink(
                __d('radius', 'Delete RADIUS NAS'),
                ['action' => 'delete', $nas->id],
                [
                    'confirm' => __d('radius', 'Are you sure you want to delete # {0}?', $nas->id),
                    'class' => 'side-nav-item',
                ]
            ) ?>
            <?= $this->AuthLink->link(
                __d('radius', 'List RADIUS NAS'),
                ['action' => 'index'],
                ['class' => 'side-nav-item']
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="Nas form content">
            <?= $this->Form->create($nas) ?>
            <fieldset>
                <legend><?= __d('radius', 'Edit RADIUS NAS') ?></legend>
                <?php
                    echo $this->Form->control('nasname');
                    echo $this->Form->control('shortname');
                    echo $this->Form->control('type');
                    echo $this->Form->control('ports');
                    echo $this->Form->control('secret');
                    echo $this->Form->control('server');
                    echo $this->Form->control('community');
                    echo $this->Form->control('description');
                ?>
            </fieldset>
            <?= $this->Form->button(__d('radius', 'Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
