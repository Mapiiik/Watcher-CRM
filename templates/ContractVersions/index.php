<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\ContractVersion> $contractVersions
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

<div class="contractVersions index content">
    <?= $this->AuthLink->link(
        __('New Contract Version'),
        ['action' => 'add'],
        ['class' => 'button float-right win-link']
    ) ?>
    <h3><?= __('Contract Versions') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('contract_id') ?></th>
                    <th><?= $this->Paginator->sort('valid_from') ?></th>
                    <th><?= $this->Paginator->sort('valid_until') ?></th>
                    <th><?= $this->Paginator->sort('obligation_until') ?></th>
                    <th><?= $this->Paginator->sort('conclusion_date') ?></th>
                    <th><?= $this->Paginator->sort('number_of_amendments') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($contractVersions as $contractVersion) : ?>
                <tr>
                    <td><?=
                        $contractVersion->has('contract') ? $this->Html->link(
                            $contractVersion->contract->name,
                            ['controller' => 'Contracts', 'action' => 'view', $contractVersion->contract->id]
                        ) : '' ?></td>
                    <td><?= h($contractVersion->valid_from) ?></td>
                    <td><?= h($contractVersion->valid_until) ?></td>
                    <td><?= h($contractVersion->obligation_until) ?></td>
                    <td><?= h($contractVersion->conclusion_date) ?></td>
                    <td><?= $this->Number->format($contractVersion->number_of_amendments) ?></td>
                    <td class="actions">
                        <?= $this->AuthLink->link(
                            __('View'),
                            ['action' => 'view', $contractVersion->id]
                        ) ?>
                        <?= $this->AuthLink->link(
                            __('Edit'),
                            ['action' => 'edit', $contractVersion->id],
                            ['class' => 'win-link']
                        ) ?>
                        <?= $this->AuthLink->postLink(
                            __('Delete'),
                            ['action' => 'delete', $contractVersion->id],
                            ['confirm' => __('Are you sure you want to delete # {0}?', $contractVersion->id)]
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
