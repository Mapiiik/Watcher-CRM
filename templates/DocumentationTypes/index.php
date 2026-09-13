<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\DocumentationType> $documentationTypes
 */
$said = fn(bool $yes): string => $yes ? __d('app_files', 'Yes') : __d('app_files', 'No');
?>
<?= $this->Form->create(null, ['type' => 'get', 'valueSources' => ['query', 'context']]) ?>
<div class="row">
    <div class="column">
        <?= $this->Form->control('search', [
            'label' => __d('app_files', 'Search'),
            'type' => 'search',
            'onchange' => $this::SUBMIT_ON_CHANGE,
        ]) ?>
    </div>
</div>
<?= $this->Form->end() ?>

<div class="documentationTypes index content">
    <?= $this->AuthLink->link(
        __d('app_files', 'New Documentation Type'),
        ['action' => 'add'],
        ['class' => 'button float-right win-link'],
    ) ?>
    <?= $this->heading(__d('app_files', 'Documentation Types')) ?>
    <p><?=
        __d(
            'app_files',
            'What documentation is filed as. A type decides what a documentation of that type has to say'
            . ' about itself, and one that is no longer offered stays on what is already'
            . ' filed under it.',
        )
        ?></p>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('position', __d('app_files', 'Position')) ?></th>
                    <th><?= $this->Paginator->sort('name', __d('app_files', 'Name')) ?></th>
                    <th><?= $this->Paginator->sort('currently_offered', __d('app_files', 'Currently Offered')) ?></th>
                    <th><?= $this->Paginator->sort('date_required', __d('app_files', 'Date Required')) ?></th>
                    <th><?= $this->Paginator->sort('customer_required', __d('app_files', 'Customer Required')) ?></th>
                    <th><?= $this->Paginator->sort('contract_required', __d('app_files', 'Contract Required')) ?></th>
                    <th><?= __d('app_files', 'Note') ?></th>
                    <th class="actions"><?= __d('app_files', 'Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($documentationTypes as $documentationType) : ?>
                <tr>
                    <td><?= $this->Number->format($documentationType->position) ?></td>
                    <td><?= h($documentationType->name) ?></td>
                    <td><?= $said($documentationType->currently_offered) ?></td>
                    <td><?= $said($documentationType->date_required) ?></td>
                    <td><?= $said($documentationType->customer_required) ?></td>
                    <td><?= $said($documentationType->contract_required) ?></td>
                    <td><?= h($documentationType->note) ?></td>
                    <td class="actions">
                        <?= $this->AuthLink->link(
                            __d('app_files', 'View'),
                            ['action' => 'view', $documentationType->id],
                        ) ?>
                        <?= $this->AuthLink->link(
                            __d('app_files', 'Edit'),
                            ['action' => 'edit', $documentationType->id],
                            ['class' => 'win-link'],
                        ) ?>
                        <?= $this->AuthLink->postLink(
                            __d('app_files', 'Delete'),
                            ['action' => 'delete', $documentationType->id],
                            ['confirm' => __d(
                                'app_files',
                                'Are you sure you want to delete {0}?',
                                $documentationType->name,
                            )],
                        ) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= $this->element('common/paginator') ?>
</div>
