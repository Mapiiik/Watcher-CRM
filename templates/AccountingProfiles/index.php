<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\AccountingProfile> $accountingProfiles
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

<div class="accountingProfiles index content">
    <?= $this->AuthLink->link(
        __('New Accounting Profile'),
        ['action' => 'add'],
        ['class' => 'button float-right win-link'],
    ) ?>
    <h3><?= __('Accounting Profiles') ?></h3>
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
                <?php foreach ($accountingProfiles as $accountingProfile) : ?>
                <tr>
                    <td><?= h($accountingProfile->name) ?></td>
                    <td><?= $this->Number->format($accountingProfile->vat_rate) ?></td>
                    <td><?= $accountingProfile->reverse_charge ? __('Yes') : __('No'); ?></td>
                    <td><?= h($accountingProfile->accounting_assignment_code) ?></td>
                    <td><?= h($accountingProfile->bank_account_code) ?></td>
                    <td><?= h($accountingProfile->activity_code) ?></td>
                    <td class="actions">
                        <?= $this->AuthLink->link(__('View'), ['action' => 'view', $accountingProfile->id]) ?>
                        <?= $this->AuthLink->link(
                            __('Edit'),
                            ['action' => 'edit', $accountingProfile->id],
                            ['class' => 'win-link'],
                        ) ?>
                        <?= $this->AuthLink->postLink(
                            __('Delete'),
                            ['action' => 'delete', $accountingProfile->id],
                            ['confirm' => __('Are you sure you want to delete # {0}?', $accountingProfile->id)],
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
            __('Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total'),
        ) ?></p>
    </div>
</div>
