<?php
/**
 * Folders, however many records they are spread over.
 *
 * Without any nesting this is everything there is, and the columns saying who each folder belongs
 * to are what makes that worth reading. Under a customer or a connection those columns say what
 * the page already says, but they cost a line and they keep one template instead of three.
 *
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Documentation> $documentations
 * @var array<string, string> $kinds
 */
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
    <div class="column">
        <?= $this->Form->control('documentation_type_id', [
            'label' => __d('app_files', 'Documentation Type'),
            'options' => $kinds,
            'empty' => true,
            'onchange' => $this::SUBMIT_ON_CHANGE,
        ]) ?>
    </div>
</div>
<?= $this->Form->end() ?>

<div class="documentations index content">
    <?= $this->AuthLink->link(
        __d('app_files', 'New Documentation'),
        ['action' => 'add'],
        ['class' => 'button float-right win-link'],
    ) ?>
    <h3><?= __d('app_files', 'Documentation') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= __d('app_files', 'Documentation') ?></th>
                    <th><?= __d('app_files', 'Documentation Type') ?></th>
                    <th><?= $this->Paginator->sort('happened_on', __d('app_files', 'Happened On')) ?></th>
                    <th><?= __d('app_files', 'Customer') ?></th>
                    <th><?= __d('app_files', 'Contract') ?></th>
                    <th class="actions"><?= __d('app_files', 'Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($documentations as $documentation) : ?>
                <tr>
                    <?php $where = ['action' => 'view', $documentation->id] ?>
                    <td><?= $this->AuthLink->link($documentation->heading, $where) ?></td>
                    <td><?= h($documentation->documentation_type->name ?? '') ?></td>
                    <td><?= h($documentation->happened_on) ?></td>
                    <td><?=
                        $documentation->customer === null ? '' : $this->AuthLink->link(
                            $documentation->customer->name_for_lists,
                            ['controller' => 'Customers', 'action' => 'view', $documentation->customer_id],
                        )
                        ?></td>
                    <td><?=
                        $documentation->contract === null ? '' : $this->AuthLink->link(
                            (string)$documentation->contract->number,
                            ['controller' => 'Contracts', 'action' => 'view', $documentation->contract_id],
                        )
                        ?></td>
                    <td class="actions">
                        <?= $this->AuthLink->link(
                            __d('app_files', 'Edit'),
                            ['action' => 'edit', $documentation->id],
                            ['class' => 'win-link'],
                        ) ?>
                        <?= $this->AuthLink->postLink(
                            __d('app_files', 'Delete'),
                            ['action' => 'delete', $documentation->id],
                            ['confirm' => __d(
                                'app_files',
                                'Delete {0} and everything in it?',
                                $documentation->heading,
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
