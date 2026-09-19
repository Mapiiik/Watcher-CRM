<?php
/**
 * One of the two listings on the overview of new and ending billings.
 *
 * The two differ only in which of the billing's days they are about, so the table is written
 * once and told which. The sum below it is what that listing adds to, or takes off, a month.
 *
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Billing> $billings
 * @var string $date_field The billing's day the listing is about, `billing_from` or `billing_until`.
 * @var string $date_column What that day is called at the head of its column.
 * @var \PhpCollective\DecimalObject\Decimal $total What the listing adds up to.
 */
?>
<div class="table-responsive">
    <table>
        <thead>
            <tr>
                <th><?= h($date_column) ?></th>
                <th><?= __('Customer') ?></th>
                <th><?= __('Contract') ?></th>
                <th><?= __('Contract State') ?></th>
                <th><?= __('Service') ?></th>
                <th><?= __('Text') ?></th>
                <th><?= __('Quantity') ?></th>
                <th><?= __('Total Price') ?></th>
                <th><?= __('Installation Address') ?></th>
                <th class="actions"><?= __('Actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($billings as $billing) : ?>
            <tr style="<?= $billing->style ?>">
                <td><?= h($billing->get($date_field)) ?></td>
                <td><?=
                    $billing->customer !== null ? $this->Html->link(
                        $billing->customer->name ?? '(' . $billing->customer->id . ')',
                        ['controller' => 'Customers', 'action' => 'view', $billing->customer->id],
                    ) : '' ?></td>
                <td><?=
                    $billing->contract !== null ? $this->Html->link(
                        $billing->contract->number ?? '--',
                        [
                            'controller' => 'Contracts',
                            'action' => 'view',
                            $billing->contract->id,
                            'customer_id' => $billing->contract->customer_id,
                        ],
                    ) : '' ?></td>
                <td><?= h($billing->contract->contract_state?->name) ?></td>
                <td><?=
                    $billing->service !== null ? $this->Html->link(
                        $billing->service->name ?? '(' . $billing->service->id . ')',
                        ['controller' => 'Services', 'action' => 'view', $billing->service->id],
                    ) : '' ?></td>
                <td><?= h($billing->text) ?></td>
                <td><?= $this->Number->format($billing->quantity) ?></td>
                <td><?= $this->Number->currency($billing->total_price->toString()) ?></td>
                <td><?=
                    $billing->contract->installation_address !== null ? $this->Html->link(
                        $billing->contract->installation_address->full_address,
                        [
                            'controller' => 'Addresses',
                            'action' => 'view',
                            $billing->contract->installation_address->id,
                        ],
                    ) : '' ?></td>
                <td class="actions">
                    <?= $this->AuthLink->link(
                        __('View'),
                        ['controller' => 'Billings', 'action' => 'view', $billing->id],
                    ) ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="7"><?= __('Total') ?></th>
                <th><?= $this->Number->currency($total->toString()) ?></th>
                <th colspan="2"></th>
            </tr>
        </tfoot>
    </table>
</div>
