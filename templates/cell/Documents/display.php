<?php
/**
 * Every paper a set of rounds has on one side of them, one row to a page.
 *
 * The one table for every place the papers are shown, so that a page filed against a round reads
 * the same wherever it is looked at. What several rows have in common is said once, down the side:
 * a contract, a round, a document and a hand each get one cell however many pages sit under them,
 * so what belongs together reads as one block.
 *
 * Which rounds these are and what they are called is the cell's business. Here is only how it
 * looks - a consent has no contract, and its rows simply leave that cell empty.
 *
 * @var \App\View\AppView $this
 * @var \Files\View\Helper\PreviewHelper $Preview
 * @var list<array<string, mixed>> $rows
 * @var bool $generatedByUs Whether this is the side we generated.
 * @var bool $showContract Whether the rows say which contract they belong to.
 * @var bool $showProposal Whether the rows say which round they belong to.
 * @var bool $manage Whether the pages may be reordered and let go of from here.
 * @var bool $thumbnails Whether each page shows what it looks like.
 */

/**
 * Where each run of rows saying the same thing starts and how far it reaches, for every row in it.
 *
 * The rows come out already gathered, so a run is however many neighbours carry the same key - no
 * sorting and no second pass over the records. Every row is answered for, not only the one a run
 * starts on, because the pages also need to know which of them is first and which last.
 *
 * @param array<int, array<string, mixed>> $rows The rows.
 * @param string $of Which key.
 * @return array<int, array<string, int>>
 */
$runs = function (array $rows, string $of): array {
    $howMany = count($rows);
    $found = [];
    $start = 0;
    $at = 0;

    while ($at <= $howMany) {
        if ($at < $howMany && $rows[$at]['keys'][$of] === $rows[$start]['keys'][$of]) {
            $at++;

            continue;
        }

        for ($each = $start; $each < $at; $each++) {
            $found[$each] = ['start' => $start, 'span' => $at - $start];
        }

        $start = $at;
        $at++;
    }

    return $found;
};

$spans = [];
foreach (['contract', 'round', 'document', 'variant'] as $of) {
    $spans[$of] = $runs($rows, $of);
}

// Which column stands at the table's left edge, so that the rows carrying it can be told from the
// ones whose left-hand cells are joined into the row above.
$leftmost = $showContract ? 'contract' : ($showProposal ? 'round' : 'document');

/**
 * A cell standing for however many rows say the same thing, drawn only where its run starts.
 *
 * @param array<string, int> $run Where the row's run starts and how far it reaches.
 * @param int $index Which row.
 * @param string $content What it says, already safe to print.
 * @param string $class What to call it, where it is one of the columns that has a name.
 * @return string
 */
$joined = function (array $run, int $index, string $content, string $class = ''): string {
    if ($run['start'] !== $index) {
        return '';
    }

    // One row is what a cell covers anyway, so saying so would only be noise.
    $reach = $run['span'] > 1 ? ' rowspan="' . $run['span'] . '"' : '';
    $named = $class === '' ? '' : ' class="' . $class . '"';

    return '<td' . $reach . $named . '>' . $content . '</td>';
};
?>
<?php if ($rows === []) : ?>
    <p><?= $generatedByUs ? __('Nothing has been generated yet.') : __('Nothing has been'
        . ' received yet.') ?></p>
<?php else : ?>
<div class="table-responsive">
    <table class="files-documents">
        <thead>
            <tr>
                <?php if ($showContract) : ?>
                <th><?= __('Contract') ?></th>
                <?php endif; ?>
                <?php if ($showProposal) : ?>
                <th><?= __('Proposal') ?></th>
                <?php endif; ?>
                <th><?= __('Document Type') ?></th>
                <th><?= __('Variant') ?></th>
                <?php if ($thumbnails) : ?>
                <th><?= __('Page') ?></th>
                <?php endif; ?>
                <th><?= __('Name') ?></th>
                <th><?= __('Size') ?></th>
                <th><?= __('Created') ?></th>
                <th class="actions"><?= __('Actions') ?></th>
                <?php if ($showProposal) : ?>
                <th class="actions"><?= __('Proposal Actions') ?></th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $index => $row) : ?>
            <?php
            $round = $row['round'];
            $page = $spans['variant'][$index];
            $first = $page['start'] === $index;
            $last = $page['start'] + $page['span'] - 1 === $index;

            $contractCell = $round['contract_id'] === null
                ? ''
                : $this->Html->link(
                    (string)$round['contract'],
                    ['controller' => 'Contracts', 'action' => 'view', $round['contract_id']],
                );
            $roundCell = $this->Html->link(
                $round['label'],
                ['controller' => $round['controller'], 'action' => 'view', $round['id']],
            );
            // The variant cell already spans exactly the pages of one document, so the way to look
            // through them belongs in it. Built once where the run starts, since that is the only
            // row the cell is drawn on.
            $variantCell = h($row['variant']);
            if ($first && $row['link'] !== null) {
                // The whole of where a page came from, whichever page the table is drawn on. The
                // columns leave out what the page they sit on already says, and the title does
                // not follow them there: it belongs to the file rather than to the listing, so
                // the same file is called the same thing wherever it is met. That it also stands
                // on its own once the overlay covers the page is the same point from the other
                // side.
                $caption = implode(' - ', array_filter([
                    $round['contract'],
                    $round['says'],
                    $row['document'],
                    $row['variant'],
                ]));

                $mark = $this->Preview->flipThrough(
                    array_column(array_slice($rows, $page['start'], $page['span']), 'link'),
                    $row['keys']['variant'],
                    $caption,
                );
                $variantCell .= $mark === '' ? '' : '<br>' . $mark;
            }

            // Which round a link is about, said the way every other link here says it.
            $whose = ['proposal_id' => $round['id'], 'agenda' => $round['controller']];

            // The papers of the round, and the round itself. The leftmost column names it and
            // leads there too, but that column is not always drawn and is a label rather than a
            // way out - so the way to the proposal these papers belong to is offered where the
            // rest of what may be done with it is.
            $papersLink = $this->AuthLink->link(
                __('Documents'),
                [
                    'plugin' => null,
                    'controller' => 'Documents',
                    'action' => 'manage',
                    '?' => $whose,
                ],
            ) . ' ' . $this->AuthLink->link(
                __('View'),
                [
                    'plugin' => null,
                    'controller' => $round['controller'],
                    'action' => 'view',
                    $round['id'],
                ],
            );
            ?>
            <?php if ($row['link'] === null) : ?>
                <?php
                // A paper that is owed and has not been drawn is said plainly; one that is only
                // wanted sometimes is said more quietly. A round with nothing at all names nothing,
                // because there is nothing yet to name.
                $missing = $row['required'] ?? true ? 'error-text' : 'warning-text';
                $named = ($row['document_type'] ?? '') !== '';
                $saying = $named ? __('Not generated yet') : __('Nothing yet');

                /**
                 * Asking for it, with or without our signature on it.
                 *
                 * Drawing one writes it down in this very table, so the page is out of date the
                 * moment the document opens and reads itself again when the reader comes back.
                 * The signed copy is offered from here as well: asking for it draws the plain one
                 * first and stamps that, so one click leaves both on file.
                 *
                 * @param string $saying What the link says.
                 * @param bool $signed Whether to ask for the copy carrying our signature.
                 * @return string
                 */
                $drawIt = function (string $saying, bool $signed) use ($round, $row): string {
                    return $this->AuthLink->link(
                        $saying,
                        [
                            'plugin' => null,
                            'controller' => 'Documents',
                            'action' => 'generate',
                            '_ext' => 'pdf',
                            '?' => array_filter([
                                'proposal_id' => $round['id'],
                                'agenda' => $round['controller'],
                                'document_type' => $row['document_type'],
                                'signed' => $signed ? '1' : null,
                            ]),
                        ],
                        ['class' => 'refresh-on-return', 'target' => '_blank'],
                    );
                };
    ?>
            <tr>
                <?= $showContract ? $joined($spans['contract'][$index], $index, $contractCell) : '' ?>
                <?= $showProposal ? $joined($spans['round'][$index], $index, $roundCell) : '' ?>
                <td><?= h($row['document']) ?></td>
                <td colspan="<?= $thumbnails ? 5 : 4 ?>">
                    <span class="<?= $missing ?>"><?= h($saying) ?></span>
                </td>
                <td class="actions">
                    <?php if ($named) : ?>
                        <?= $drawIt(__('Generate'), false) ?>
                        <?php if ($row['mayBeSigned'] ?? false) : ?>
                            <?= $drawIt(__('Generate Signed'), true) ?>
                        <?php endif; ?>
                    <?php elseif (!$generatedByUs) : ?>
                        <?php
                        // Nothing has come back for this round yet, and what says so is the very
                        // place to offer the filing of it - wherever the table is being read.
                        ?>
                        <?= $this->AuthLink->link(__('Add Files'), [
                            'plugin' => null,
                            'controller' => 'Documents',
                            'action' => 'addPages',
                            '?' => $whose,
                        ]) ?>
                    <?php endif; ?>
                </td>
                <?= $showProposal ? $joined($spans['round'][$index], $index, $papersLink, 'actions') : '' ?>
            </tr>
                <?php continue; ?>
            <?php endif; ?>
            <tr<?= $spans[$leftmost][$index]['start'] === $index ? '' : ' class="continued"' ?>>
                <?= $showContract ? $joined($spans['contract'][$index], $index, $contractCell) : '' ?>
                <?= $showProposal ? $joined($spans['round'][$index], $index, $roundCell) : '' ?>
                <?= $joined($spans['document'][$index], $index, h($row['document'])) ?>
                <?= $joined($page, $index, $variantCell) ?>
                <?php if ($thumbnails) : ?>
                <td><?=
                    $this->Preview->pageMark(
                        array_column(array_slice($rows, $page['start'], $page['span']), 'link'),
                        $index - $page['start'],
                        $row['keys']['variant'],
                    )
                    ?></td>
                <?php endif; ?>
                <td><?=
                    $this->Preview->pageName(
                        array_column(array_slice($rows, $page['start'], $page['span']), 'link'),
                        $index - $page['start'],
                        $row['keys']['variant'],
                    )
                    ?></td>
                <td><?= $this->Number->toReadableSize($row['link']->file->byte_size) ?></td>
                <td><?= h($row['link']->created) ?></td>
                <td class="actions">
                    <?= $this->AuthLink->link(
                        __('Open'),
                        [
                            'plugin' => 'Files',
                            'controller' => 'FileLinks',
                            'action' => 'open',
                            $row['link']->id,
                        ],
                        ['target' => '_blank'],
                    ) ?>
                    <?= $this->AuthLink->link(
                        __('Download'),
                        [
                            'plugin' => 'Files',
                            'controller' => 'FileLinks',
                            'action' => 'download',
                            $row['link']->id,
                        ],
                    ) ?>
                    <?php if ($row['mayBeSigned'] ?? false) : ?>
                        <?= $this->AuthLink->link(
                            __('Generate Signed'),
                            [
                                'plugin' => null,
                                'controller' => 'Documents',
                                'action' => 'generate',
                                '_ext' => 'pdf',
                                '?' => [
                                    'proposal_id' => $round['id'],
                                    'agenda' => $round['controller'],
                                    'document_type' => $row['document_type'],
                                    'signed' => '1',
                                ],
                            ],
                            ['class' => 'refresh-on-return', 'target' => '_blank'],
                        ) ?>
                    <?php endif; ?>
                    <?php if ($manage && !$first) : ?>
                        <?= $this->AuthLink->postLink(
                            __('Up'),
                            [
                                'plugin' => null,
                                'controller' => 'Documents',
                                'action' => 'movePage',
                                $row['link']->id,
                                'up',
                                '?' => $whose,
                            ],
                        ) ?>
                    <?php endif; ?>
                    <?php if ($manage && !$last) : ?>
                        <?= $this->AuthLink->postLink(
                            __('Down'),
                            [
                                'plugin' => null,
                                'controller' => 'Documents',
                                'action' => 'movePage',
                                $row['link']->id,
                                'down',
                                '?' => $whose,
                            ],
                        ) ?>
                    <?php endif; ?>
                    <?php if ($manage) : ?>
                        <?= $this->AuthLink->postLink(
                            __('Remove'),
                            [
                                'plugin' => null,
                                'controller' => 'Documents',
                                'action' => 'dropPage',
                                $row['link']->id,
                                '?' => $whose,
                            ],
                            ['confirm' => $generatedByUs
                                ? __(
                                    'Remove this document? The next time it is requested, it'
                                    . ' will be generated again.',
                                )
                                : __('Remove this page?')],
                        ) ?>
                    <?php endif; ?>
                </td>
                <?= $showProposal ? $joined($spans['round'][$index], $index, $papersLink, 'actions') : '' ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
