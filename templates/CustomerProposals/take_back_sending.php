<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\CustomerProposal $customerProposal
 * @var array<array<string, mixed>> $papers
 * @var string|null $stopping
 */

$anythingDrawn = false;
foreach ($papers as $record) {
    $anythingDrawn = $anythingDrawn || $record['documents'] !== [];
}

$whatItDoes = __(
    'It was recorded as handed over in person on {0}. Taking the sending back removes that record and'
    . ' the proposal can be changed again. Do this only if the customer does not have the papers yet.',
    $customerProposal->sent_date,
);
$whatGoes = __(
    'Ticked documents are deleted, and a copy already printed will no longer match once the proposal'
    . ' changes. Received documents always stay on file.',
);
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('View Proposal'),
                ['action' => 'view', $customerProposal->id],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="customerProposals form content">
            <?= $this->element('CustomerProposals/heading', ['doing' => __('Take Back the Sending')]) ?>

            <?php if ($stopping !== null) : ?>
                <div class="message warning" role="status"><?= h($stopping) ?></div>
            <?php else : ?>
                <?= $this->Form->create(null) ?>
                <fieldset>
                    <p><?= h($whatItDoes) ?></p>
                    <br>
                    <?php if ($papers !== []) : ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th><?= __('Proposal') ?></th>
                                    <th><?= __('Generated Documents') ?></th>
                                    <th><?= __('Delete') ?></th>
                                    <th><?= __('Received Documents') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($papers as $record) : ?>
                                <tr>
                                    <td><?= h($record['said']) ?></td>
                                    <td>
                                        <?= count($record['documents']) ?>
                                        <?php foreach ($record['documents'] as $document) : ?>
                                            <br><?= h($document) ?>
                                        <?php endforeach; ?>
                                    </td>
                                    <td>
                                        <?php if ($record['documents'] !== []) : ?>
                                            <?= $this->Form->control('discard.' . $record['id'], [
                                                'type' => 'checkbox',
                                                'label' => false,
                                                'checked' => true,
                                            ]) ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $record['received'] ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                    <?php if ($anythingDrawn) : ?>
                        <p><?= h($whatGoes) ?></p>
                    <?php endif; ?>
                </fieldset>
                <?= $this->Form->button(__('Take Back the Sending')) ?>
                <?= $this->Form->end() ?>
            <?php endif; ?>
        </div>
    </div>
</div>
