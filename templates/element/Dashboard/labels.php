<?php
/**
 * @var \App\View\AppView $this
 * @var list<\App\Model\Entity\Label> $labels
 * @var array<string, int> $counts
 * @var array<string, array<string, mixed>> $urls
 * @var bool $configured
 */
?>
<?php if ($labels === []) : ?>
    <p><?= $configured
        ? __('No label has found anything.')
        : __('No label is set to show here.') ?></p>
<?php else : ?>
    <table class="dashboard-table">
        <tbody>
            <?php foreach ($labels as $label) : ?>
                <tr>
                    <td>
                        <?= $this->Html->link(
                            $label->name ?? '(' . $label->id . ')',
                            $urls[$label->id],
                            ['style' => $label->style],
                        ) ?>
                        <?php if ($label->caption !== null) : ?>
                            <br><small><?= h($label->caption) ?></small>
                        <?php endif ?>
                    </td>
                    <td><?= h((string)($counts[$label->id] ?? 0)) ?></td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
<?php endif ?>
