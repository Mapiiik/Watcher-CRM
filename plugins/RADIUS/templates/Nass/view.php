<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\EntityInterface $nas
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('Edit Nas'), ['action' => 'edit', $nas->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete Nas'), ['action' => 'delete', $nas->id], ['confirm' => __('Are you sure you want to delete # {0}?', $nas->id), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Nass'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Nas'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-90">
        <div class="nass view content">
            <h3><?= h($nas->nasname) ?></h3>
            <table>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= $this->Number->format($nas->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Ports') ?></th>
                    <td><?= $this->Number->format($nas->ports) ?></td>
                </tr>
            </table>
            <div class="text">
                <strong><?= __('Nasname') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($nas->nasname)); ?>
                </blockquote>
            </div>
            <div class="text">
                <strong><?= __('Shortname') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($nas->shortname)); ?>
                </blockquote>
            </div>
            <div class="text">
                <strong><?= __('Type') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($nas->type)); ?>
                </blockquote>
            </div>
            <div class="text">
                <strong><?= __('Secret') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($nas->secret)); ?>
                </blockquote>
            </div>
            <div class="text">
                <strong><?= __('Server') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($nas->server)); ?>
                </blockquote>
            </div>
            <div class="text">
                <strong><?= __('Community') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($nas->community)); ?>
                </blockquote>
            </div>
            <div class="text">
                <strong><?= __('Description') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($nas->description)); ?>
                </blockquote>
            </div>
        </div>
    </div>
</div>
