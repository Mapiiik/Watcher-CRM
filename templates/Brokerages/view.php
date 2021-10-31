<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Brokerage $brokerage
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(__('Edit Brokerage'), ['action' => 'edit', $brokerage->id], ['class' => 'side-nav-item']) ?>
            <?= $this->AuthLink->postLink(__('Delete Brokerage'), ['action' => 'delete', $brokerage->id], ['confirm' => __('Are you sure you want to delete # {0}?', $brokerage->id), 'class' => 'side-nav-item']) ?>
            <?= $this->AuthLink->link(__('List Brokerages'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->AuthLink->link(__('New Brokerage'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-90">
        <div class="brokerages view content">
            <h3><?= h($brokerage->name) ?></h3>
            <table>
                <tr>
                    <th><?= __('Name') ?></th>
                    <td><?= h($brokerage->name) ?></td>
                </tr>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= $this->Number->format($brokerage->id) ?></td>
                </tr>
            </table>
            <div class="related">
                <h4><?= __('Related Brokerage Dealers') ?></h4>
                <?php if (!empty($brokerage->brokerage_dealers)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Dealer Id') ?></th>
                            <th><?= __('Brokerage Id') ?></th>
                            <th><?= __('Fixed') ?></th>
                            <th><?= __('Percentage') ?></th>
                            <th><?= __('Id') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($brokerage->brokerage_dealers as $brokerageDealers) : ?>
                        <tr>
                            <td><?= h($brokerageDealers->dealer_id) ?></td>
                            <td><?= h($brokerageDealers->brokerage_id) ?></td>
                            <td><?= h($brokerageDealers->fixed) ?></td>
                            <td><?= h($brokerageDealers->percentage) ?></td>
                            <td><?= h($brokerageDealers->id) ?></td>
                            <td class="actions">
                                <?= $this->AuthLink->link(__('View'), ['controller' => 'BrokerageDealers', 'action' => 'view', $brokerageDealers->id]) ?>
                                <?= $this->AuthLink->link(__('Edit'), ['controller' => 'BrokerageDealers', 'action' => 'edit', $brokerageDealers->id]) ?>
                                <?= $this->AuthLink->postLink(__('Delete'), ['controller' => 'BrokerageDealers', 'action' => 'delete', $brokerageDealers->id], ['confirm' => __('Are you sure you want to delete # {0}?', $brokerageDealers->id)]) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <div class="related">
                <h4><?= __('Related Contracts') ?></h4>
                <?php if (!empty($brokerage->contracts)) : ?>
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
                        <?php foreach ($brokerage->contracts as $contracts) : ?>
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
                                <?= $this->AuthLink->link(__('View'), ['controller' => 'Contracts', 'action' => 'view', $contracts->id]) ?>
                                <?= $this->AuthLink->link(__('Edit'), ['controller' => 'Contracts', 'action' => 'edit', $contracts->id]) ?>
                                <?= $this->AuthLink->postLink(__('Delete'), ['controller' => 'Contracts', 'action' => 'delete', $contracts->id], ['confirm' => __('Are you sure you want to delete # {0}?', $contracts->id)]) ?>
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
