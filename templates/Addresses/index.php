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
            'onchange' => 'this.form.submit();',
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
                    <th><?= $this->Paginator->sort('city') ?></th>
                    <th><?= $this->Paginator->sort('zip') ?></th>
                    <th><?= $this->Paginator->sort('country_id') ?></th>
                    <th><?= $this->Paginator->sort('ruian_gid', __('RÚIAN')) ?></th>
                    <th class="actions"><?= __('Map location') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($addresses as $address) : ?>
                <tr>
                    <td>
                        <?= $address->__isset('customer') ? $this->Html->link(
                            $address->customer->name,
                            ['controller' => 'Customers', 'action' => 'view', $address->customer->id]
                        ) : '' ?>
                    </td>
                    <td><?= $address->__isset('customer') ? h($address->customer->number) : '' ?></td>
                    <td><?= h($address->type->label()) ?></td>
                    <td><?= h($address->company) ?></td>
                    <td><?= h($address->title) ?></td>
                    <td><?= h($address->first_name) ?></td>
                    <td><?= h($address->last_name) ?></td>
                    <td><?= h($address->suffix) ?></td>
                    <td><?= h($address->street) ?></td>
                    <td><?= h($address->number) ?></td>
                    <td><?= h($address->city) ?></td>
                    <td><?= h($address->zip) ?></td>
                    <td>
                        <?= $address->__isset('country') ? $this->Html->link(
                            $address->country->name,
                            ['controller' => 'Countries', 'action' => 'view', $address->country->id]
                        ) : '' ?>
                    </td>
                    <td><?= $address->ruian_gid === null ?
                        '<span style="color: red;">' . __('unknown') . '</span>' :
                        $this->Number->format($address->ruian_gid)
                    ?></td>
                    <td class="actions">
                        <?= $address->__isset('gps_x') && $address->__isset('gps_y') ?
                            '' : '<span style="color: red;">' . __('unknown') . '</span>' ?>
                        <?= $address->__isset('gps_x') && $address->__isset('gps_y') ? $this->Html->link(
                            __('Google Maps'),
                            'https://maps.google.com/maps?q='
                                . h("{$address->gps_y},{$address->gps_x}"),
                            ['target' => '_blank']
                        ) : '' ?>
                        <?= $address->__isset('gps_x') && $address->__isset('gps_y') ? $this->Html->link(
                            __('Mapy.cz'),
                            'https://mapy.cz/zakladni?source=coor&id='
                                . h("{$address->gps_x},{$address->gps_y}"),
                            ['target' => '_blank']
                        ) : ''?>
                    </td>
                    <td class="actions">
                        <?= $this->AuthLink->link(__('View'), ['action' => 'view', $address->id]) ?>
                        <?= $this->AuthLink->link(
                            __('Edit'),
                            ['action' => 'edit', $address->id],
                            ['class' => 'win-link']
                        ) ?>
                        <?= $this->AuthLink->postLink(
                            __('Delete'),
                            ['action' => 'delete', $address->id],
                            ['confirm' => __('Are you sure you want to delete # {0}?', $address->id)]
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
