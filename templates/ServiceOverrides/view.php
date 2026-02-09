<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ServiceOverride $serviceOverride
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('Edit Service Override'),
                ['action' => 'edit', $serviceOverride->id],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink
                ->postLink(
                    __('Revoke Service Override'),
                    ['action' => 'revoke', $serviceOverride->id],
                    [
                        'confirm' => __(
                            'Are you sure you want to revoke service override {0}?',
                            $serviceOverride->id,
                        ),
                        'class' => 'side-nav-item',
                    ],
                ) ?>
            <?= $this->AuthLink->postLink(
                __('Delete Service Override'),
                ['action' => 'delete', $serviceOverride->id],
                [
                    'confirm' => __('Are you sure you want to delete # {0}?', $serviceOverride->id),
                    'class' => 'side-nav-item',
                ],
            ) ?>
            <?= $this->AuthLink->link(
                __('List Service Overrides'),
                ['action' => 'index'],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->link(
                __('New Service Override'),
                ['action' => 'add'],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="serviceOverrides view content">
            <?= $this->AuthLink->postLink(
                __('Revoke Service Override'),
                ['action' => 'revoke', $serviceOverride->id],
                [
                    'confirm' => __(
                        'Are you sure you want to revoke service override {0}?',
                        $serviceOverride->id,
                    ),
                    'class' => 'button float-right',
                ],
            ) ?>
            <?= __('Contract No.') ?>
            <h3><?= h($serviceOverride->contract->number) ?></h3>
            <?= __('Service') ?>
            <h3><?= h($serviceOverride->service->name) ?></h3>
            <?= __('Validity') ?>
            <h3><?= h($serviceOverride->valid_from) ?> - <?= h($serviceOverride->valid_until) ?>
            <?= $serviceOverride->revoked ?
                '(' . __('Revoked on {0}', h($serviceOverride->revoked)) . ')' : '' ?></h3>
            <div class="row">
                <div class="column">
                    <table>
                        <tr>
                            <th><?= __('Contract') ?></th>
                            <td><?= $serviceOverride->hasValue('contract') ? $this->Html->link(
                                $serviceOverride->contract->name,
                                [
                                    'controller' => 'Contracts',
                                    'action' => 'view',
                                    $serviceOverride->contract->id,
                                    'customer_id' => $serviceOverride->contract->customer_id,
                                ],
                            ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Service') ?></th>
                            <td><?= $serviceOverride->hasValue('service') ? $this->Html->link(
                                $serviceOverride->service->name,
                                [
                                    'controller' => 'Services',
                                    'action' => 'view',
                                    $serviceOverride->service->id,
                                ],
                            ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Valid From') ?></th>
                            <td><?= h($serviceOverride->valid_from) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Valid Until') ?></th>
                            <td><?= h($serviceOverride->valid_until) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Revoked') ?></th>
                            <td><?= h($serviceOverride->revoked) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Active') ?></th>
                            <td><?= $serviceOverride->isActive() ? __('Yes') : __('No'); ?></td>
                        </tr>
                    </table>
                </div>
                <div class="column">
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <td><?= h($serviceOverride->id) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Created') ?></th>
                            <td><?= h($serviceOverride->created) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Created By') ?></th>
                            <td><?= $serviceOverride->__isset('creator') ? $this->Html->link(
                                $serviceOverride->creator->username,
                                [
                                    'controller' => 'AppUsers',
                                    'action' => 'view',
                                    $serviceOverride->creator->id,
                                ],
                            ) : h($serviceOverride->created_by) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Modified') ?></th>
                            <td><?= h($serviceOverride->modified) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Modified By') ?></th>
                            <td><?= $serviceOverride->__isset('modifier') ? $this->Html->link(
                                $serviceOverride->modifier->username,
                                [
                                    'controller' => 'AppUsers',
                                    'action' => 'view',
                                    $serviceOverride->modifier->id,
                                ],
                            ) : h($serviceOverride->modified_by) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Revoker') ?></th>
                            <td><?= $serviceOverride->hasValue('revoker') ? $this->Html->link(
                                $serviceOverride->revoker->username,
                                [
                                    'controller' => 'AppUsers',
                                    'action' => 'view',
                                    $serviceOverride->revoker->id,
                                ],
                            ) : '' ?></td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="text">
                <strong><?= __('Reason') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($serviceOverride->reason)); ?>
                </blockquote>
            </div>
        </div>
    </div>
</div>