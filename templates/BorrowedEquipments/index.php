<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\BorrowedEquipment[]|\Cake\Collection\CollectionInterface $borrowedEquipments
 */
?>
<?= $this->Form->create(null, ['type' => 'get', 'valueSources' => ['query', 'context']]) ?>
<div class="row">
    <div class="column-responsive">
        <?= $this->Form->control('search', [
            'label' => __('Search'),
            'type' => 'search',
            'onchange' => 'this.form.submit();',
        ]) ?>
    </div>
</div>
<?= $this->Form->end() ?>

<div class="borrowedEquipments index content">
    <?= $this->AuthLink->link(
        __('New Borrowed Equipment'),
        ['action' => 'add'],
        ['class' => 'button float-right win-link']
    ) ?>
    <h3><?= __('Borrowed Equipments') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('customer_id') ?></th>
                    <th><?= $this->Paginator->sort('customer_id', __('Customer Number')) ?></th>
                    <th><?= $this->Paginator->sort('contract_id') ?></th>
                    <th><?= $this->Paginator->sort('equipment_type_id') ?></th>
                    <th><?= $this->Paginator->sort('serial_number') ?></th>
                    <th><?= $this->Paginator->sort('borrowed_from') ?></th>
                    <th><?= $this->Paginator->sort('borrowed_until') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($borrowedEquipments as $borrowedEquipment) : ?>
                <tr style="<?= $borrowedEquipment->style ?>">
                    <td>
                        <?= $borrowedEquipment->has('customer') ? $this->Html->link(
                            $borrowedEquipment->customer->name,
                            ['controller' => 'Customers', 'action' => 'view', $borrowedEquipment->customer->id]
                        ) : '' ?>
                    </td>
                    <td><?= $borrowedEquipment->has('customer') ? h($borrowedEquipment->customer->number) : '' ?></td>
                    <td>
                        <?= $borrowedEquipment->has('contract') ? $this->Html->link(
                            $borrowedEquipment->contract->number,
                            ['controller' => 'Contracts', 'action' => 'view', $borrowedEquipment->contract->id]
                        ) : '' ?>
                    </td>
                    <td>
                        <?= $borrowedEquipment->has('equipment_type') ? $this->Html->link(
                            $borrowedEquipment->equipment_type->name,
                            [
                                'controller' => 'EquipmentTypes',
                                'action' => 'view',
                                $borrowedEquipment->equipment_type->id,
                            ]
                        ) : '' ?>
                    </td>
                    <td><?= h($borrowedEquipment->serial_number) ?></td>
                    <td><?= h($borrowedEquipment->borrowed_from) ?></td>
                    <td><?= h($borrowedEquipment->borrowed_until) ?></td>
                    <td class="actions">
                        <?= $this->AuthLink->link(__('View'), ['action' => 'view', $borrowedEquipment->id]) ?>
                        <?= $this->AuthLink->link(
                            __('Edit'),
                            ['action' => 'edit', $borrowedEquipment->id],
                            ['class' => 'win-link']
                        ) ?>
                        <?= $this->AuthLink->postLink(
                            __('Delete'),
                            ['action' => 'delete', $borrowedEquipment->id],
                            ['confirm' => __('Are you sure you want to delete # {0}?', $borrowedEquipment->id)]
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
