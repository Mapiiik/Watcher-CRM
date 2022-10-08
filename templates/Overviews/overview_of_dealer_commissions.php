<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Collection\CollectionInterface $dealers
 * @var \Cake\I18n\FrozenDate $month_to_display
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(__('List Overviews'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-90">
        <div class="overviews index content">
            <h3><?= __('Overview of dealer commissions')
                . ' - '
                . $month_to_display->i18nFormat('LLLL yyyy') ?></h3>

            <?= $this->Form->create(null, ['type' => 'get', 'valueSources' => ['query', 'context']]) ?>
            <div class="row">
                <div class="column-responsive">
                    <?= $this->Form->control('month_to_display', [
                        'label' => __('Month To Display'),
                        'placeholder' => __('YYYY-MM'),
                        'type' => 'month',
                        'onchange' => 'this.form.submit();',
                    ]) ?>
                </div>
            </div>
            <?= $this->Form->end() ?>

            <?php foreach ($dealers as $dealer => $dealerCommissions) : ?>
                <hr>
                <h4><?= $dealer ?></h4>

                <?php foreach ($dealerCommissions as $dealerCommission) : ?>
                    <div class="related">
                        <h5><?= $dealerCommission->commission->name ?></h5>
                        <div><?= __('Fixed') . ': '
                            . $this->Number->currency($dealerCommission->fixed)?></div>
                        <div><?= __('Percentage') . ': '
                            . $this->Number->toPercentage($dealerCommission->percentage)?></div>

                        <br>

                        <?php if (!empty($dealerCommission->commission->contracts)) : ?>
                        <div><?= __('Total Price') . ': '
                            . $this->Number->currency($dealerCommission->commission->total_price) ?></div>
                        <div class="table-responsive">
                            <table>
                                <tr>
                                    <th><?= __('Customer') ?></th>
                                    <th><?= __('Contract') ?></th>
                                    <th><?= __('Name') ?></th>
                                    <th><?= __('Quantity') ?></th>
                                    <th><?= __('Price') ?></th>
                                    <th><?= __('Fixed Discount') ?></th>
                                    <th><?= __('Percentage Discount') ?></th>
                                    <th><?= __('Total Price') ?></th>
                                    <th><?= __('Billing From') ?></th>
                                    <th><?= __('Billing Until') ?></th>
                                    <th class="actions"><?= __('Actions') ?></th>
                                </tr>
                                <?php foreach ($dealerCommission->commission->contracts as $contract) : ?>
                                    <?php foreach ($contract->billings as $billing) : ?>
                                    <tr style="<?= $billing->style ?>">
                                        <td><?= $contract->has('customer') ?
                                            $this->Html->link(
                                                $contract->customer->name,
                                                [
                                                    'controller' => 'Customers',
                                                    'action' => 'view',
                                                    $contract->customer->id,
                                                ]
                                            ) : '' ?></td>
                                        <td><?=
                                            $this->Html->link(
                                                $contract->number,
                                                [
                                                    'controller' => 'Contracts',
                                                    'action' => 'view',
                                                    'customer_id' => $contract->customer->id,
                                                    $contract->id,
                                                ]
                                            ) ?></td>
                                        <td><?= h($billing->name) ?></td>
                                        <td><?= h($billing->quantity) ?></td>
                                        <td><?= h($billing->price) ?><?= $billing->has('service') ?
                                            ' (' . h($billing->service->price) . ')' : '' ?></td>
                                        <td><?= h($billing->fixed_discount) ?></td>
                                        <td><?= h($billing->percentage_discount) ?></td>
                                        <td><?= $this->Number->currency($billing->total_price) ?></td>
                                        <td><?= h($billing->billing_from) ?></td>
                                        <td><?= h($billing->billing_until) ?></td>
                                        <td class="actions">
                                            <?= $this->AuthLink->link(
                                                __('View'),
                                                ['controller' => 'Billings', 'action' => 'view', $billing->id]
                                            ) ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
    </div>
</div>
