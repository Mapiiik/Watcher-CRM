<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Contract[]|\Cake\Collection\CollectionInterface $contracts
 */
?>
<div class="contracts index content">
    <?= $this->AuthLink->link(__('New Contract'), ['action' => 'add'], ['class' => 'button float-right win-link']) ?>
    <h3><?= __('Contracts') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('id') ?></th>
                    <th><?= $this->Paginator->sort('customer_id') ?></th>
                    <th><?= $this->Paginator->sort('service_type_id') ?></th>
                    <th><?= $this->Paginator->sort('number') ?></th>
                    <th><?= $this->Paginator->sort('installation_address_id') ?></th>
                    <th><?= $this->Paginator->sort('conclusion_date') ?></th>
                    <th><?= $this->Paginator->sort('number_of_amendments') ?></th>
                    <th><?= $this->Paginator->sort('valid_from') ?></th>
                    <th><?= $this->Paginator->sort('valid_until') ?></th>
                    <th><?= $this->Paginator->sort('obligation_until') ?></th>
                    <th><?= $this->Paginator->sort('vip') ?></th>
                    <th><?= $this->Paginator->sort('access_point_id') ?></th>
                    <th><?= $this->Paginator->sort('installation_date') ?></th>
                    <th><?= $this->Paginator->sort('installation_technician_id') ?></th>
                    <th><?= $this->Paginator->sort('commission_id') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($contracts as $contract) : ?>
                <tr>
                    <td><?= $this->Number->format($contract->id) ?></td>
                    <td>
                        <?= $contract->has('customer') ? $this->Html->link(
                            $contract->customer->name,
                            ['controller' => 'Customers', 'action' => 'view', $contract->customer->id]
                        ) : '' ?>
                    </td>
                    <td>
                        <?= $contract->has('service_type') ? $this->Html->link(
                            $contract->service_type->name,
                            ['controller' => 'ServiceTypes', 'action' => 'view', $contract->service_type->id]
                        ) : '' ?>
                    </td>
                    <td><?= h($contract->number) ?></td>
                    <td>
                        <?= $contract->has('installation_address') ? $this->Html->link(
                            $contract->installation_address->full_address,
                            ['controller' => 'Addresses', 'action' => 'view', $contract->installation_address->id]
                        ) : '' ?>
                    </td>
                    <td><?= h($contract->conclusion_date) ?></td>
                    <td><?= $this->Number->format($contract->number_of_amendments) ?></td>
                    <td><?= h($contract->valid_from) ?></td>
                    <td><?= h($contract->valid_until) ?></td>
                    <td><?= h($contract->obligation_until) ?></td>
                    <td><?= $contract->vip ? __('Yes') : __('No'); ?></td>
                    <td><?= $contract->has('access_point') ? h($contract->access_point->name) : '' ?></td>
                    <td><?= h($contract->installation_date) ?></td>
                    <td>
                        <?= $contract->has('installation_technician') ? $this->Html->link(
                            $contract->installation_technician->name,
                            ['controller' => 'Customers', 'action' => 'view', $contract->installation_technician->id]
                        ) : '' ?>
                    </td>
                    <td>
                        <?= $contract->has('commission') ? $this->Html->link(
                            $contract->commission->name,
                            ['controller' => 'Commissions', 'action' => 'view', $contract->commission->id]
                        ) : '' ?>
                    </td>
                    <td class="actions">
                        <?= $this->AuthLink->link(__('View'), ['action' => 'view', $contract->id]) ?>
                        <?= $this->AuthLink->link(
                            __('Edit'),
                            ['action' => 'edit', $contract->id],
                            ['class' => 'win-link']
                        ) ?>
                        <?= $this->AuthLink->postLink(
                            __('Delete'),
                            ['action' => 'delete', $contract->id],
                            ['confirm' => __('Are you sure you want to delete # {0}?', $contract->id)]
                        ) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="paginator">
        <ul class="pagination">
            <?= $this->Paginator->first('<< ' . __('first')) ?>
            <?= $this->Paginator->prev('< ' . __('previous')) ?>
            <?= $this->Paginator->numbers() ?>
            <?= $this->Paginator->next(__('next') . ' >') ?>
            <?= $this->Paginator->last(__('last') . ' >>') ?>
        </ul>
        <p><?= $this->Paginator->counter(
            __('Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total')
        ) ?></p>
    </div>
</div>
