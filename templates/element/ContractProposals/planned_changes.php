<?php
/**
 * What a transfer would write onto the records, a row to a field.
 *
 * Read in two places, and only one of them may say anything about the records themselves. The
 * preview is read in the moment before the button is pressed, so what the records say today is
 * what is about to be written over, and the fields nobody asked for are worked out against it.
 * The proposal's own page is read whenever - months later, or after it has been carried over - so
 * all it can stand behind is what it asks for. What the records happened to say when the page was
 * opened would be a fact about today rather than about the proposal.
 *
 * @var \App\View\AppView $this
 * @var list<\App\Contracts\Proposal\PlannedChange> $planned
 * @var bool $preview Whether this is the moment before it happens rather than the record of it.
 */

use App\Contracts\Proposal\TransferPlan;

?>
<div class="table-responsive">
    <table>
        <thead>
            <tr>
                <th><?= __('Agenda') ?></th>
                <th><?= __('Record') ?></th>
                <th><?= __('Field') ?></th>
                <?php if ($preview) : ?>
                <th><?= __('As it is now') ?></th>
                <?php endif; ?>
                <th><?= __('As it would be') ?></th>
                <?php if ($preview) : ?>
                <th><?= __('Asked For') ?></th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($planned as $write) : ?>
            <?php
            // A record the papers are about to bring into being has nowhere to be looked at, so
            // it is named and left at that.
            $recordCell = h($write->record);

            if ($write->id !== null) {
                $recordCell = $this->Html->link(
                    $write->record,
                    [
                        'controller' => $write->target === TransferPlan::CONTRACT
                            ? 'Contracts'
                            : 'ContractVersions',
                        'action' => 'view',
                        $write->id,
                    ],
                );
            }

            if ($write->target === TransferPlan::REPLACED_VERSION) {
                $recordCell .= ' (' . __('being replaced') . ')';
            }
            ?>
            <tr>
                <td><?= h($write->agenda()) ?></td>
                <td><?= $recordCell ?></td>
                <td><?= h($write->label) ?></td>
                <?php if ($preview) : ?>
                <td><?= h($write->from) ?></td>
                <?php endif; ?>
                <td><?= $write->to === null ? __('cleared') : h($write->to) ?></td>
                <?php if ($preview) : ?>
                <td><?= $write->asked ? __('Yes') : __('No') ?></td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
