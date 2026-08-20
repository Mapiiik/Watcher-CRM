<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Commission $commission
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('Edit Commission'),
                ['action' => 'edit', $commission->id],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->postLink(
                __('Delete Commission'),
                ['action' => 'delete', $commission->id],
                [
                    'confirm' => __('Are you sure you want to delete # {0}?', $commission->id),
                    'class' => 'side-nav-item',
                ],
            ) ?>
            <?= $this->AuthLink->link(__('List Commissions'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->AuthLink->link(__('New Commission'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="commissions view content">
            <h3><?= h($commission->name) ?></h3>
            <div class="row">
                <div class="column">
                    <table>
                        <tr>
                            <th><?= __('Name') ?></th>
                            <td><?= h($commission->name) ?></td>
                        </tr>
                    </table>
                </div>
                <div class="column">
                    <?= $this->element('common/audit', ['entity' => $commission]) ?>
                </div>
            </div>
            <div class="related">
                <h4><?= __('Related Dealer Commissions') ?></h4>
                <?php if (!empty($commission->dealer_commissions)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Dealer') ?></th>
                            <th><?= __('Fixed') ?></th>
                            <th><?= __('Percentage') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($commission->dealer_commissions as $dealerCommission) : ?>
                        <tr>
                            <td>
                                <?= $dealerCommission->dealer !== null ? $this->Html->link(
                                    $dealerCommission->dealer->name ?? '(' . $dealerCommission->dealer->id . ')',
                                    ['controller' => 'Customers', 'action' => 'view', $dealerCommission->dealer->id],
                                ) : '' ?>
                            </td>
                            <td><?= $dealerCommission->fixed === null ?
                                '' : $this->Number->format($dealerCommission->fixed) ?></td>
                            <td><?= $dealerCommission->percentage === null ?
                                '' : $this->Number->format($dealerCommission->percentage) ?></td>
                            <td class="actions">
                                <?= $this->AuthLink->link(
                                    __('View'),
                                    ['controller' => 'DealerCommissions', 'action' => 'view', $dealerCommission->id],
                                ) ?>
                                <?= $this->AuthLink->link(
                                    __('Edit'),
                                    ['controller' => 'DealerCommissions', 'action' => 'edit', $dealerCommission->id],
                                    ['class' => 'win-link'],
                                ) ?>
                                <?= $this->AuthLink->postLink(
                                    __('Delete'),
                                    ['controller' => 'DealerCommissions', 'action' => 'delete', $dealerCommission->id],
                                    ['confirm' => __('Are you sure you want to delete # {0}?', $dealerCommission->id)],
                                ) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <div class="related">
                <h4><?= __('Related Contracts') ?></h4>
                <?= $this->element('Contracts/related', [
                    'contracts' => $commission->contracts,
                    'contract_state_column' => true,
                    'service_type_column' => true,
                ]) ?>
            </div>
        </div>
    </div>
</div>
