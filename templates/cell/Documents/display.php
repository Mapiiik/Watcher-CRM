<?php
/**
 * Every paper a set of proposals has on one side of them, one row to a page.
 *
 * The one table for every place the papers are shown, so that a page filed against a proposal
 * reads the same wherever it is looked at. What several rows have in common is said once, down the
 * side: a contract, a proposal, a document and a hand each get one cell however many pages sit
 * under them, so what belongs together reads as one block.
 *
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\ContractVersionProposal> $proposals
 * @var array<string, array<string, array<string, array<\Files\Model\Entity\FileLink>>>> $filed
 * @var bool $ours Whether this is the side we drew up.
 * @var bool $showContract Whether the rows say which contract they belong to.
 * @var bool $showProposal Whether the rows say which proposal they belong to.
 * @var bool $manage Whether the pages may be reordered and let go of from here.
 */

use App\Model\Enum\ContractPrintType;
use App\Model\Enum\DocumentVariant;

$rows = [];
foreach ($proposals as $proposal) {
    $contractCell = $this->Html->link(
        h($proposal->contract->number ?? ''),
        ['controller' => 'Contracts', 'action' => 'view', $proposal->contract_id],
    );
    $proposalCell = $this->Html->link(
        h($proposal->effective_from) . ' - ' . h($proposal->purpose->label()),
        ['controller' => 'ContractVersionProposals', 'action' => 'view', $proposal->id],
        ['escape' => false],
    );
    $papersLink = $this->AuthLink->link(
        __('Proposal Documents'),
        [
            'plugin' => null,
            'controller' => 'ContractVersionProposals',
            'action' => 'documents',
            $proposal->id,
        ],
        ['class' => 'win-link'],
    );

    foreach ($filed[$proposal->id] ?? [] as $document_type => $byVariant) {
        foreach ($byVariant as $variant => $links) {
            $case = DocumentVariant::tryFrom((string)$variant);
            if ($case === null || $case->isDrawnUpByUs() !== $ours) {
                continue;
            }

            foreach ($links as $link) {
                $rows[] = [
                    'contract' => $contractCell,
                    'proposal' => $proposalCell,
                    'papers' => $papersLink,
                    'proposalId' => (string)$proposal->id,
                    'document' => ContractPrintType::tryFrom((string)$document_type)?->label()
                        ?? (string)$document_type,
                    'variant' => $case->label(),
                    'link' => $link,
                    'keys' => [
                        'contract' => (string)$proposal->contract_id,
                        'proposal' => (string)$proposal->id,
                        'document' => $proposal->id . '/' . $document_type,
                        'variant' => $proposal->id . '/' . $document_type . '/' . $variant,
                    ],
                ];
            }
        }
    }
}

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

    for ($at = 1; $at <= $howMany; $at++) {
        if ($at < $howMany && $rows[$at]['keys'][$of] === $rows[$start]['keys'][$of]) {
            continue;
        }

        for ($each = $start; $each < $at; $each++) {
            $found[$each] = ['start' => $start, 'span' => $at - $start];
        }

        $start = $at;
    }

    return $found;
};

$spans = [];
foreach (['contract', 'proposal', 'document', 'variant'] as $of) {
    $spans[$of] = $runs($rows, $of);
}

// Which column stands at the table's left edge, so that the rows carrying it can be told from the
// ones whose left-hand cells are joined into the row above.
$leftmost = $showContract ? 'contract' : ($showProposal ? 'proposal' : 'document');

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

    return '<td' . $reach . $named . ' style="vertical-align: top;">' . $content . '</td>';
};
?>
<?php if ($rows === []) : ?>
    <p><?= $ours ? __('Nothing has been drawn up yet.') : __('Nothing has come back yet.') ?></p>
<?php else : ?>
<div class="table-responsive">
    <table>
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
            $page = $spans['variant'][$index];
            $first = $page['start'] === $index;
            $last = $page['start'] + $page['span'] - 1 === $index;
            ?>
            <tr<?= $spans[$leftmost][$index]['start'] === $index ? '' : ' class="continued"' ?>>
                <?= $showContract ? $joined($spans['contract'][$index], $index, $row['contract']) : '' ?>
                <?= $showProposal ? $joined($spans['proposal'][$index], $index, $row['proposal']) : '' ?>
                <?= $joined($spans['document'][$index], $index, h($row['document'])) ?>
                <?= $joined($page, $index, h($row['variant'])) ?>
                <td><?= h($row['link']->downloadName()) ?></td>
                <td><?= $this->Number->toReadableSize($row['link']->file->byte_size) ?></td>
                <td><?= h($row['link']->created) ?></td>
                <td class="actions">
                    <?= $this->AuthLink->link(
                        __('Open'),
                        [
                            'plugin' => 'Files',
                            'controller' => 'Documents',
                            'action' => 'open',
                            $row['link']->id,
                        ],
                        ['target' => '_blank'],
                    ) ?>
                    <?= $this->AuthLink->link(
                        __('Download'),
                        [
                            'plugin' => 'Files',
                            'controller' => 'Documents',
                            'action' => 'download',
                            $row['link']->id,
                        ],
                    ) ?>
                    <?php if ($manage && !$first) : ?>
                        <?= $this->AuthLink->postLink(
                            __('Up'),
                            [
                                'plugin' => null,
                                'controller' => 'ContractVersionProposals',
                                'action' => 'movePage',
                                $row['proposalId'],
                                $row['link']->id,
                                'up',
                            ],
                        ) ?>
                    <?php endif; ?>
                    <?php if ($manage && !$last) : ?>
                        <?= $this->AuthLink->postLink(
                            __('Down'),
                            [
                                'plugin' => null,
                                'controller' => 'ContractVersionProposals',
                                'action' => 'movePage',
                                $row['proposalId'],
                                $row['link']->id,
                                'down',
                            ],
                        ) ?>
                    <?php endif; ?>
                    <?php if ($manage) : ?>
                        <?= $this->AuthLink->postLink(
                            __('Remove'),
                            [
                                'plugin' => null,
                                'controller' => 'ContractVersionProposals',
                                'action' => 'dropPage',
                                $row['proposalId'],
                                $row['link']->id,
                            ],
                            ['confirm' => $ours
                                ? __(
                                    'Remove this paper? The document stops being frozen and the'
                                    . ' next request for it draws it afresh.',
                                )
                                : __('Remove this page?')],
                        ) ?>
                    <?php endif; ?>
                </td>
                <?= $showProposal ? $joined($spans['proposal'][$index], $index, $row['papers'], 'actions') : '' ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
