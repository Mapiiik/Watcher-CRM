<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\AvailableConnection> $availableConnections
 * @var array<string, array<string, string>> $accessTechnologies
 * @var array<string, string> $origins
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
    <div class="column">
        <?= $this->Form->control('access_technology', [
            'options' => $accessTechnologies,
            'empty' => true,
            'onchange' => $this::SUBMIT_ON_CHANGE,
        ]) ?>
    </div>
    <div class="column">
        <?= $this->Form->control('origin', [
            'options' => $origins,
            'empty' => true,
            'onchange' => $this::SUBMIT_ON_CHANGE,
        ]) ?>
        <?= $this->Form->control('show_retired', [
            'label' => __('Show Retired'),
            'type' => 'checkbox',
            'onchange' => $this::SUBMIT_ON_CHANGE,
        ]) ?>
    </div>
</div>
<?= $this->Form->end() ?>

<div class="availableConnections index content">
    <?= $this->AuthLink->link(
        __('New Available Connection'),
        ['action' => 'add'],
        ['class' => 'button float-right win-link'],
    ) ?>
    <?= $this->heading(__('Available Connections')) ?>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('address_label', __('Address')) ?></th>
                    <th><?= $this->Paginator->sort('address_registry_reference', __('Address Point')) ?></th>
                    <th><?= $this->Paginator->sort('access_technology') ?></th>
                    <th><?= $this->Paginator->sort('speed_down_max') ?></th>
                    <th><?= $this->Paginator->sort('speed_up_max') ?></th>
                    <th><?= $this->Paginator->sort('origin') ?></th>
                    <th><?= $this->Paginator->sort('retired') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($availableConnections as $availableConnection) : ?>
                <tr>
                    <td><?= h($availableConnection->address_label) ?></td>
                    <td><?= h($availableConnection->address_registry_reference)
                        . ' (' . h(strtoupper($availableConnection->address_registry_source)) . ')' ?></td>
                    <td><?= h($availableConnection->access_technology->label()) ?></td>
                    <td><?= $this->Number->format($availableConnection->speed_down_max) ?></td>
                    <td><?= $this->Number->format($availableConnection->speed_up_max) ?></td>
                    <td><?= h($availableConnection->origin->label()) ?></td>
                    <td><?= h($availableConnection->retired) ?></td>
                    <td class="actions">
                        <?= $this->AuthLink->link(__('View'), ['action' => 'view', $availableConnection->id]) ?>
                        <?= $this->AuthLink->link(
                            __('Edit'),
                            ['action' => 'edit', $availableConnection->id],
                            ['class' => 'win-link'],
                        ) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= $this->element('common/paginator') ?>
</div>
