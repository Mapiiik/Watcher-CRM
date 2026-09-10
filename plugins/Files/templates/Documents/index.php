<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\Files\Model\Entity\FileLink> $documents
 * @var array<string, string> $models
 * @var array<string, string> $collections
 * @var array<string, string> $roles
 */
?>
<?= $this->Form->create(null, ['type' => 'get', 'valueSources' => ['query', 'context']]) ?>
<div class="row">
    <div class="column">
        <?= $this->Form->control('model', [
            'label' => __d('files', 'Held By'),
            'options' => $models,
            'empty' => true,
            'onchange' => $this::SUBMIT_ON_CHANGE,
        ]) ?>
    </div>
    <div class="column">
        <?= $this->Form->control('collection', [
            'label' => __d('files', 'Document'),
            'options' => $collections,
            'empty' => true,
            'onchange' => $this::SUBMIT_ON_CHANGE,
        ]) ?>
    </div>
    <div class="column">
        <?= $this->Form->control('role', [
            'label' => __d('files', 'Signatures'),
            'options' => $roles,
            'empty' => true,
            'onchange' => $this::SUBMIT_ON_CHANGE,
        ]) ?>
    </div>
    <div class="column">
        <?= $this->Form->control('search', [
            'label' => __d('files', 'Name'),
            'type' => 'search',
            'onchange' => $this::SUBMIT_ON_CHANGE,
        ]) ?>
    </div>
</div>
<?= $this->Form->end() ?>

<div class="files index content">
    <?= $this->AuthLink->link(
        __d('files', 'Storage'),
        ['controller' => 'Storage', 'action' => 'index'],
        ['class' => 'button float-right win-link'],
    ) ?>
    <h3><?= __d('files', 'Documents') ?></h3>
    <p><?= __d('files', 'Every file the records have, and what each of them is filed as.') ?></p>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('created', __d('files', 'Filed')) ?></th>
                    <th><?= $this->Paginator->sort('model', __d('files', 'Held By')) ?></th>
                    <th><?= $this->Paginator->sort('collection', __d('files', 'Document')) ?></th>
                    <th><?= $this->Paginator->sort('role', __d('files', 'Signatures')) ?></th>
                    <th><?= $this->Paginator->sort('position', __d('files', 'Page')) ?></th>
                    <th><?= $this->Paginator->sort('name', __d('files', 'Name')) ?></th>
                    <th><?= __d('files', 'Size') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($documents as $document) : ?>
                <tr>
                    <td><?= h($document->created) ?></td>
                    <td><?= h($document->model) ?></td>
                    <td><?= h($document->collection) ?></td>
                    <td><?= h($document->role) ?></td>
                    <td><?= $this->Number->format($document->position + 1) ?></td>
                    <td><?= h($document->name) ?></td>
                    <td><?= $this->Number->toReadableSize($document->file->byte_size) ?></td>
                    <td class="actions">
                        <?= $this->AuthLink->link(
                            __d('files', 'Download'),
                            ['action' => 'download', $document->id],
                        ) ?>
                        <?= $this->AuthLink->link(
                            __d('files', 'Content'),
                            ['controller' => 'Storage', 'action' => 'view', $document->file_id],
                            ['class' => 'win-link'],
                        ) ?>
                        <?= $this->AuthLink->postLink(
                            __('Delete'),
                            ['action' => 'delete', $document->id],
                            ['confirm' => __d(
                                'files',
                                'Remove this document from the record? The content itself goes only'
                                . ' if nothing else wants it.',
                            )],
                        ) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= $this->element('common/paginator') ?>
</div>
