<?php
/**
 * @var \App\View\AppView $this
 * @var bool $isFuture
 * @var bool $isRevoked
 * @var \App\Model\Entity\ServiceOverride $serviceOverride
 * @var \Cake\Collection\CollectionInterface<string, string>|array<string> $contracts
 * @var \Cake\Collection\CollectionInterface<string, string>|array<string> $services
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink
                ->postLink(
                    __('Revoke'),
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
                __('Delete'),
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
        </div>
    </aside>
    <div class="column column-90">
        <div class="serviceOverrides form content">
            <?= $this->Form->create($serviceOverride) ?>
            <fieldset>
                <legend><?= __('Edit Service Override') ?></legend>
                <?php
                if (!isset($contract_id)) {
                    echo $this->Form->control('contract_id', [
                        'options' => $contracts,
                        'empty' => true,
                        'onchange' => <<<JS
                            var refresh = document.createElement("input");
                            refresh.type = "hidden";
                            refresh.name = "refresh";
                            refresh.value = "refresh";
                            this.form.appendChild(refresh);
                            this.form.submit();
                            JS,
                        'disabled' => $isRevoked || !$isFuture,
                    ]);
                    $this->Form->unlockField('refresh'); //disable form security check
                }
                echo $this->Form->control('service_id', [
                    'options' => $services,
                    'empty' => true,
                    'disabled' => $isRevoked || !$isFuture,
                ]);
                echo $this->Form->control('valid_from', ['disabled' => $isRevoked || !$isFuture]);
                echo $this->Form->control('valid_until', ['disabled' => $isRevoked]);
                echo $isRevoked ?
                    '<label><strong style="color: red;">'
                        . __('Revoked on {0}', h($serviceOverride->revoked))
                        . '</strong></label><br>'
                    : '';

                echo $this->Form->control('reason');
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
