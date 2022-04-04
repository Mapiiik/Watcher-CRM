<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ServiceType $serviceType
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('Edit Service Type'),
                ['action' => 'edit', $serviceType->id],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->postLink(
                __('Delete Service Type'),
                ['action' => 'delete', $serviceType->id],
                [
                    'confirm' => __('Are you sure you want to delete # {0}?', $serviceType->id),
                    'class' => 'side-nav-item',
                ]
            ) ?>
            <?= $this->AuthLink->link(__('List Service Types'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->AuthLink->link(__('New Service Type'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-90">
        <div class="serviceTypes view content">
            <h3><?= h($serviceType->name) ?></h3>
            <table>
                <tr>
                    <th><?= __('Name') ?></th>
                    <td><?= h($serviceType->name) ?></td>
                </tr>
                <tr>
                    <th><?= __('Contract Number Format') ?></th>
                    <td><?= h($serviceType->contract_number_format) ?></td>
                </tr>
                <tr>
                    <th><?= __('Activation Fee') ?></th>
                    <td><?= h($serviceType->activation_fee) ?></td>
                </tr>
                <tr>
                    <th><?= __('Activation Fee With Obligation') ?></th>
                    <td><?= h($serviceType->activation_fee_with_obligation) ?></td>
                </tr>
                <tr>
                    <th><?= __('Invoice Text') ?></th>
                    <td><?= h($serviceType->invoice_text) ?></td>
                </tr>
                <tr>
                    <th><?= __('Separate Invoice') ?></th>
                    <td><?= $serviceType->separate_invoice ? __('Yes') : __('No'); ?></td>
                </tr>
                <tr>
                    <th><?= __('Invoice With Items') ?></th>
                    <td><?= $serviceType->invoice_with_items ? __('Yes') : __('No'); ?></td>
                </tr>
                <tr>
                    <th><?= __('Installation Address Required') ?></th>
                    <td><?= $serviceType->installation_address_required ? __('Yes') : __('No'); ?></td>
                </tr>
                <tr>
                    <th><?= __('Normally With Borrowed Equipment') ?></th>
                    <td><?= $serviceType->normally_with_borrowed_equipment ? __('Yes') : __('No'); ?></td>
                </tr>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= $this->Number->format($serviceType->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Created') ?></th>
                    <td><?= h($serviceType->created) ?></td>
                </tr>
                <tr>
                    <th><?= __('Created By') ?></th>
                    <td><?= $this->Number->format($serviceType->created_by) ?></td>
                </tr>
                <tr>
                    <th><?= __('Modified') ?></th>
                    <td><?= h($serviceType->modified) ?></td>
                </tr>
                <tr>
                    <th><?= __('Modified By') ?></th>
                    <td><?= $this->Number->format($serviceType->modified_by) ?></td>
                </tr>
            </table>
            <div class="related">
                <h4><?= __('Related Contracts') ?></h4>
                <?php if (!empty($serviceType->contracts)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <th><?= __('Customer Id') ?></th>
                            <th><?= __('Installation Address Id') ?></th>
                            <th><?= __('Number') ?></th>
                            <th><?= __('Service Type Id') ?></th>
                            <th><?= __('Created') ?></th>
                            <th><?= __('Created By') ?></th>
                            <th><?= __('Modified') ?></th>
                            <th><?= __('Modified By') ?></th>
                            <th><?= __('Note') ?></th>
                            <th><?= __('Obligation Until') ?></th>
                            <th><?= __('Vip') ?></th>
                            <th><?= __('Installation Technician Id') ?></th>
                            <th><?= __('Brokerage Id') ?></th>
                            <th><?= __('Installation Date') ?></th>
                            <th><?= __('Access Description') ?></th>
                            <th><?= __('Valid From') ?></th>
                            <th><?= __('Valid Until') ?></th>
                            <th><?= __('Conclusion Date') ?></th>
                            <th><?= __('Number Of Amendments') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($serviceType->contracts as $contracts) : ?>
                        <tr>
                            <td><?= h($contracts->id) ?></td>
                            <td><?= h($contracts->customer_id) ?></td>
                            <td><?= h($contracts->installation_address_id) ?></td>
                            <td><?= h($contracts->number) ?></td>
                            <td><?= h($contracts->service_type_id) ?></td>
                            <td><?= h($contracts->created) ?></td>
                            <td><?= h($contracts->created_by) ?></td>
                            <td><?= h($contracts->modified) ?></td>
                            <td><?= h($contracts->modified_by) ?></td>
                            <td><?= h($contracts->note) ?></td>
                            <td><?= h($contracts->obligation_until) ?></td>
                            <td><?= h($contracts->vip) ?></td>
                            <td><?= h($contracts->installation_technician_id) ?></td>
                            <td><?= h($contracts->brokerage_id) ?></td>
                            <td><?= h($contracts->installation_date) ?></td>
                            <td><?= h($contracts->access_description) ?></td>
                            <td><?= h($contracts->valid_from) ?></td>
                            <td><?= h($contracts->valid_until) ?></td>
                            <td><?= h($contracts->conclusion_date) ?></td>
                            <td><?= h($contracts->number_of_amendments) ?></td>
                            <td class="actions">
                                <?= $this->AuthLink->link(
                                    __('View'),
                                    ['controller' => 'Contracts', 'action' => 'view', $contracts->id]
                                ) ?>
                                <?= $this->AuthLink->link(
                                    __('Edit'),
                                    ['controller' => 'Contracts', 'action' => 'edit', $contracts->id],
                                    ['class' => 'win-link']
                                ) ?>
                                <?= $this->AuthLink->postLink(
                                    __('Delete'),
                                    ['controller' => 'Contracts', 'action' => 'delete', $contracts->id],
                                    ['confirm' => __('Are you sure you want to delete # {0}?', $contracts->id)]
                                ) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <div class="related">
                <h4><?= __('Related Queues') ?></h4>
                <?php if (!empty($serviceType->queues)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <th><?= __('Name') ?></th>
                            <th><?= __('Caption') ?></th>
                            <th><?= __('Fup') ?></th>
                            <th><?= __('Limit') ?></th>
                            <th><?= __('Overlimit Fragment') ?></th>
                            <th><?= __('Overlimit Cost') ?></th>
                            <th><?= __('Service Type Id') ?></th>
                            <th><?= __('Speed Up') ?></th>
                            <th><?= __('Speed Down') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($serviceType->queues as $queues) : ?>
                        <tr>
                            <td><?= h($queues->id) ?></td>
                            <td><?= h($queues->name) ?></td>
                            <td><?= h($queues->caption) ?></td>
                            <td><?= h($queues->fup) ?></td>
                            <td><?= h($queues->limit) ?></td>
                            <td><?= h($queues->overlimit_fragment) ?></td>
                            <td><?= h($queues->overlimit_cost) ?></td>
                            <td><?= h($queues->service_type_id) ?></td>
                            <td><?= h($queues->speed_up) ?></td>
                            <td><?= h($queues->speed_down) ?></td>
                            <td class="actions">
                                <?= $this->AuthLink->link(
                                    __('View'),
                                    ['controller' => 'Queues', 'action' => 'view', $queues->id]
                                ) ?>
                                <?= $this->AuthLink->link(
                                    __('Edit'),
                                    ['controller' => 'Queues', 'action' => 'edit', $queues->id],
                                    ['class' => 'win-link']
                                ) ?>
                                <?= $this->AuthLink->postLink(
                                    __('Delete'),
                                    ['controller' => 'Queues', 'action' => 'delete', $queues->id],
                                    ['confirm' => __('Are you sure you want to delete # {0}?', $queues->id)]
                                ) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <div class="related">
                <h4><?= __('Related Services') ?></h4>
                <?php if (!empty($serviceType->services)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <th><?= __('Created') ?></th>
                            <th><?= __('Modified') ?></th>
                            <th><?= __('Name') ?></th>
                            <th><?= __('Price') ?></th>
                            <th><?= __('Service Type Id') ?></th>
                            <th><?= __('Queue Id') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($serviceType->services as $services) : ?>
                        <tr>
                            <td><?= h($services->id) ?></td>
                            <td><?= h($services->created) ?></td>
                            <td><?= h($services->modified) ?></td>
                            <td><?= h($services->name) ?></td>
                            <td><?= h($services->price) ?></td>
                            <td><?= h($services->service_type_id) ?></td>
                            <td><?= h($services->queue_id) ?></td>
                            <td class="actions">
                                <?= $this->AuthLink->link(
                                    __('View'),
                                    ['controller' => 'Services', 'action' => 'view', $services->id]
                                ) ?>
                                <?= $this->AuthLink->link(
                                    __('Edit'),
                                    ['controller' => 'Services', 'action' => 'edit', $services->id],
                                    ['class' => 'win-link']
                                ) ?>
                                <?= $this->AuthLink->postLink(
                                    __('Delete'),
                                    ['controller' => 'Services', 'action' => 'delete', $services->id],
                                    ['confirm' => __('Are you sure you want to delete # {0}?', $services->id)]
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
