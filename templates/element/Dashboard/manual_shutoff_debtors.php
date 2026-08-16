<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Contract> $contracts
 * @var int $total
 */

$shown = 0;
?>
<?php if ($total === 0) : ?>
    <p><?= __('Nothing is running that the automatic blocking cannot reach.') ?></p>
<?php else : ?>
    <p><?= __('These keep running until somebody switches them off by hand.') ?></p>
    <table class="dashboard-table">
        <tbody>
            <?php foreach ($contracts as $contract) : ?>
                <?php $shown++ ?>
                <tr>
                    <td>
                        <?= $this->Html->link(
                            $contract->number ?? $contract->id,
                            [
                                'controller' => 'Contracts',
                                'action' => 'view',
                                $contract->id,
                                'customer_id' => $contract->customer_id,
                            ],
                        ) ?>
                        <?php if ($contract->customer !== null) : ?>
                            <br><small><?= h($contract->customer->name) ?></small>
                        <?php endif ?>
                    </td>
                    <td class="dashboard-wrap">
                        <?= $contract->service_type !== null ? h($contract->service_type->name) : '' ?>
                    </td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>

    <?php if ($total > $shown) : ?>
        <p><?= __('and {0} more', $total - $shown) ?></p>
    <?php endif ?>
<?php endif ?>
