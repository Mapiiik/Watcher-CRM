<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\ContractProposal> $records
 * @var bool|null $contract_column
 */

$contract_column ??= true;
?>
<p>
    <?= __(
        'The customer signed and the signed papers are on nobody\'s shelf. Nothing is held up by'
        . ' it, but what was agreed to cannot be shown to anybody either.',
    ) ?>
</p>
<div class="table-responsive">
    <table>
        <thead>
            <tr>
                <?php if ($contract_column) : ?>
                    <th><?= __('Contract') ?></th>
                <?php endif ?>
                <th><?= __('Purpose') ?></th>
                <th><?= __('Effective From') ?></th>
                <th><?= __('Conclusion Date') ?></th>
                <th class="actions"><?= __('Actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($records as $proposal) : ?>
                <tr>
                    <?= $this->element('ContractChecks/contract_cell', [
                        'contract' => $proposal->contract,
                        'contract_column' => $contract_column,
                    ]) ?>
                    <td><?= h($proposal->purpose->label()) ?></td>
                    <td><?= h($proposal->effective_from) ?></td>
                    <td><?= h($proposal->conclusion_date) ?></td>
                    <td class="actions">
                        <?= $this->AuthLink->link(
                            __('Proposal Documents'),
                            [
                                'controller' => 'ContractProposals',
                                'action' => 'documents',
                                $proposal->id,
                            ],
                            ['class' => 'win-link'],
                        ) ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
