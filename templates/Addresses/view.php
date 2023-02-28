<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Address $address
 * @var string[]|\Cake\Collection\CollectionInterface $types
 * @var string[]|\Cake\Collection\CollectionInterface $number_types
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('Edit Address'),
                ['action' => 'edit', $address->id],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->postLink(
                __('Delete Address'),
                ['action' => 'delete', $address->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $address->id), 'class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->link(__('List Addresses'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->AuthLink->link(__('New Address'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="addresses view content">
            <h3><?= h($address->address) ?></h3>
            <div class="row">
                <div class="column">
                    <table>
                        <tr>
                            <th><?= __('Customer') ?></th>
                            <td><?= $address->has('customer') ? $this->Html->link(
                                $address->customer->name,
                                ['controller' => 'Customers', 'action' => 'view', $address->customer->id]
                            ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Customer Number') ?></th>
                            <td><?= $address->has('customer') ? h($address->customer->number) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Type') ?></th>
                            <td><?= h($types[$address->type]) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Company') ?></th>
                            <td><?= h($address->company) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Title') ?></th>
                            <td><?= h($address->title) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('First Name') ?></th>
                            <td><?= h($address->first_name) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Last Name') ?></th>
                            <td><?= h($address->last_name) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Suffix') ?></th>
                            <td><?= h($address->suffix) ?></td>
                        </tr>
                    </table>
                </div>
                <div class="column">
                    <table>
                        <tr>
                            <th><?= __('Street') ?></th>
                            <td><?= h($address->street) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Number') ?></th>
                            <td><?= h($address->number) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Number Type') ?></th>
                            <td><?= h($number_types[$address->number_type]) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('City') ?></th>
                            <td><?= h($address->city) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Zip') ?></th>
                            <td><?= h($address->zip) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Country') ?></th>
                            <td><?= $address->has('country') ? $this->Html->link(
                                $address->country->name,
                                ['controller' => 'Countries', 'action' => 'view', $address->country->id]
                            ) : '' ?></td>
                        </tr>
                    </table>
                </div>
            </div>
            <br />
            <div class="row">
                <div class="column">
                    <table>
                        <tr>
                            <th class="actions"><?= __('Map location') ?></th>
                            <td class="actions">
                                <?= $address->has('gps_x') && $address->has('gps_y') ?
                                    '' : '<span style="color: red;">' . __('unknown') . '</span>' ?>
                                <?= $address->has('gps_x') && $address->has('gps_y') ? $this->Html->link(
                                    __('Google Maps'),
                                    'https://maps.google.com/maps?q='
                                        . h("{$address->gps_y},{$address->gps_x}"),
                                    ['target' => '_blank']
                                ) : '' ?>
                                <?= $address->has('gps_x') && $address->has('gps_y') ? $this->Html->link(
                                    __('Mapy.cz'),
                                    'https://mapy.cz/zakladni?source=coor&id='
                                        . h("{$address->gps_x},{$address->gps_y}"),
                                    ['target' => '_blank']
                                ) : ''?>
                            </td>
                        </tr>
                        <tr>
                            <th><?= __('RÚIAN') ?></th>
                            <td><?= $address->has('ruian_gid') ?
                                $this->Number->format($address->ruian_gid) :
                                '<span style="color: red;">' . __('unknown') . '</span>'
                            ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Manual Coordinate Setting') ?></th>
                            <td><?= $address->manual_coordinate_setting ? __('Yes') : __('No'); ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Gps Y') ?></th>
                            <td><?= $this->Number->format($address->gps_y, ['precision' => 15]) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Gps X') ?></th>
                            <td><?= $this->Number->format($address->gps_x, ['precision' => 15]) ?></td>
                        </tr>
                    </table>
                </div>
                <div class="column">
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <td><?= $this->Number->format($address->id) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Created') ?></th>
                            <td><?= h($address->created) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Created By') ?></th>
                            <td><?= $address->has('creator') ? $this->Html->link(
                                $address->creator->username,
                                [
                                    'plugin' => 'CakeDC/Users',
                                    'controller' => 'Users',
                                    'action' => 'view',
                                    $address->creator->id,
                                ]
                            ) : h($address->created_by) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Modified') ?></th>
                            <td><?= h($address->modified) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Modified By') ?></th>
                            <td><?= $address->has('modifier') ? $this->Html->link(
                                $address->modifier->username,
                                [
                                    'plugin' => 'CakeDC/Users',
                                    'controller' => 'Users',
                                    'action' => 'view',
                                    $address->modifier->id,
                                ]
                            ) : h($address->modified_by) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="text">
                <strong><?= __('Note') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($address->note)); ?>
                </blockquote>
            </div>
        </div>
    </div>
</div>
