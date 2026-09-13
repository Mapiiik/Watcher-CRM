<?php
/**
 * What is in one folder, as a wall of tiles.
 *
 * A folder of photographs from a roof is looked at rather than read, so what each of them shows
 * comes first and its name after. What nothing can draw keeps its place in the wall and says what
 * kind of file it is instead.
 *
 * @var \App\View\AppView $this
 * @var \Files\View\Helper\PreviewHelper $Preview
 * @var \Files\Model\Entity\Documentation $documentation
 * @var list<\Files\Model\Entity\FileLink> $contents
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
<ul class="files-tiles">
    <?php foreach ($contents as $at => $link) : ?>
    <li class="files-tile">
        <?= $this->Preview->tileMark($contents, $at, $gallery) ?>
        <span class="files-tile-name"><?= $this->Preview->pageName($contents, $at, $gallery) ?></span>
        <span class="files-tile-size"><?= $this->Number->toReadableSize($link->file->byte_size ?? 0) ?></span>
    </li>
    <?php endforeach; ?>
</ul>
<?php endif; ?>
