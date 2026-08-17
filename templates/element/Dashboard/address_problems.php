<?php
/**
 * @var \App\View\AppView $this
 * @var list<array<string, mixed>> $rows
 * @var array<string, mixed> $overview_url
 */
?>
<?php if ($rows === []) : ?>
    <p><?= __('Nothing is wrong with the addresses on record.') ?></p>
<?php else : ?>
    <table class="dashboard-table">
        <tbody>
            <?php foreach ($rows as $row) : ?>
                <tr>
                    <td><?= $this->AuthLink->link($row['title'], $row['url']) ?></td>
                    <td><?= h((string)$row['total']) ?></td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>

    <p><?= $this->AuthLink->link(__('Show all address checks'), $overview_url) ?></p>
<?php endif ?>
