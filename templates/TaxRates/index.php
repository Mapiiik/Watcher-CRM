<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\TaxRate> $taxRates
 */
?>
<?= $this->Form->create(null, ['type' => 'get', 'valueSources' => ['query', 'context']]) ?>
<div class="row">
    <div class="column">
        <?= $this->Form->control('search', [
            'label' => __('Search'),
            'type' => 'search',
            'onchange' => 'this.form.submit();',
        ]) ?>
    </div>
</div>
<?= $this->Form->end() ?>

<div class="taxRates index content">
    <?= $this->Html->link(__('New Tax Rate'), ['action' => 'add'], ['class' => 'button float-right win-link']) ?>
    <h3><?= __('Tax Rates') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('name') ?></th>
                    <th><?= $this->Paginator->sort('vat_rate') ?></th>
                    <th><?= $this->Paginator->sort('reverse_charge') ?></th>
                    <th><?= $this->Paginator->sort('accounting_assignment_code') ?></th>
                    <th><?= $this->Paginator->sort('bank_account_code') ?></th>
                    <th><?= $this->Paginator->sort('activity_code') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($taxRates as $taxRate) : ?>
                <tr>
                    <td><?= h($taxRate->name) ?></td>
                    <td><?= $this->Number->format($taxRate->vat_rate) ?></td>
                    <td><?= $taxRate->reverse_charge ? __('Yes') : __('No'); ?></td>
                    <td><?= h($taxRate->accounting_assignment_code) ?></td>
                    <td><?= h($taxRate->bank_account_code) ?></td>
                    <td><?= h($taxRate->activity_code) ?></td>
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
