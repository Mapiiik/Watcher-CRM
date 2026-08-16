<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\ContractVersion> $contract_versions
 * @var int $total
 * @var int $within_days
 * @var array<string, mixed> $url
 */

$shown = 0;
?>
<?php if ($total === 0) : ?>
    <p><?= __('No obligation runs out within {0} days.', $within_days) ?></p>
<?php else : ?>
    <table class="dashboard-table">
        <tbody>
            <?php foreach ($contract_versions as $contract_version) : ?>
                <?php $shown++ ?>
                <tr style="<?= $contract_version->style ?>">
                    <td>
                        <?php if ($contract_version->contract !== null) : ?>
                            <?= $this->Html->link(
                                $contract_version->contract->number ?? $contract_version->contract->id,
                                [
                                    'controller' => 'Contracts',
                                    'action' => 'view',
                                    $contract_version->contract->id,
                                    'customer_id' => $contract_version->contract->customer_id,
                                ],
                            ) ?>
                            <?php if ($contract_version->contract->customer !== null) : ?>
                                <br><small><?= h($contract_version->contract->customer->name) ?></small>
                            <?php endif ?>
                        <?php endif ?>
                    </td>
                    <td><?= h($contract_version->obligation_until) ?></td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>

    <?php if ($total > $shown) : ?>
        <p><?= $this->Html->link(__('and {0} more', $total - $shown), $url) ?></p>
    <?php endif ?>
<?php endif ?>
