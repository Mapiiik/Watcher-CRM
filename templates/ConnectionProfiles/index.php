<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\ConnectionProfile> $connectionProfiles
 */
?>
<?= $this->Form->create(null, ['type' => 'get', 'valueSources' => ['query', 'context']]) ?>
<div class="row">
    <div class="column">
        <?= $this->Form->control('search', [
            'label' => __('Search'),
            'type' => 'search',
            'onchange' => $this::SUBMIT_ON_CHANGE,
        ]) ?>
    </div>
</div>
<?= $this->Form->end() ?>

<div class="connectionProfiles index content">
    <?= $this->AuthLink->link(
        __('New Connection Profile'),
        ['action' => 'add'],
        ['class' => 'button float-right win-link'],
    ) ?>
    <?= $this->heading(__('Connection Profiles')) ?>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('name') ?></th>
                    <th><?= $this->Paginator->sort('radius_group', __('RADIUS Group')) ?></th>
                    <th><?= $this->Paginator->sort('fup_limit', __('FUP Limit')) ?></th>
                    <th><?= $this->Paginator->sort('data_limit') ?></th>
                    <th><?= $this->Paginator->sort('overlimit_fragment') ?></th>
                    <th><?= $this->Paginator->sort('overlimit_cost') ?></th>
                    <th><?= $this->Paginator->sort('speed_down') ?></th>
                    <th><?= $this->Paginator->sort('speed_up') ?></th>
                    <th><?= $this->Paginator->sort('cto_category') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($connectionProfiles as $connectionProfile) : ?>
                <tr>
                    <td><?= h($connectionProfile->name) ?></td>
                    <td><?= h($connectionProfile->radius_group) ?></td>
                    <td><?= $connectionProfile->fup_limit === null ?
                        '' : $this->Number->format($connectionProfile->fup_limit) ?></td>
                    <td><?= $connectionProfile->data_limit === null ?
                        '' : $this->Number->format($connectionProfile->data_limit) ?></td>
                    <td><?= $connectionProfile->overlimit_fragment === null ?
                        '' : $this->Number->format($connectionProfile->overlimit_fragment) ?></td>
                    <td><?= $connectionProfile->overlimit_cost === null ?
                        '' : $this->Number->currency($connectionProfile->overlimit_cost) ?></td>
                    <td><?= $connectionProfile->speed_down === null ?
                        '' : $this->Number->format($connectionProfile->speed_down) ?></td>
                    <td><?= $connectionProfile->speed_up === null ?
                        '' : $this->Number->format($connectionProfile->speed_up) ?></td>
                    <td><?= h($connectionProfile->cto_category) ?></td>
                    <td class="actions">
                        <?= $this->AuthLink->link(__('View'), ['action' => 'view', $connectionProfile->id]) ?>
                        <?= $this->AuthLink->link(
                            __('Edit'),
                            ['action' => 'edit', $connectionProfile->id],
                            ['class' => 'win-link'],
                        ) ?>
                        <?= $this->AuthLink->postLink(
                            __('Delete'),
                            ['action' => 'delete', $connectionProfile->id],
                            ['confirm' => __('Are you sure you want to delete # {0}?', $connectionProfile->id)],
                        ) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= $this->element('common/paginator') ?>
</div>
