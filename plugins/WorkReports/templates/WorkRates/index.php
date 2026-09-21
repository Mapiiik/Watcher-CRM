<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\WorkReports\Model\Entity\WorkRate> $records
 */
?>
<div class="work-rates index content">
    <?= $this->AuthLink->link(
        __d('work_reports', 'New Work Rate'),
        ['action' => 'add'],
        ['class' => 'button float-right win-link'],
    ) ?>
    <?= $this->heading(__d('work_reports', 'Work Rates')) ?>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('code', __d('work_reports', 'Code')) ?></th>
                    <th><?= $this->Paginator->sort('name', __d('work_reports', 'Name')) ?></th>
                    <th><?= $this->Paginator->sort('price', __d('work_reports', 'Price Per Hour')) ?></th>
                    <th><?= $this->Paginator->sort(
                        'accounting_product_code',
                        __d('work_reports', 'Accounting Product Code'),
                    ) ?></th>
                    <th><?= $this->Paginator->sort('active', __d('work_reports', 'Active')) ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($records as $record) : ?>
                <tr>
                    <td><?= h($record->code) ?></td>
                    <td><?= h($record->name) ?></td>
                    <td>
                        <?= $record->price === null
                            ? ''
                            : $this->Number->currency($record->price->toFloat()) ?>
                    </td>
                    <td><?= h($record->accounting_product_code) ?></td>
                    <td><?= $record->active ? __('Yes') : __('No') ?></td>
                    <td class="actions">
                        <?= $this->AuthLink->link(
                            __('Edit'),
                            ['action' => 'edit', $record->id],
                            ['class' => 'win-link'],
                        ) ?>
                        <?= $this->AuthLink->postLink(
                            __('Delete'),
                            ['action' => 'delete', $record->id],
                            ['confirm' => __('Are you sure you want to delete # {0}?', $record->id)],
                        ) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= $this->element('common/paginator') ?>
</div>
