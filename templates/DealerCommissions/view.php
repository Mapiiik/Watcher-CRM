<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\DealerCommission $dealerCommission
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('Edit Dealer Commission'),
                ['action' => 'edit', $dealerCommission->id],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->postLink(
                __('Delete Dealer Commission'),
                ['action' => 'delete', $dealerCommission->id],
                [
                    'confirm' => __('Are you sure you want to delete # {0}?', $dealerCommission->id),
                    'class' => 'side-nav-item',
                ]
            ) ?>
            <?= $this->AuthLink->link(
                __('List Dealer Commissions'),
                ['action' => 'index'],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->link(
                __('New Dealer Commission'),
                ['action' => 'add'],
                ['class' => 'side-nav-item']
            ) ?>
        </div>
    </aside>
    <div class="column-responsive column-90">
        <div class="dealerCommissions view content">
            <h3><?= h($dealerCommission->id) ?></h3>
            <table>
                <tr>
                    <th><?= __('Dealer') ?></th>
                    <td><?= $dealerCommission->has('dealer') ? $this->Html->link(
                        $dealerCommission->dealer->name,
                        ['controller' => 'Customers', 'action' => 'view', $dealerCommission->dealer->id]
                    ) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Commission') ?></th>
                    <td><?= $dealerCommission->has('commission') ? $this->Html->link(
                        $dealerCommission->commission->name,
                        ['controller' => 'Commissions', 'action' => 'view', $dealerCommission->commission->id]
                    ) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Fixed') ?></th>
                    <td><?= $this->Number->format($dealerCommission->fixed) ?></td>
                </tr>
                <tr>
                    <th><?= __('Percentage') ?></th>
                    <td><?= $this->Number->format($dealerCommission->percentage) ?></td>
                </tr>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= $this->Number->format($dealerCommission->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Created') ?></th>
                    <td><?= h($dealerCommission->created) ?></td>
                </tr>
                <tr>
                    <th><?= __('Created By') ?></th>
                    <td><?= $dealerCommission->has('creator') ? $this->Html->link(
                        $dealerCommission->creator->username,
                        [
                            'plugin' => 'CakeDC/Users',
                            'controller' => 'Users',
                            'action' => 'view',
                             $dealerCommission->creator->id,
                        ]
                    ) : h($dealerCommission->created_by) ?></td>
                </tr>
                <tr>
                    <th><?= __('Modified') ?></th>
                    <td><?= h($dealerCommission->modified) ?></td>
                </tr>
                <tr>
                    <th><?= __('Modified By') ?></th>
                    <td><?= $dealerCommission->has('modifier') ? $this->Html->link(
                        $dealerCommission->modifier->username,
                        [
                            'plugin' => 'CakeDC/Users',
                            'controller' => 'Users',
                            'action' => 'view',
                            $dealerCommission->modifier->id,
                        ]
                    ) : h($dealerCommission->modified_by) ?></td>
                </tr>
            </table>
        </div>
    </div>
</div>
