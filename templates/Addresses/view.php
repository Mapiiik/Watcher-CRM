<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Address $address
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('Edit Address'), ['action' => 'edit', $address->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete Address'), ['action' => 'delete', $address->id], ['confirm' => __('Are you sure you want to delete # {0}?', $address->id), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Addresses'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Address'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-90">
        <div class="addresses view content">
            <h3><?= h($address->address) ?></h3>
            <div class="row">
                <div class="column-responsive">
                    <table>
                        <tr>
                            <th><?= __('Customer') ?></th>
                            <td><?= $address->has('customer') ? $this->Html->link($address->customer->name, ['controller' => 'Customers', 'action' => 'view', $address->customer->id]) : '' ?></td>
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
                <div class="column-responsive">
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
                            <th><?= __('City') ?></th>
                            <td><?= h($address->city) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Zip') ?></th>
                            <td><?= h($address->zip) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Country') ?></th>
                            <td><?= $address->has('country') ? $this->Html->link($address->country->name, ['controller' => 'Countries', 'action' => 'view', $address->country->id]) : '' ?></td>
                        </tr>
                    </table>
                </div>
            </div>
            <br />
            <div class="row">
                <div class="column-responsive">
                    <table>
                        <?php if ($address->has('ruian_gid')): ?>
                        <tr>
                            <th><?= __('Maps') ?></th>
                            <td>
                                <?= $this->Html->link(__('Google Maps'), 'https://maps.google.com/maps?q=' . h("{$address->gpsy},{$address->gpsx}"), ['target' => '_blank']) ?>
                                ,
                                <?= $this->Html->link(__('Mapy.cz'), 'https://mapy.cz/zakladni?source=coor&id=' . h("{$address->gpsx},{$address->gpsy}"), ['target' => '_blank']) ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <th><?= __('Ruian Gid') ?></th>
                            <td><?= $this->Number->format($address->ruian_gid) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Gpsx') ?></th>
                            <td><?= $this->Number->format($address->gpsx, ['precision' => 15]) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Gpsy') ?></th>
                            <td><?= $this->Number->format($address->gpsy, ['precision' => 15]) ?></td>
                        </tr>
                    </table>
                </div>
                <div class="column-responsive">
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
                            <td><?= $this->Number->format($address->created_by) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Modified') ?></th>
                            <td><?= h($address->modified) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Modified By') ?></th>
                            <td><?= $this->Number->format($address->modified_by) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
