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
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->postLink(
                __('Delete Contract Version'),
                ['action' => 'delete', $contractVersion->id],
                [
                    'confirm' => __('Are you sure you want to delete # {0}?', $contractVersion->id),
                    'class' => 'side-nav-item',
                ]
            ) ?>
            <?= $this->AuthLink->link(
                __('List Contract Versions'),
                ['action' => 'index'],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->link(
                __('New Contract Version'),
                ['action' => 'add'],
                ['class' => 'side-nav-item']
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="contractVersions view content">
            <?= __('Contract No.') ?><h3><?= h($contractVersion->contract->number) ?></h3>
            <?= __('Validity') ?><h3><?= h($contractVersion->valid_from) ?> - <?= $contractVersion->valid_until ?
                h($contractVersion->valid_until) : __('indefinitely') ?></h3>
            <?php if ($contractVersion->has('contract')) : ?>
            <h5><?=
                (
                    $contractVersion->contract->has('service_type') ?
                        $contractVersion->contract->service_type->name :
                        ''
                )
                . (
                    $contractVersion->contract->has('installation_address') ?
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
                            <td><?= $contractVersion->has('contract') ? $this->Html->link(
                                $contractVersion->contract->name,
                                ['controller' => 'Contracts', 'action' => 'view', $contractVersion->contract->id]
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
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <td><?= h($contractVersion->id) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Created') ?></th>
                            <td><?= h($contractVersion->created) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Created By') ?></th>
                            <td><?= $contractVersion->has('creator') ? $this->Html->link(
                                $contractVersion->creator->username,
                                [
                                    'plugin' => 'CakeDC/Users',
                                    'controller' => 'Users',
                                    'action' => 'view',
                                    $contractVersion->creator->id,
                                ]
                            ) : h($contractVersion->created_by) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Modified') ?></th>
                            <td><?= h($contractVersion->modified) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Modified By') ?></th>
                            <td><?= $contractVersion->has('modifier') ? $this->Html->link(
                                $contractVersion->modifier->username,
                                [
                                    'plugin' => 'CakeDC/Users',
                                    'controller' => 'Users',
                                    'action' => 'view',
                                    $contractVersion->modifier->id,
                                ]
                            ) : h($contractVersion->modified_by) ?></td>
                        </tr>
                    </table>
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
