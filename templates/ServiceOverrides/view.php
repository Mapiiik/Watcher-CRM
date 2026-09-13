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
            <?php
            // An override is known by the service it overrides. Which contract that is comes from
            // the bar across the top, so the line under the name is how long it applies - and
            // whether it was called off, which is the first thing to know about one.
            $about = $serviceOverride->valid_from . ' - ' . $serviceOverride->valid_until
                . ($serviceOverride->revoked
                    ? ' (' . __('Revoked on {0}', $serviceOverride->revoked) . ')'
                    : '');
            ?>
            <?= $this->record(
                __('Service Override'),
                (string)$serviceOverride->service->name,
                $about,
            ) ?>
            <div class="row">
                <div class="column">
                    <table>
                        <tr>
                            <th><?= __('Contract') ?></th>
                            <td><?= $serviceOverride->hasValue('contract') ? $this->Html->link(
                                $serviceOverride->contract->name ?? '(' . $serviceOverride->contract->id . ')',
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
                                $serviceOverride->service->name ?? '(' . $serviceOverride->service->id . ')',
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
                    <?= $this->element('common/audit', ['entity' => $serviceOverride]) ?>
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