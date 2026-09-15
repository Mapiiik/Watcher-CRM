<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ContractVersion $contractVersion
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('Edit Contract Version'),
                ['action' => 'edit', $contractVersion->id],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->postLink(
                __('Delete Contract Version'),
                ['action' => 'delete', $contractVersion->id],
                [
                    'confirm' => __('Are you sure you want to delete # {0}?', $contractVersion->id),
                    'class' => 'side-nav-item',
                ],
            ) ?>
            <?= $this->AuthLink->link(
                __('List Contract Versions'),
                ['action' => 'index'],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->link(
                __('New Contract Version'),
                ['action' => 'add'],
                ['class' => 'side-nav-item'],
            ) ?>
            <br>
            <?= $this->AuthLink->link(
                __('Documents'),
                [
                    'plugin' => null,
                    'controller' => 'Documents',
                    'action' => 'manage',
                    'customer_id' => $contractVersion->contract->customer_id,
                    'contract_id' => $contractVersion->contract_id,
                    'contract_version_id' => $contractVersion->id,
                ],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="contractVersions view content">
            <?= $this->AuthLink->link(
                __('Documents'),
                [
                    'plugin' => null,
                    'controller' => 'Documents',
                    'action' => 'manage',
                    'customer_id' => $contractVersion->contract->customer_id,
                    'contract_id' => $contractVersion->contract_id,
                    'contract_version_id' => $contractVersion->id,
                ],
                ['class' => 'button float-right'],
            ) ?>
            <?php
            // A version is known by how long it runs. Which contract it belongs to is carried by
            // the bar across the top and by the address the page was reached at, so what is worth
            // a line under the name is what the contract is for and where it is installed.
            $about = [];
            if ($contractVersion->contract !== null) {
                if ($contractVersion->contract->service_type !== null) {
                    $about[] = $contractVersion->contract->service_type->name;
                }
                if ($contractVersion->contract->installation_address !== null) {
                    $about[] = $contractVersion->contract->installation_address->address;
                }
            }
            ?>
            <?= $this->record(
                __('Contract Version'),
                (string)$contractVersion->name,
                implode(' - ', $about),
            ) ?>
            <div class="row">
                <div class="column">
                    <table>
                        <tr>
                            <th><?= __('Contract') ?></th>
                            <td><?= $contractVersion->contract !== null ? $this->Html->link(
                                $contractVersion->contract->name ?? '(' . $contractVersion->contract->id . ')',
                                [
                                    'controller' => 'Contracts',
                                    'action' => 'view',
                                    $contractVersion->contract->id,
                                    'customer_id' => $contractVersion->contract->customer_id,
                                ],
                            ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Valid From') ?></th>
                            <td><?= h($contractVersion->valid_from) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Valid Until') ?></th>
                            <td><?= h($contractVersion->valid_until) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Obligation Until') ?></th>
                            <td><?= h($contractVersion->obligation_until) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Obligations Settled') ?></th>
                            <td><?= isset($contractVersion->obligation_until) ?
                                ($contractVersion->obligations_settled ? __('Yes') : __('No')) : '' ?></td>

                        </tr>
                        <tr>
                            <th><?= __('Sent To The Customer') ?></th>
                            <td><?= h($contractVersion->getSending()) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Conclusion Date') ?></th>
                            <td><?= h($contractVersion->conclusion_date) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Number Of Amendments') ?></th>
                            <td><?= $this->Number->format($contractVersion->number_of_amendments) ?></td>
                        </tr>
                    </table>
                </div>
                <div class="column">
                    <?= $this->element('common/audit', ['entity' => $contractVersion]) ?>
                </div>
            </div>
            <div class="text">
                <strong><?= __('Note') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($contractVersion->note)); ?>
                </blockquote>
            </div>
        </div>
    </div>
</div>
