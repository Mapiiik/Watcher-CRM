<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Country $country
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('Edit Country'),
                ['action' => 'edit', $country->id],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->postLink(
                __('Delete Country'),
                ['action' => 'delete', $country->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $country->id), 'class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->link(__('List Countries'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->AuthLink->link(__('New Country'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="countries view content">
            <h3><?= h($country->name) ?></h3>
            <div class="row">
                <div class="column">
                    <table>
                        <tr>
                            <th><?= __('Name') ?></th>
                            <td><?= h($country->name) ?></td>
                        </tr>
                    </table>
                </div>
                <div class="column">
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <td><?= $this->Number->format($country->id) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Created') ?></th>
                            <td><?= h($country->created) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Created By') ?></th>
                            <td><?= $country->__isset('creator') ? $this->Html->link(
                                $country->creator->username,
                                [
                                    'controller' => 'AppUsers',
                                    'action' => 'view',
                                    $country->creator->id,
                                ]
                            ) : h($country->created_by) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Modified') ?></th>
                            <td><?= h($country->modified) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Modified By') ?></th>
                            <td><?= $country->__isset('modifier') ? $this->Html->link(
                                $country->modifier->username,
                                [
                                    'controller' => 'AppUsers',
                                    'action' => 'view',
                                    $country->modifier->id,
                                ]
                            ) : h($country->modified_by) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="related">
                <h4><?= __('Related Addresses') ?></h4>
                <?php if (!empty($country->addresses)) : ?>
                <div class="table-responsive">
                <table>
                        <tr>
                            <th><?= __('Customer') ?></th>
                            <th><?= __('Customer Number') ?></th>
                            <th><?= __('Type') ?></th>
                            <th><?= __('Company') ?></th>
                            <th><?= __('Title') ?></th>
                            <th><?= __('First Name') ?></th>
                            <th><?= __('Last Name') ?></th>
                            <th><?= __('Suffix') ?></th>
                            <th><?= __('Street') ?></th>
                            <th><?= __('Number') ?></th>
                            <th><?= __('City') ?></th>
                            <th><?= __('Zip') ?></th>
                            <th><?= __('RÚIAN') ?></th>
                            <th class="actions"><?= __('Map location') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($country->addresses as $address) : ?>
                        <tr>
                            <td>
                                <?= $address->__isset('customer') ? $this->Html->link(
                                    $address->customer->name,
                                    ['controller' => 'Customers', 'action' => 'view', $address->customer->id]
                                ) : '' ?>
                            </td>
                            <td><?= $address->__isset('customer') ? h($address->customer->number) : '' ?></td>
                            <td><?= h($address->getTypeName()) ?></td>
                            <td><?= h($address->company) ?></td>
                            <td><?= h($address->title) ?></td>
                            <td><?= h($address->first_name) ?></td>
                            <td><?= h($address->last_name) ?></td>
                            <td><?= h($address->suffix) ?></td>
                            <td><?= h($address->street) ?></td>
                            <td><?= h($address->number) ?></td>
                            <td><?= h($address->city) ?></td>
                            <td><?= h($address->zip) ?></td>
                            <td><?= $address->__isset('ruian_gid') ?
                                $this->Number->format($address->ruian_gid) :
                                '<span style="color: red;">' . __('unknown') . '</span>'
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
                                <?= $this->AuthLink->link(
                                    __('View'),
                                    ['controller' => 'Addresses', 'action' => 'view', $address->id]
                                ) ?>
                                <?= $this->AuthLink->link(
                                    __('Edit'),
                                    ['controller' => 'Addresses', 'action' => 'edit', $address->id],
                                    ['class' => 'win-link']
                                ) ?>
                                <?= $this->AuthLink->postLink(
                                    __('Delete'),
                                    ['controller' => 'Addresses', 'action' => 'delete', $address->id],
                                    ['confirm' => __('Are you sure you want to delete # {0}?', $address->id)]
                                ) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
