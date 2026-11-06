<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\Files\Model\Entity\RemovedFileLink> $documents
 * @var array<string, string> $models
 * @var array<string, string|null> $stops
 */

use Files\Model\Table\FileLinksTable;

$this->Preview->load();

$pages = iterator_to_array($documents, false);
?>
<?= $this->Form->create(null, ['type' => 'get', 'valueSources' => ['query', 'context']]) ?>
<div class="row">
    <div class="column">
        <?= $this->Form->control('model', [
            'label' => __d('files', 'Model'),
            'options' => $models,
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

<div class="files index content files-trash">
    <?= $this->AuthLink->link(
        __d('files', 'File Links'),
        ['controller' => 'FileLinks', 'action' => 'index'],
        ['class' => 'button float-right'],
    ) ?>
    <?= $this->heading(__d('files', 'Trash')) ?>
    <p>
        <?= __d('files', 'What was removed from the records, until it comes back or its time is up.') ?>
        <?= $this->Preview->flipThrough($pages, 'trash', __d('files', 'Trash')) ?>
    </p>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('removed', __d('files', 'Removed')) ?></th>
                    <th><?= __d('files', 'Removed By') ?></th>
                    <th><?= $this->Paginator->sort('model', __d('files', 'Model')) ?></th>
                    <th><?= $this->Paginator->sort('document_type', __d('files', 'Document Type')) ?></th>
                    <th><?= $this->Paginator->sort('variant', __d('files', 'Variant')) ?></th>
                    <th><?= $this->Paginator->sort('name', __d('files', 'Name')) ?></th>
                    <th><?= __d('files', 'Size') ?></th>
                    <th></th>
                    <th class="actions"><?= __d('files', 'Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pages as $at => $document) : ?>
                    <?php $stopped = $stops[(string)$document->id] ?? null; ?>
                <tr>
                    <td><?= h($document->removed) ?></td>
                    <td><?= h($document->remover->username ?? '') ?></td>
                    <td>
                        <?php if ($document->documentation === null) : ?>
                            <?= $this->Record->linkTo($document) ?>
                        <?php else : ?>
                            <?= h($document->model) ?>: <?= h($document->folderName()) ?>
                        <?php endif; ?>
                    </td>
                    <td><?= h($document->document_type) ?></td>
                    <td><?= h($document->variant) ?></td>
                    <td><?= $this->Preview->pageName($pages, $at, 'trash') ?></td>
                    <td><?= $this->Number->toReadableSize($document->file->byte_size) ?></td>
                    <td><?= $this->Preview->pageMark($pages, $at, 'trash') ?></td>
                    <td class="actions">
                        <?= $this->AuthLink->link(
                            __d('files', 'Open'),
                            ['action' => 'open', $document->id],
                            ['target' => '_blank'],
                        ) ?>
                        <?= $this->AuthLink->link(
                            __d('files', 'Download'),
                            ['action' => 'download', $document->id],
                        ) ?>
                        <?php if ($stopped === null) : ?>
                            <?= $this->AuthLink->postLink(
                                __d('files', 'Restore'),
                                ['action' => 'restore', $document->id],
                                ['confirm' => in_array($document->variant, FileLinksTable::ONE_PER_DOCUMENT, true)
                                    ? __d(
                                        'files',
                                        'Restore this document? It says what it said when it was made,'
                                        . ' which may no longer be what the record says.',
                                    )
                                    : __d('files', 'Restore this file?')],
                            ) ?>
                        <?php else : ?>
                            <span class="files-stopped warning-text" title="<?= h($stopped) ?>">
                                <s><?= __d('files', 'Restore') ?></s> ⚠
                            </span>
                        <?php endif; ?>
                        <?= $this->AuthLink->postLink(
                            __d('files', 'Delete'),
                            ['action' => 'delete', $document->id],
                            ['confirm' => __d('files', 'Delete this file for good? This cannot be undone.')],
                        ) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= $this->element('common/paginator') ?>
</div>
