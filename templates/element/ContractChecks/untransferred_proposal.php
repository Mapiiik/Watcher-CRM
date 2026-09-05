<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\ContractVersionProposal> $records
 * @var bool|null $contract_column
 */

$contract_column ??= true;
?>
<p>
    <?= __(
        'The customer has agreed to something and the records still say the old thing,'
        . ' so the service runs and is invoiced on the old terms until somebody carries it over.',
    ) ?>
</p>
<div class="table-responsive">
    <table>
        <thead>
            <tr>
                <?php if ($contract_column) : ?>
                    <th><?= __('Contract') ?></th>
                <?php endif ?>
                <th><?= __('Effective From') ?></th>
                <th><?= __('Conclusion Date') ?></th>
                <th><?= __('Sent To The Customer') ?></th>
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
                    <td><?= h($proposal->effective_from) ?></td>
                    <td><?= h($proposal->conclusion_date) ?></td>
                    <td><?= h($proposal->getSending()) ?></td>
                    <td class="actions">
                        <?= $this->AuthLink->link(
                            __('Carry Over'),
                            [
                                'controller' => 'ContractVersionProposals',
                                'action' => 'transfer',
                                $proposal->id,
                            ],
                        ) ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
