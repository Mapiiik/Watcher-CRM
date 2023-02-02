<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Contract $contract
 */
?>
<?php if (!empty($contract->contract_versions)) : ?>
<div class="table-responsive">
    <table>
    <thead>
        <tr>
            <th><?= __('Valid From') ?></th>
            <th><?= __('Valid Until') ?></th>
            <th><?= __('Obligation Until') ?></th>
            <th><?= __('Conclusion Date') ?></th>
            <th><?= __('Number Of Amendments') ?></th>
            <th class="actions"><?= __('Actions') ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($contract->contract_versions as $contractVersion) : ?>
        <tr>
            <td><?= h($contractVersion->valid_from) ?></td>
            <td><?= h($contractVersion->valid_until) ?></td>
            <td style="<?=
                isset($contractVersion->obligation_until)
                && $contractVersion->obligation_until->isFuture() ?
                    'color: red;' : ''
            ?>"><?= h($contractVersion->obligation_until) ?></td>
            <td><?= h($contractVersion->conclusion_date) ?></td>
            <td><?= $this->Number->format($contractVersion->number_of_amendments) ?></td>
            <td class="actions">
                <?= $this->Html->link(
                    __('View'),
                    ['controller' => 'ContractVersions', 'action' => 'view', $contractVersion->id]
                ) ?>
                <?= $this->Html->link(
                    __('Edit'),
                    ['controller' => 'ContractVersions', 'action' => 'edit', $contractVersion->id],
                    ['class' => 'win-link']
                ) ?>
                <?= $this->Form->postLink(
                    __('Delete'),
                    ['controller' => 'ContractVersions', 'action' => 'delete', $contractVersion->id],
                    ['confirm' => __('Are you sure you want to delete # {0}?', $contractVersion->id)]
                ) ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
<?php endif; ?>
