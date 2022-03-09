<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\EntityInterface $nas
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __d('radius', 'Actions') ?></h4>
            <?= $this->Html->link(
                __d('radius', 'Edit Nas'),
                ['action' => 'edit', $nas->id],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->Form->postLink(
                __d('radius', 'Delete Nas'),
                ['action' => 'delete', $nas->id],
                [
                    'confirm' => __d('radius', 'Are you sure you want to delete # {0}?', $nas->id),
                    'class' => 'side-nav-item',
                ]
            ) ?>
            <?= $this->Html->link(
                __d('radius', 'List Nas'),
                ['action' => 'index'],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->Html->link(
                __d('radius', 'New Nas'),
                ['action' => 'add'],
                ['class' => 'side-nav-item']
            ) ?>
        </div>
    </aside>
    <div class="column-responsive column-90">
        <div class="Nas view content">
            <h3><?= h($nas->nasname) ?></h3>
            <table>
                <tr>
                    <th><?= __d('radius', 'Id') ?></th>
                    <td><?= $this->Number->format($nas->id) ?></td>
                </tr>
                <tr>
                    <th><?= __d('radius', 'Ports') ?></th>
                    <td><?= $this->Number->format($nas->ports) ?></td>
                </tr>
            </table>
            <div class="text">
                <strong><?= __d('radius', 'Nasname') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($nas->nasname)); ?>
                </blockquote>
            </div>
            <div class="text">
                <strong><?= __d('radius', 'Shortname') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($nas->shortname)); ?>
                </blockquote>
            </div>
            <div class="text">
                <strong><?= __d('radius', 'Type') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($nas->type)); ?>
                </blockquote>
            </div>
            <div class="text">
                <strong><?= __d('radius', 'Secret') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($nas->secret)); ?>
                </blockquote>
            </div>
            <div class="text">
                <strong><?= __d('radius', 'Server') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($nas->server)); ?>
                </blockquote>
            </div>
            <div class="text">
                <strong><?= __d('radius', 'Community') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($nas->community)); ?>
                </blockquote>
            </div>
            <div class="text">
                <strong><?= __d('radius', 'Description') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($nas->description)); ?>
                </blockquote>
            </div>
        </div>
    </div>
</div>