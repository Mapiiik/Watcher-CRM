<?php
/**
 * The folders of a record, one to a row.
 *
 * A folder with no day is one that is kept up to date rather than one that happened, and those
 * read first - which is the order they arrive in, not something done here.
 *
 * @var \App\View\AppView $this
 * @var \Files\View\Helper\PreviewHelper $Preview
 * @var list<array<string, mixed>> $rows
 */
?>
<?php if ($rows === []) : ?>
    <p><?= __d('files', 'Nothing is filed here yet.') ?></p>
<?php else : ?>
<div class="table-responsive">
    <table class="files-documentations">
        <thead>
            <tr>
                <th><?= __d('files', 'Documentation') ?></th>
                <th><?= __d('files', 'Documentation Type') ?></th>
                <th><?= __d('files', 'Happened On') ?></th>
                <th><?= __d('files', 'Files') ?></th>
                <th><?= __d('files', 'Size') ?></th>
                <th><?= __d('files', 'Note') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $row) : ?>
                <?php
                /** @var \Files\Model\Entity\Documentation $documentation */
                $documentation = $row['documentation'];
                ?>
            <tr>
                <td><?=
                    $this->Html->link(
                        $documentation->heading,
                        [
                            'plugin' => null,
                            'controller' => 'Documentations',
                            'action' => 'view',
                            $documentation->id,
                        ],
                    )
                    ?></td>
                <td><?= h($documentation->documentation_type->name ?? '') ?></td>
                <td><?= h($documentation->happened_on) ?></td>
                <td><?=
                    $this->Preview->flipThrough(
                        $row['contents'],
                        (string)$row['gallery'],
                        $documentation->heading,
                    ) ?: __dn(
                        'files',
                        '{0} file',
                        '{0} files',
                        count($row['contents']),
                        count($row['contents']),
                    )
                    ?></td>
                <td><?= $row['bytes'] > 0 ? $this->Number->toReadableSize($row['bytes']) : '' ?></td>
                <td><?= $this->Text->autoParagraph(h($documentation->note)) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
