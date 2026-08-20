<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Address> $addresses
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

<div class="addresses index content">
    <?= $this->AuthLink->link(__('New Address'), ['action' => 'add'], ['class' => 'button float-right win-link']) ?>
    <h3><?= __('Addresses') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('customer_id') ?></th>
                    <th><?= $this->Paginator->sort('customer_id', __('Customer Number')) ?></th>
                    <th><?= $this->Paginator->sort('type') ?></th>
                    <th><?= $this->Paginator->sort('company') ?></th>
                    <th><?= $this->Paginator->sort('title') ?></th>
                    <th><?= $this->Paginator->sort('first_name') ?></th>
                    <th><?= $this->Paginator->sort('last_name') ?></th>
                    <th><?= $this->Paginator->sort('suffix') ?></th>
                    <th><?= $this->Paginator->sort('street') ?></th>
                    <th><?= $this->Paginator->sort('number') ?></th>
                    <th><?= $this->Paginator->sort('entrance') ?></th>
                    <th><?= $this->Paginator->sort('unit') ?></th>
                    <th><?= $this->Paginator->sort('city') ?></th>
                    <th><?= $this->Paginator->sort('zip') ?></th>
                    <th><?= $this->Paginator->sort('country_id') ?></th>
                    <th><?= $this->Paginator->sort(
                        'address_registry_reference',
                        __('Address Registry Reference'),
                    ) ?></th>
                    <th class="actions"><?= __('Map location') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($addresses as $address) : ?>
                <tr>
                    <td>
                        <?= $address->customer !== null ? $this->Html->link(
                            $address->customer->name ?? '(' . $address->customer->id . ')',
                            ['controller' => 'Customers', 'action' => 'view', $address->customer->id],
                        ) : '' ?>
                    </td>
                    <td><?= $address->customer !== null ? h($address->customer->number) : '' ?></td>
                    <td><?= h($address->type->label()) ?></td>
                    <td><?= h($address->company) ?></td>
                    <td><?= h($address->title) ?></td>
                    <td><?= h($address->first_name) ?></td>
                    <td><?= h($address->last_name) ?></td>
                    <td><?= h($address->suffix) ?></td>
                    <td><?= h($address->street) ?></td>
                    <td><?= h($address->number) ?></td>
                    <td><?= h($address->entrance) ?></td>
                    <td><?= h($address->unit) ?></td>
                    <td><?= h($address->city) ?></td>
                    <td><?= h($address->zip) ?></td>
                    <td>
                        <?= $address->country !== null ? $this->Html->link(
                            $address->country->name ?? '(' . $address->country->id . ')',
                            ['controller' => 'Countries', 'action' => 'view', $address->country->id],
                        ) : '' ?>
                    </td>
                    <td><?=
                        $address->address_registry_reference === null
                        || $address->address_registry_source === null ?
                            '<span style="color: red;">' . __('unknown') . '</span>'
                            :
                            h($address->address_registry_reference)
                                . ' (' . h(strtoupper($address->address_registry_source)) . ')'
                    ?></td>
                    <td class="actions">
                        <?= $address->gps_x !== null && $address->gps_y !== null ?
                            '' : '<span style="color: red;">' . __('unknown') . '</span>' ?>
                        <?= $this->element('Maps.Maps/links', [
                            'lat' => $address->gps_y,
                            'lng' => $address->gps_x,
                        ]) ?>
                    </td>
                    <td class="actions">
                        <?= $this->AuthLink->link(__('View'), ['action' => 'view', $address->id]) ?>
                        <?= $this->AuthLink->link(
                            __('Edit'),
                            ['action' => 'edit', $address->id],
                            ['class' => 'win-link'],
                        ) ?>
                        <?= $this->AuthLink->postLink(
                            __('Delete'),
                            ['action' => 'delete', $address->id],
                            ['confirm' => __('Are you sure you want to delete # {0}?', $address->id)],
                        ) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= $this->element('common/paginator') ?>
</div>
