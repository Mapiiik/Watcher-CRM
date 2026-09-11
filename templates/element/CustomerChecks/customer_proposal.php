<?php
/**
 * A round of papers put to the customer, and how far it has got.
 *
 * Shared by the checks whose finding is a round rather than the customer: showing all three days
 * at once is what makes the missing step readable without saying so.
 *
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\CustomerProposal> $records
 * @var bool|null $customer_column
 */

$customer_column ??= true;
?>
<div class="table-responsive">
    <table>
        <thead>
            <tr>
                <?php if ($customer_column) : ?>
                    <th><?= __('Customer') ?></th>
                <?php endif ?>
                <th><?= __('Purpose') ?></th>
                <th><?= __('Effective From') ?></th>
                <th><?= __('Sent Date') ?></th>
                <th><?= __('Conclusion Date') ?></th>
                <th class="actions"><?= __('Actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($records as $proposal) : ?>
                <tr>
                    <?php if ($customer_column) : ?>
                        <td class="dashboard-wrap">
                            <?= $this->Html->link(
                                $proposal->customer->name_for_lists,
                                ['controller' => 'Customers', 'action' => 'view', $proposal->customer_id],
                            ) ?>
                        </td>
                    <?php endif ?>
                    <td><?= h($proposal->purpose->label()) ?></td>
                    <td><?= h($proposal->effective_from) ?></td>
                    <td><?= h($proposal->sent_date) ?></td>
                    <td><?= h($proposal->conclusion_date) ?></td>
                    <td class="actions">
                        <?= $this->AuthLink->link(
                            __('View Proposal'),
                            ['controller' => 'CustomerProposals', 'action' => 'view', $proposal->id],
                        ) ?>
                        <?= $this->AuthLink->link(
                            __('Proposal Documents'),
                            ['controller' => 'CustomerProposals', 'action' => 'documents', $proposal->id],
                        ) ?>
                    </td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
</div>
