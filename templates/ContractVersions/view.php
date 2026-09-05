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
        </div>
    </aside>
    <div class="column column-90">
        <div class="contractVersions view content">
            <?= __('Contract No.') ?><h3><?= h($contractVersion->contract->number) ?></h3>
            <?= __('Validity') ?><h3><?= h($contractVersion->valid_from) ?> - <?= $contractVersion->valid_until ?
                h($contractVersion->valid_until) : __('indefinitely') ?></h3>
            <?php if ($contractVersion->contract !== null) : ?>
            <h5><?=
                (
                    $contractVersion->contract->service_type !== null ?
                        $contractVersion->contract->service_type->name :
                        ''
                )
                . (
                    $contractVersion->contract->installation_address !== null ?
                        ' - ' . $contractVersion->contract->installation_address->address :
                        ''
                )
                ?></h5>
            <?php endif; ?>
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
            <div class="related">
                <?= $this->AuthLink->link(
                    __('New Proposal'),
                    [
                        'controller' => 'ContractVersionProposals',
                        'action' => 'add',
                        'customer_id' => $contractVersion->contract->customer_id,
                        'contract_id' => $contractVersion->contract_id,
                        '?' => ['contract_version_id' => $contractVersion->id],
                    ],
                    ['class' => 'button button-small float-right win-link'],
                ) ?>
                <h4><?= __('Proposals') ?></h4>
                <?= $this->element('Contracts/ContractVersionProposals', [
                    'contract_version_proposals' => $contractVersion->contract_version_proposals,
                    'version_column' => false,
                ]) ?>
            </div>
        </div>
    </div>
</div>
