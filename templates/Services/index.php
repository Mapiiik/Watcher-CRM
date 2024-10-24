<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Service> $services
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

<div class="services index content">
    <?= $this->AuthLink->link(__('New Service'), ['action' => 'add'], ['class' => 'button float-right win-link']) ?>
    <h3><?= __('Services') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('name') ?></th>
                    <th><?= $this->Paginator->sort('price') ?></th>
                    <th><?= $this->Paginator->sort('service_type_id') ?></th>
                    <th><?= $this->Paginator->sort('queue_id') ?></th>
                    <th><?= $this->Paginator->sort('not_for_new_customers') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($services as $service) : ?>
                <tr>
                    <td><?= h($service->name) ?></td>
                    <td><?= $service->price === null ? '' : $this->Number->currency($service->price->toString()) ?></td>
                    <td>
                        <?= $service->__isset('service_type') ? $this->Html->link(
                            $service->service_type->name,
                            ['controller' => 'ServiceTypes', 'action' => 'view', $service->service_type->id]
                        ) : '' ?>
                    </td>
                    <td>
                        <?= $service->__isset('queue') ? $this->Html->link(
                            $service->queue->name,
                            ['controller' => 'Queues', 'action' => 'view', $service->queue->id]
                        ) : '' ?>
                    </td>
                    <td><?= $service->not_for_new_customers ? __('Yes') : __('No'); ?></td>
                    <td class="actions">
                        <?= $this->AuthLink->link(__('View'), ['action' => 'view', $service->id]) ?>
                        <?= $this->AuthLink->link(
                            __('Edit'),
                            ['action' => 'edit', $service->id],
                            ['class' => 'win-link']
                        ) ?>
                        <?= $this->AuthLink->postLink(
                            __('Delete'),
                            ['action' => 'delete', $service->id],
                            ['confirm' => __('Are you sure you want to delete # {0}?', $service->id)]
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
