<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\Files\Model\Entity\FileLink> $documents
 * @var array<string, string> $models
 * @var array<string, string> $documentTypes
 * @var array<string, string> $variants
 */
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
        <?= $this->Form->control('document_type', [
            'label' => __d('files', 'Document Type'),
            'options' => $documentTypes,
            'empty' => true,
            'onchange' => $this::SUBMIT_ON_CHANGE,
        ]) ?>
    </div>
    <div class="column">
        <?= $this->Form->control('variant', [
            'label' => __d('files', 'Variant'),
            'options' => $variants,
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
        ['class' => 'button float-right'],
    ) ?>
    <h3><?= __d('files', 'Documents') ?></h3>
    <p><?= __d('files', 'Every file the records have, and what each of them is filed as.') ?></p>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('created', __d('files', 'Created')) ?></th>
                    <th><?= $this->Paginator->sort('model', __d('files', 'Model')) ?></th>
                    <th><?= $this->Paginator->sort('document_type', __d('files', 'Document Type')) ?></th>
                    <th><?= $this->Paginator->sort('variant', __d('files', 'Variant')) ?></th>
                    <th><?= $this->Paginator->sort('position', __d('files', 'Position')) ?></th>
                    <th><?= $this->Paginator->sort('name', __d('files', 'Name')) ?></th>
                    <th><?= __d('files', 'Size') ?></th>
                    <th class="actions"><?= __d('files', 'Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($documents as $document) : ?>
                <tr>
                    <td><?= h($document->created) ?></td>
                    <td><?= $this->Record->linkTo($document) ?></td>
                    <td><?= h($document->document_type) ?></td>
                    <td><?= h($document->variant) ?></td>
                    <td><?= $this->Number->format($document->position) ?></td>
                    <td><?= h($document->name) ?></td>
                    <td><?= $this->Number->toReadableSize($document->file->byte_size) ?></td>
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
                        <?= $this->AuthLink->link(
                            __d('files', 'Content'),
                            ['controller' => 'Storage', 'action' => 'view', $document->file_id],
                            ['class' => 'win-link'],
                        ) ?>
                        <?= $this->AuthLink->postLink(
                            __d('files', 'Delete'),
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
