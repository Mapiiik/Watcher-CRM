<?php
/**
 * @var \App\View\AppView $this
 * @var \Files\Model\Entity\File $file
 * @var bool $onTheShelf
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __d('files', 'Actions') ?></h4>
            <?= $this->AuthLink->link(
                __d('files', 'Storage'),
                ['action' => 'index'],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->link(
                __d('files', 'Documents'),
                ['controller' => 'Documents', 'action' => 'index'],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="files view content">
            <h3><?= h($file->hash) ?></h3>

            <?php if (!$onTheShelf) : ?>
                <p class="error-message">
                    <?=
                    __d(
                        'files',
                        'The bytes this row stands for are not in the store. Nothing that points'
                        . ' at it can be handed over until they are put back.',
                    )
                    ?>
                </p>
            <?php endif; ?>

            <table>
                <tr>
                    <th><?= __d('files', 'Hash Type') ?></th>
                    <td><?= h($file->hash_type) ?></td>
                </tr>
                <tr>
                    <th><?= __d('files', 'MIME Type') ?></th>
                    <td><?= h($file->mime_type) ?></td>
                </tr>
                <tr>
                    <th><?= __d('files', 'Size') ?></th>
                    <td><?= $this->Number->toReadableSize($file->byte_size) ?></td>
                </tr>
                <tr>
                    <th><?= __d('files', 'Path') ?></th>
                    <td><code><?= h($file->path) ?></code></td>
                </tr>
                <tr>
                    <th><?= __d('files', 'Created') ?></th>
                    <td><?= h($file->created) ?></td>
                </tr>
                <tr>
                    <th><?= __d('files', 'Created By') ?></th>
                    <td><?= h($file->creator->username ?? '') ?></td>
                </tr>
            </table>

            <div class="related">
                <h4><?= __d('files', 'File Links') ?></h4>
                <?php if (empty($file->file_links)) : ?>
                    <p>
                        <?=
                        __d(
                            'files',
                            'Nothing points at this content. It is what a backup taken while'
                            . ' something was being written leaves behind, and it is safe to'
                            . ' throw away.',
                        )
                        ?>
                    </p>
                <?php else : ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th><?= __d('files', 'Model') ?></th>
                                    <th><?= __d('files', 'Foreign Key') ?></th>
                                    <th><?= __d('files', 'Document Type') ?></th>
                                    <th><?= __d('files', 'Variant') ?></th>
                                    <th><?= __d('files', 'Position') ?></th>
                                    <th><?= __d('files', 'Name') ?></th>
                                    <th class="actions"><?= __d('files', 'Actions') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($file->file_links as $link) : ?>
                                <tr>
                                    <td><?= $this->Record->linkTo($link) ?></td>
                                    <td><code><?= h($link->foreign_key) ?></code></td>
                                    <td><?= h($link->document_type) ?></td>
                                    <td><?= h($link->variant) ?></td>
                                    <td><?= $this->Number->format($link->position) ?></td>
                                    <td><?= h($link->name) ?></td>
                                    <td class="actions">
                                        <?= $this->AuthLink->link(
                                            __d('files', 'Open'),
                                            ['controller' => 'Documents', 'action' => 'open', $link->id],
                                            ['target' => '_blank'],
                                        ) ?>
                                        <?= $this->AuthLink->link(
                                            __d('files', 'Download'),
                                            ['controller' => 'Documents', 'action' => 'download', $link->id],
                                        ) ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
