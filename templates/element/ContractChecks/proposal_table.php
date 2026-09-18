<?php
/**
 * The papers of contracts a check found, one row each.
 *
 * The four checks on proposals list the same thing and differ only in which days they are about
 * and what may be done next, so each says that much and this draws the rest.
 *
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\ContractProposal> $records
 * @var bool $contract_column
 * @var bool $customer_column
 * @var list<string> $dates Which days the rows show: `created`, `sent` or `concluded`.
 * @var list<string> $steps What the rows offer: `documents`, `send`, `conclude` or `apply`.
 */

$headings = [
    'created' => __('Created'),
    'sent' => __('Sent To The Customer'),
    'concluded' => __('Conclusion Date'),
];
?>
<div class="table-responsive">
    <table>
        <thead>
            <tr>
                <?php if ($contract_column) : ?>
                    <th><?= __('Contract') ?></th>
                <?php endif ?>
                <?php if ($customer_column) : ?>
                    <th><?= __('Customer') ?></th>
                <?php endif ?>
                <th><?= __('Purpose') ?></th>
                <th><?= __('Effective From') ?></th>
                <?php foreach ($dates as $date) : ?>
                    <th><?= $headings[$date] ?></th>
                <?php endforeach ?>
                <th class="actions"><?= __('Actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($records as $proposal) : ?>
                <?php
                $on = [
                    'created' => $proposal->created,
                    'sent' => $proposal->getSending(),
                    'concluded' => $proposal->conclusion_date,
                ];
                ?>
                <tr>
                    <?= $this->element('ContractChecks/contract_cell', [
                        'contract' => $proposal->contract,
                        'contract_column' => $contract_column,
                    ]) ?>
                    <?= $this->element('ContractChecks/customer_cell', [
                        'customer' => $proposal->contract?->customer,
                        'customer_column' => $customer_column,
                    ]) ?>
                    <td><?= h($proposal->purpose->label()) ?></td>
                    <td><?= h($proposal->effective_from) ?></td>
                    <?php foreach ($dates as $date) : ?>
                        <td><?= h($on[$date]) ?></td>
                    <?php endforeach ?>
                    <td class="actions">
                        <?php if (in_array('documents', $steps, true)) : ?>
                            <?= $this->AuthLink->link(
                                __('Documents'),
                                [
                                    'plugin' => null,
                                    'controller' => 'Documents',
                                    'action' => 'manage',
                                    '?' => [
                                        'proposal_id' => $proposal->id,
                                        'agenda' => 'ContractProposals',
                                    ],
                                ],
                            ) ?>
                        <?php endif ?>
                        <?php if (in_array('send', $steps, true)) : ?>
                            <?= $this->AuthLink->link(
                                __('Record the Sending'),
                                [
                                    'plugin' => null,
                                    'controller' => 'CustomerProposals',
                                    'action' => 'send',
                                    $proposal->customer_proposal_id,
                                ],
                                ['class' => 'win-link'],
                            ) ?>
                        <?php endif ?>
                        <?php if (in_array('conclude', $steps, true)) : ?>
                            <?= $this->AuthLink->link(
                                __('Record the Signature'),
                                [
                                    'plugin' => null,
                                    'controller' => 'CustomerProposals',
                                    'action' => 'conclude',
                                    $proposal->customer_proposal_id,
                                ],
                                ['class' => 'win-link'],
                            ) ?>
                        <?php endif ?>
                        <?php if (in_array('apply', $steps, true)) : ?>
                            <?= $this->AuthLink->link(
                                __('Carry Over'),
                                [
                                    'plugin' => null,
                                    'controller' => 'CustomerProposals',
                                    'action' => 'applyChanges',
                                    $proposal->customer_proposal_id,
                                ],
                            ) ?>
                        <?php endif ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
