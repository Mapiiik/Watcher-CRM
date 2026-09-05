<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\ContractVersionProposal> $contractVersionProposals
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

<div class="contractVersionProposals index content">
    <?= $this->AuthLink->link(
        __('Draw Up a Proposal'),
        ['action' => 'add'],
        ['class' => 'button float-right win-link'],
    ) ?>
    <h3><?= __('Contract Version Proposals') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('Contracts.number', __('Contract')) ?></th>
                    <th><?= $this->Paginator->sort('ContractVersions.valid_from', __('Contract Version')) ?></th>
                    <th><?= $this->Paginator->sort('purpose', __('What This Is For')) ?></th>
                    <th><?= $this->Paginator->sort('effective_from') ?></th>
                    <th><?= $this->Paginator->sort('sent_date', __('Sent To The Customer')) ?></th>
                    <th><?= $this->Paginator->sort('conclusion_date') ?></th>
                    <th><?= __('State') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($contractVersionProposals as $contractVersionProposal) : ?>
                    <?php
                    $contract = $contractVersionProposal->contract;
                    $version = $contractVersionProposal->contract_version;
                    ?>
                <tr style="<?= $contractVersionProposal->isOpen() ? '' : 'color: darkgray;' ?>">
                    <td><?=
                        $contract !== null ? $this->Html->link(
                            $contract->name ?? '(' . $contract->id . ')',
                            [
                                'controller' => 'Contracts',
                                'action' => 'view',
                                $contract->id,
                                'customer_id' => $contract->customer_id,
                            ],
                        ) : '' ?></td>
                    <td><?=
                        $version !== null ? $this->Html->link(
                            $version->valid_from . ' - ' . ($version->valid_until ?: __('indefinitely')),
                            [
                                'controller' => 'ContractVersions',
                                'action' => 'view',
                                $version->id,
                            ],
                        ) : '' ?></td>
                    <td><?= h($contractVersionProposal->purpose->label()) ?></td>
                    <td><?= h($contractVersionProposal->effective_from) ?></td>
                    <td><?= h($contractVersionProposal->getSending()) ?></td>
                    <td><?= h($contractVersionProposal->conclusion_date) ?></td>
                    <td><?= h($contractVersionProposal->getState()) ?></td>
                    <td class="actions">
                        <?= $this->AuthLink->link(
                            __('View'),
                            ['action' => 'view', $contractVersionProposal->id],
                        ) ?>
                        <?= $this->AuthLink->link(
                            __('Edit'),
                            ['action' => 'edit', $contractVersionProposal->id],
                            ['class' => 'win-link'],
                        ) ?>
                        <?= $this->AuthLink->postLink(
                            __('Delete'),
                            ['action' => 'delete', $contractVersionProposal->id],
                            ['confirm' => __(
                                'Are you sure you want to delete # {0}?',
                                $contractVersionProposal->id,
                            )],
                        ) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= $this->element('common/paginator') ?>
</div>
