<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\ContractProposal> $records
 * @var bool|null $contract_column
 * @var bool|null $customer_column
 */

$contract_column ??= true;
$customer_column ??= true;
?>
<p>
    <?= __(
        'The customer agreed to all of this and part of it could not be written, because what the'
        . ' line acted on was no longer on the contract. The rest was applied so that the proposal'
        . ' could be settled at all. What was left out is below and wants settling by hand - and'
        . ' saying so, once it is done, is what takes it off this list.',
    ) ?>
</p>
<table>
    <thead>
        <tr>
            <?php if ($customer_column) : ?>
                <th><?= __('Customer') ?></th>
            <?php endif; ?>
            <?php if ($contract_column) : ?>
                <th><?= __('Contract') ?></th>
            <?php endif; ?>
            <th><?= __('Changes Applied') ?></th>
            <th><?= __('Left Out') ?></th>
            <th class="actions"><?= __('Actions') ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($records as $proposal) : ?>
            <tr>
                <?php if ($customer_column) : ?>
                    <td><?=
                        $proposal->contract->customer !== null
                            ? $this->Html->link(
                                $proposal->contract->customer->name,
                                [
                                    'controller' => 'Customers',
                                    'action' => 'view',
                                    $proposal->contract->customer_id,
                                ],
                            )
                            : ''
                        ?></td>
                <?php endif; ?>
                <?php if ($contract_column) : ?>
                    <td><?=
                        $this->Html->link(
                            $proposal->contract->number ?? '',
                            ['controller' => 'Contracts', 'action' => 'view', $proposal->contract_id],
                        )
                        ?></td>
                <?php endif; ?>
                <td><?= h($proposal->applied) ?></td>
                <td><?=
                    implode('<br>', array_map(
                        fn(string $why): string => h($why),
                        $proposal->whatWasLeftOut(),
                    ))
                    ?></td>
                <td class="actions">
                    <?= $this->Html->link(
                        __('View'),
                        ['controller' => 'ContractProposals', 'action' => 'view', $proposal->id],
                    ) ?>
                    <?= $this->AuthLink->postLink(
                        __('Settled'),
                        [
                            'controller' => 'ContractProposals',
                            'action' => 'settleWhatWasLeftOut',
                            $proposal->id,
                        ],
                        ['confirm' => __('Has what this proposal could not write been dealt with on'
                            . ' the contract by hand?')],
                    ) ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
