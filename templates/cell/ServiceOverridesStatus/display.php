<?php
/**
 * Service Overrides Status Cell template
 *
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\ResultSetInterface<\App\Model\Entity\ServiceOverride>|\Cake\Collection\CollectionInterface<array-key, \App\Model\Entity\ServiceOverride> $activeServiceOverrides
 * @var \Cake\Datasource\ResultSetInterface<\App\Model\Entity\ServiceOverride>|\Cake\Collection\CollectionInterface<array-key, \App\Model\Entity\ServiceOverride> $futureServiceOverrides
 * @var bool $showContractNumber
 */
?>

<?php if (!$activeServiceOverrides->isEmpty() || !$futureServiceOverrides->isEmpty()) : ?>
    <div class="service-overrides-status">
        <div class="row">
            <div class="column">
                <?php if (!$activeServiceOverrides->isEmpty()) : ?>
                <h6><?= __('Active Service Overrides') ?></h6>
                <ul>
                    <?php foreach ($activeServiceOverrides as $override) : ?>
                        <li>
                            <?php if ($showContractNumber) : ?>
                                <?= __('Contract') ?> <?= h($override->contract->number) ?> –
                            <?php endif; ?>

                            <?= h($override->service->name) ?>
                            (<?=
                                __(
                                    'active from {0} to {1}',
                                    $override->valid_from->i18nFormat(),
                                    $override->valid_until->i18nFormat(),
                                )
                                ?>)
                        </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
            <div class="column">
                <?php if (!$futureServiceOverrides->isEmpty()) : ?>
                <h6><?= __('Future Service Overrides') ?></h6>
                <ul>
                    <?php foreach ($futureServiceOverrides as $override) : ?>
                        <li>
                            <?php if ($showContractNumber) : ?>
                                <?= __('Contract') ?> <?= h($override->contract->number) ?> –
                            <?php endif; ?>

                            <?= h($override->service->name) ?>
                            (<?=
                                __(
                                    'scheduled from {0} to {1}',
                                    $override->valid_from->i18nFormat(),
                                    $override->valid_until->i18nFormat(),
                                )
                                ?>)
                        </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>
