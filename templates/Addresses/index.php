<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Address[]|\Cake\Collection\CollectionInterface $addresses
 */
?>
<div class="addresses index content">
    <?= $this->AuthLink->link(__('New Address'), ['action' => 'add'], ['class' => 'button float-right win-link']) ?>
    <h3><?= __('Addresses') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('id') ?></th>
                    <th><?= $this->Paginator->sort('customer_id') ?></th>
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
                    <th class="actions"><?= __('Maps') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($addresses as $address) : ?>
                <tr>
                    <td><?= $this->Number->format($address->id) ?></td>
                    <td>
                        <?= $address->has('customer') ? $this->Html->link(
                            $address->customer->name,
                            ['controller' => 'Customers', 'action' => 'view', $address->customer->id]
                        ) : '' ?>
                    </td>
                    <td><?= h($types[$address->type]) ?></td>
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
                        <?= $address->has('country') ? $this->Html->link(
                            $address->country->name,
                            ['controller' => 'Countries', 'action' => 'view', $address->country->id]
                        ) : '' ?>
                    </td>
                    <td class="actions">
                        <?= $address->has('ruian_gid') ? $this->Html->link(
                            __('Google Maps'),
                            'https://maps.google.com/maps?q=' . h("{$address->gpsy},{$address->gpsx}"),
                            ['target' => '_blank']
                        ) : '' ?>
                        <?= $address->has('ruian_gid') ? $this->Html->link(
                            __('Mapy.cz'),
                            'https://mapy.cz/zakladni?source=coor&id=' . h("{$address->gpsx},{$address->gpsy}"),
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
