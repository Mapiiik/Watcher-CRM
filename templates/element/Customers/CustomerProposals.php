<?php
/**
 * The rounds of papers put to a customer, as they are listed beside them.
 *
 * The counterpart of {@see templates/element/Contracts/ContractProposals.php} and read the same
 * way, with two columns fewer: there is no version to name and nothing is asked of the records, so
 * a round is only what it was for and where it got to.
 *
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\CustomerProposal> $customer_proposals
 */
?>
<?php if (!empty($customer_proposals)) : ?>
<div class="table-responsive">
    <table>
    <thead>
        <tr>
            <th><?= __('Effective From') ?></th>
            <th><?= __('Purpose') ?></th>
            <th><?= __('Sent To The Customer') ?></th>
            <th><?= __('Conclusion Date') ?></th>
            <th><?= __('State') ?></th>
            <th class="actions"><?= __('Actions') ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($customer_proposals as $proposal) : ?>
        <tr style="<?= $proposal->isOpen() ? '' : 'color: darkgray;' ?>">
            <td><?= h($proposal->effective_from) ?></td>
            <td><?= h($proposal->purpose->label()) ?></td>
            <td><?= h($proposal->getSending()) ?></td>
            <td><?= h($proposal->conclusion_date) ?></td>
            <td><?= h($proposal->getState()) ?></td>
            <td class="actions">
                <?= $this->AuthLink->link(
                    __('View'),
                    ['controller' => 'CustomerProposals', 'action' => 'view', $proposal->id],
                ) ?>
                <?= $this->AuthLink->link(
                    __('Edit'),
                    ['controller' => 'CustomerProposals', 'action' => 'edit', $proposal->id],
                    ['class' => 'win-link'],
                ) ?>
                <?php if ($proposal->isOpen()) : ?>
                    <?= $this->AuthLink->link(
                        $proposal->hasBeenSent()
                            ? __('Record the Sending Again')
                            : __('Record the Sending'),
                        ['controller' => 'CustomerProposals', 'action' => 'send', $proposal->id],
                        ['class' => 'win-link'],
                    ) ?>
                    <?= $this->AuthLink->link(
                        __('Record the Signature'),
                        ['controller' => 'CustomerProposals', 'action' => 'conclude', $proposal->id],
                        ['class' => 'win-link'],
                    ) ?>
                <?php endif; ?>
                <?= $this->AuthLink->postLink(
                    __('Delete'),
                    ['controller' => 'CustomerProposals', 'action' => 'delete', $proposal->id],
                    ['confirm' => __('Are you sure you want to delete # {0}?', $proposal->id)],
                ) ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
<?php endif; ?>
