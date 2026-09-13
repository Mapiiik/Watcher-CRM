<?php
/**
 * What is in one folder, said twice over.
 *
 * The table is everything it holds, and is where a file is fetched or let go of - a firmware
 * image has nothing to show and a row says all there is to say about it. The wall under it is
 * what can be drawn, and is for looking rather than for working: one click opens the viewer and
 * the rest are turned through from there.
 *
 * Ordinary links rather than the ones that ask about permission, because a cell is drawn in
 * tests of this plugin as well, where the application's helpers are not there to ask.
 *
 * @var \App\View\AppView $this
 * @var \Files\View\Helper\PreviewHelper $Preview
 * @var \Files\Model\Entity\Documentation $documentation
 * @var list<\Files\Model\Entity\FileLink> $contents
 * @var list<\Files\Model\Entity\FileLink> $seen
 * @var string $gallery
 * @var int $bytes
 */
?>
<?php if ($contents === []) : ?>
    <p><?= __d('files', 'Nothing is filed in this documentation yet.') ?></p>
<?php else : ?>
    <p><?=
        __dn(
            'files',
            '{0} file, {1}',
            '{0} files, {1}',
            count($contents),
            count($contents),
            $this->Number->toReadableSize($bytes),
        )
        ?></p>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= __d('files', 'Name') ?></th>
                    <th><?= __d('files', 'Size') ?></th>
                    <th><?= __d('files', 'Created') ?></th>
                    <th class="actions"><?= __d('files', 'Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($contents as $link) : ?>
                <tr>
                    <td><?= h($link->downloadName()) ?></td>
                    <td><?= $this->Number->toReadableSize($link->file->byte_size ?? 0) ?></td>
                    <td><?= h($link->created) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(
                            __d('files', 'Download'),
                            [
                                'plugin' => 'Files',
                                'controller' => 'Documents',
                                'action' => 'download',
                                $link->id,
                            ],
                        ) ?>
                        <?= $this->Form->postLink(
                            __d('files', 'Delete'),
                            [
                                'plugin' => null,
                                'controller' => 'Documentations',
                                'action' => 'dropFile',
                                $documentation->id,
                                $link->id,
                            ],
                            ['confirm' => __d('files', 'Take {0} out of this documentation?', $link->downloadName())],
                        ) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if ($seen !== []) : ?>
    <ul class="files-tiles">
        <?php foreach ($seen as $at => $link) : ?>
        <li class="files-tile">
            <?= $this->Preview->tileMark($seen, $at, $gallery) ?>
            <span class="files-tile-name"><?= $this->Preview->pageName($seen, $at, $gallery) ?></span>
            <span class="files-tile-size"><?= $this->Number->toReadableSize($link->file->byte_size ?? 0) ?></span>
        </li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>
<?php endif; ?>
