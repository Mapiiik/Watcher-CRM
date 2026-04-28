<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\ContractVersion> $contract_versions
 * @var bool $historical_checkbox
 */
?>
<?php if (!empty($contract_versions)) : ?>
<div class="table-responsive">
    <table>
    <thead>
        <tr>
            <th><?= __('Valid From') ?></th>
            <th><?= __('Valid Until') ?></th>
            <th><?= __('Obligation Until') ?></th>
            <th><?= __('Obligations Settled') ?></th>
            <th><?= __('Conclusion Date') ?></th>
            <th><?= __('Number Of Amendments') ?></th>
            <th><?= __('Note') ?></th>
            <th class="actions"><?= __('Actions') ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($contract_versions as $contractVersion) : ?>
        <tr style="<?= $contractVersion->style ?>">
            <td><?= h($contractVersion->valid_from) ?></td>
            <td><?= h($contractVersion->valid_until) ?></td>
            <td style="<?=
                isset($contractVersion->obligation_until)
                && $contractVersion->obligation_until->isFuture() ?
                    'color: red;' : ''
            ?>"><?= h($contractVersion->obligation_until) ?></td>
            <td><?= isset($contractVersion->obligation_until) ?
                ($contractVersion->obligations_settled ? __('Yes') : __('No')) : '' ?></td>
            <td><?= h($contractVersion->conclusion_date) ?></td>
            <td><?= $this->Number->format($contractVersion->number_of_amendments) ?></td>
            <td><?= h($contractVersion->note) ?></td>
            <td class="actions">
                <?= $this->AuthLink->link(
                    __('View'),
                    ['controller' => 'ContractVersions', 'action' => 'view', $contractVersion->id],
                ) ?>
                <?= $this->AuthLink->link(
                    __('Edit'),
                    ['controller' => 'ContractVersions', 'action' => 'edit', $contractVersion->id],
                    ['class' => 'win-link'],
                ) ?>
                <?= $this->AuthLink->postLink(
                    __('Delete'),
                    ['controller' => 'ContractVersions', 'action' => 'delete', $contractVersion->id],
                    ['confirm' => __('Are you sure you want to delete # {0}?', $contractVersion->id)],
                ) ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
<?php endif; ?>
<?php if (!empty($historical_checkbox)) : ?>
<div class="float-right">
    <?= $this->Form->create(null, ['type' => 'get', 'valueSources' => ['query']]) ?>
    <?= $this->Form->control('show_historical_records', [
        'label' => __d('bookkeeping', 'Show historical records'),
        'type' => 'checkbox',
        'onchange' => 'this.form.submit();',
    ]) ?>
    <?= $this->Form->end() ?>
</div>
<?php endif; ?>
