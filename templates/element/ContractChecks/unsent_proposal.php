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
        'The proposal was drawn up and never sent. The day it takes effect comes whether or not'
        . ' the customer has seen the papers.',
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
                <th><?= __('Created') ?></th>
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
                    <td><?= h($proposal->created) ?></td>
                    <td class="actions">
                        <?= $this->AuthLink->link(
                            __('Print'),
                            [
                                'controller' => 'Contracts',
                                'action' => 'print',
                                $proposal->contract_id,
                                '?' => ['proposal_id' => $proposal->id],
                            ],
                        ) ?>
                        <?= $this->AuthLink->link(
                            __('Record the Sending'),
                            [
                                'controller' => 'ContractProposals',
                                'action' => 'send',
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
