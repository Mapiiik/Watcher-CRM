<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\TaxRate[]|\Cake\Collection\CollectionInterface $taxRates
 */
?>
<div class="taxRates index content">
    <?= $this->Html->link(__('New Tax Rate'), ['action' => 'add'], ['class' => 'button float-right win-link']) ?>
    <h3><?= __('Tax Rates') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('id') ?></th>
                    <th><?= $this->Paginator->sort('name') ?></th>
                    <th><?= $this->Paginator->sort('vat_rate') ?></th>
                    <th><?= $this->Paginator->sort('reverse_charge') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($taxRates as $taxRate) : ?>
                <tr>
                    <td><?= $this->Number->format($taxRate->id) ?></td>
                    <td><?= h($taxRate->name) ?></td>
                    <td><?= $this->Number->format($taxRate->vat_rate) ?></td>
                    <td><?= $taxRate->reverse_charge ? __('Yes') : __('No'); ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('View'), ['action' => 'view', $taxRate->id]) ?>
                        <?= $this->Html->link(
                            __('Edit'),
                            ['action' => 'edit', $taxRate->id],
                            ['class' => 'win-link']
                        ) ?>
                        <?= $this->Form->postLink(
                            __('Delete'),
                            ['action' => 'delete', $taxRate->id],
                            ['confirm' => __('Are you sure you want to delete # {0}?', $taxRate->id)]
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
