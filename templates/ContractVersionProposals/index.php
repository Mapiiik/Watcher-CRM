<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\ContractVersionProposal> $contractVersionProposals
 * @var bool $show_settled
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('Draw Up a Proposal'),
                ['action' => 'add'],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->Html->link(
                $show_settled ? __('Hide settled proposals') : __('Show settled proposals'),
                ['?' => ['show_settled' => $show_settled ? null : 1]],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="contractVersionProposals index content">
            <h3><?= __('Proposals') ?></h3>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th><?= $this->Paginator->sort('Contracts.number', __('Contract')) ?></th>
                            <th><?= $this->Paginator->sort('effective_from', __('Effective From')) ?></th>
                            <th><?= $this->Paginator->sort('sent_date', __('Sent On')) ?></th>
                            <th><?= $this->Paginator->sort('conclusion_date', __('Concluded On')) ?></th>
                            <th><?= __('State') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($contractVersionProposals as $proposal) : ?>
                        <tr>
                            <td><?= h($proposal->contract->number ?? '') ?></td>
                            <td><?= h($proposal->effective_from) ?></td>
                            <td><?= h($proposal->sent_date) ?></td>
                            <td><?= h($proposal->conclusion_date) ?></td>
                            <td>
                                <?= match (true) {
                                    $proposal->hasBeenApplied() => __('Carried over'),
                                    $proposal->hasBeenRevoked() => __('Revoked'),
                                    $proposal->hasBeenConcluded() => __('Waiting to be carried over'),
                                    $proposal->hasBeenSent() => __('Sent'),
                                    default => __('Being drawn up'),
                                } ?>
                            </td>
                            <td class="actions">
                                <?= $this->AuthLink->link(
                                    __('View'),
                                    ['action' => 'view', $proposal->id],
                                ) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?= $this->element('common/paginator') ?>
        </div>
    </div>
</div>
