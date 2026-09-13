<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\CustomerProposal> $customerProposals
 * @var bool $show_settled
 */
?>
<?= $this->Form->create(null, ['type' => 'get', 'valueSources' => ['query', 'context']]) ?>
<div class="row">
    <div class="column">
        <?= $this->Form->control('search', [
            'label' => __('Search'),
            'type' => 'search',
            'onchange' => $this::SUBMIT_ON_CHANGE,
        ]) ?>
        <?= $this->Form->control('show_settled', [
            'label' => __('Settled Proposals As Well'),
            'type' => 'checkbox',
            'checked' => $show_settled,
            'onchange' => $this::SUBMIT_ON_CHANGE,
        ]) ?>
    </div>
</div>
<?= $this->Form->end() ?>

<div class="customerProposals index content">
    <?= $this->AuthLink->link(
        __('New Proposal'),
        ['action' => 'add'],
        ['class' => 'button float-right'],
    ) ?>
    <?= $this->heading(__('Customer Proposals')) ?>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= __('Customer') ?></th>
                    <th><?= $this->Paginator->sort('effective_from', __('Effective From')) ?></th>
                    <th><?= $this->Paginator->sort('purpose', __('Purpose')) ?></th>
                    <th><?= $this->Paginator->sort('sent_date', __('Sent To The Customer')) ?></th>
                    <th><?= $this->Paginator->sort('conclusion_date', __('Conclusion Date')) ?></th>
                    <th><?= __('State') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($customerProposals as $customerProposal) : ?>
                <tr style="<?= $customerProposal->isOpen() ? '' : 'color: darkgray;' ?>">
                    <td><?=
                        $this->Html->link(
                            h($customerProposal->customer->name ?? ''),
                            [
                                'controller' => 'Customers',
                                'action' => 'view',
                                $customerProposal->customer_id,
                            ],
                        )
                        ?></td>
                    <td><?= h($customerProposal->effective_from) ?></td>
                    <td><?= h($customerProposal->purpose->label()) ?></td>
                    <td><?= h($customerProposal->getSending()) ?></td>
                    <td><?= h($customerProposal->conclusion_date) ?></td>
                    <td><?= h($customerProposal->getState()) ?></td>
                    <td class="actions">
                        <?= $this->AuthLink->link(
                            __('View'),
                            ['action' => 'view', $customerProposal->id],
                        ) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= $this->element('common/paginator') ?>
</div>
