<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\Files\Model\Entity\File> $files
 * @var array<string, string> $mimeTypes
 * @var array<string, int> $totals
 */
?>
<?= $this->Form->create(null, ['type' => 'get', 'valueSources' => ['query', 'context']]) ?>
<div class="row">
    <div class="column">
        <?= $this->Form->control('mime_type', [
            'label' => __d('files', 'MIME Type'),
            'options' => $mimeTypes,
            'empty' => true,
            'onchange' => $this::SUBMIT_ON_CHANGE,
        ]) ?>
    </div>
    <div class="column">
        <?= $this->Form->control('search', [
            'label' => __d('files', 'Hash Begins With'),
            'type' => 'search',
            'onchange' => $this::SUBMIT_ON_CHANGE,
        ]) ?>
    </div>
    <div class="column">
        <?= $this->Form->control('unused', [
            'label' => __d('files', 'Nothing points at it'),
            'type' => 'checkbox',
            'onchange' => $this::SUBMIT_ON_CHANGE,
        ]) ?>
    </div>
</div>
<?= $this->Form->end() ?>

<div class="files index content">
    <?= $this->AuthLink->link(
        __d('files', 'Documents'),
        ['controller' => 'Documents', 'action' => 'index'],
        ['class' => 'button float-right'],
    ) ?>
    <h3><?= __d('files', 'Storage') ?></h3>
    <p><?= __d(
        'files',
        'Each piece of content once, however many records point at it. What nothing points at any'
        . ' more is here too - that is the state a torn backup leaves behind, and the harmless one.',
    ) ?></p>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('created', __d('files', 'Created')) ?></th>
                    <th><?= $this->Paginator->sort('hash', __d('files', 'Hash')) ?></th>
                    <th><?= $this->Paginator->sort('mime_type', __d('files', 'MIME Type')) ?></th>
                    <th><?= $this->Paginator->sort('byte_size', __d('files', 'Size')) ?></th>
                    <th><?= __d('files', 'File Links') ?></th>
                    <th class="actions"><?= __d('files', 'Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($files as $file) : ?>
                <tr>
                    <td><?= h($file->created) ?></td>
                    <td><code><?= h(substr($file->hash, 0, 12)) ?></code></td>
                    <td><?= h($file->mime_type) ?></td>
                    <td><?= $this->Number->toReadableSize($file->byte_size) ?></td>
                    <td>
                        <?php $uses = count($file->file_links ?? []); ?>
                        <?= $uses === 0
                            ? __d('files', 'nothing')
                            : $this->Number->format($uses) ?>
                    </td>
                    <td class="actions">
                        <?= $this->AuthLink->link(__d('files', 'View'), ['action' => 'view', $file->id]) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= $this->element('common/paginator') ?>

    <p>
        <?= __d(
            'files',
            '{0} on the shelf, {1} in all, {2} of them pointed at by nothing.',
            $this->Number->format($totals['files']),
            $this->Number->toReadableSize($totals['bytes']),
            $this->Number->format($totals['unused']),
        ) ?>
    </p>
</div>
