<?php
/**
 * @var \App\View\AppView $this
 * @var list<\App\Model\Entity\ContractState> $states
 * @var array<string, int> $counts
 * @var array<string, array<string, mixed>> $urls
 */
?>
<?php if ($states === []) : ?>
    <p><?= __('No contract state is set to show here.') ?></p>
<?php else : ?>
    <table class="dashboard-table">
        <tbody>
            <?php foreach ($states as $state) : ?>
                <tr>
                    <td>
                        <span class="dashboard-swatch" style="background-color: <?= h($state->color) ?>;"></span>
                        <?= $this->Html->link($state->name, $urls[$state->id]) ?>
                        <?php if ($state->note !== null) : ?>
                            <br><small class="dashboard-hint" title="<?= h($state->note) ?>">
                                <?= h($state->note) ?>
                            </small>
                        <?php endif ?>
                    </td>
                    <td><?= h((string)($counts[$state->id] ?? 0)) ?></td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
<?php endif ?>
