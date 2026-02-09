<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\ServiceOverride> $serviceOverrides
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
<div class="row">
    <div class="column">
        <?= $this->Form->control('include_future', [
            'label' => __('Include Future'),
            'type' => 'checkbox',
            'onchange' => 'this.form.submit();',
        ]) ?>
    </div>
    <div class="column">
        <?= $this->Form->control('include_past', [
            'label' => __('Include Past'),
            'type' => 'checkbox',
            'onchange' => 'this.form.submit();',
        ]) ?>
    </div>
    <div class="column">
        <?= $this->Form->control('include_revoked', [
            'label' => __('Include Revoked'),
            'type' => 'checkbox',
            'onchange' => 'this.form.submit();',
        ]) ?>
    </div>
</div>
<?= $this->Form->end() ?>

<div class="serviceOverrides index content">
    <?= $this->AuthLink->link(
        __('New Service Override'),
        ['action' => 'add'],
        ['class' => 'button float-right win-link'],
    ) ?>
    <h3><?= __('Service Overrides') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('contract_id') ?></th>
                    <th><?= $this->Paginator->sort('service_id') ?></th>
                    <th><?= $this->Paginator->sort('valid_from') ?></th>
                    <th><?= $this->Paginator->sort('valid_until') ?></th>
                    <th><?= $this->Paginator->sort('revoked') ?></th>
                    <th><?= __('Active') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($serviceOverrides as $serviceOverride) : ?>
                <tr>
                    <td><?=
                        $serviceOverride->hasValue('contract') ? $this->Html->link(
                            $serviceOverride->contract->name,
                            [
                                'controller' => 'Contracts',
                                'action' => 'view',
                                $serviceOverride->contract->id,
                                'customer_id' => $serviceOverride->contract->customer_id,
                            ],
                        ) : '' ?></td>
                    <td><?=
                        $serviceOverride->hasValue('service') ? $this->Html->link(
                            $serviceOverride->service->name,
                            [
                                'controller' => 'Services',
                                'action' => 'view',
                                $serviceOverride->service->id,
                            ],
                        ) : '' ?></td>
                    <td><?= h($serviceOverride->valid_from) ?></td>
                    <td><?= h($serviceOverride->valid_until) ?></td>
                    <td><?= h($serviceOverride->revoked) ?></td>
                    <td><?= $serviceOverride->isActive() ? __('Yes') : __('No'); ?></td>
                    <td class="actions">
                        <?= $this->AuthLink->link(
                            __('View'),
                            ['action' => 'view', $serviceOverride->id],
                        ) ?>
                        <?= $this->AuthLink->link(
                            __('Edit'),
                            ['action' => 'edit', $serviceOverride->id],
                            ['class' => 'win-link'],
                        ) ?>
                        <?= $this->AuthLink->postLink(
                            __('Revoke'),
                            ['action' => 'revoke', $serviceOverride->id],
                            [
                                'confirm' => __(
                                    'Are you sure you want to revoke service override {0}?',
                                    $serviceOverride->id,
                                ),
                            ],
                        ) ?>
                        <?= $this->AuthLink->postLink(
                            __('Delete'),
                            ['action' => 'delete', $serviceOverride->id],
                            ['confirm' => __('Are you sure you want to delete # {0}?', $serviceOverride->id)],
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