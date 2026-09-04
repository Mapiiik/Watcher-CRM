<?php
/**
 * A list of billings, for reading one set against another.
 *
 * @var \App\View\AppView $this
 * @var array<\App\Model\Entity\Billing> $billings
 */
?>
<?php if ($billings === []) : ?>
    <p><?= __('Nothing.') ?></p>
<?php else : ?>
<table>
    <thead>
        <tr>
            <th><?= __('Name') ?></th>
            <th><?= __('From') ?></th>
            <th><?= __('Until') ?></th>
            <th><?= __('Total Price') ?></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($billings as $billing) : ?>
        <tr>
            <td><?= h($billing->name) ?></td>
            <td><?= h($billing->billing_from) ?></td>
            <td><?= h($billing->billing_until) ?></td>
            <td><?= h($billing->total_price) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
