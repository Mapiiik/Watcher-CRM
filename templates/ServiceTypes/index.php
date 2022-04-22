<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ServiceType[]|\Cake\Collection\CollectionInterface $serviceTypes
 */
?>
<div class="serviceTypes index content">
    <?= $this->AuthLink->link(
        __('New Service Type'),
        ['action' => 'add'],
        ['class' => 'button float-right win-link']
    ) ?>
    <h3><?= __('Service Types') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('id') ?></th>
                    <th><?= $this->Paginator->sort('name') ?></th>
                    <th><?= $this->Paginator->sort('contract_number_format') ?></th>
                    <th><?= $this->Paginator->sort('activation_fee') ?></th>
                    <th><?= $this->Paginator->sort('activation_fee_with_obligation') ?></th>
                    <th><?= $this->Paginator->sort('invoice_text') ?></th>
                    <th><?= $this->Paginator->sort('separate_invoice') ?></th>
                    <th><?= $this->Paginator->sort('invoice_with_items') ?></th>
                    <th><?= $this->Paginator->sort('installation_address_required') ?></th>
                    <th><?= $this->Paginator->sort('access_point_required') ?></th>
                    <th><?= $this->Paginator->sort('normally_with_borrowed_equipment') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($serviceTypes as $serviceType) : ?>
                <tr>
                    <td><?= $this->Number->format($serviceType->id) ?></td>
                    <td><?= h($serviceType->name) ?></td>
                    <td><?= h($serviceType->contract_number_format) ?></td>
                    <td><?= h($serviceType->activation_fee) ?></td>
                    <td><?= h($serviceType->activation_fee_with_obligation) ?></td>
                    <td><?= h($serviceType->invoice_text) ?></td>
                    <td><?= $serviceType->separate_invoice ? __('Yes') : __('No'); ?></td>
                    <td><?= $serviceType->invoice_with_items ? __('Yes') : __('No'); ?></td>
                    <td><?= $serviceType->installation_address_required ? __('Yes') : __('No'); ?></td>
                    <td><?= $serviceType->access_point_required ? __('Yes') : __('No'); ?></td>
                    <td><?= $serviceType->normally_with_borrowed_equipment ? __('Yes') : __('No'); ?></td>
                    <td class="actions">
                        <?= $this->AuthLink->link(__('View'), ['action' => 'view', $serviceType->id]) ?>
                        <?= $this->AuthLink->link(
                            __('Edit'),
                            ['action' => 'edit', $serviceType->id],
                            ['class' => 'win-link']
                        ) ?>
                        <?= $this->AuthLink->postLink(
                            __('Delete'),
                            ['action' => 'delete', $serviceType->id],
                            ['confirm' => __('Are you sure you want to delete # {0}?', $serviceType->id)]
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
