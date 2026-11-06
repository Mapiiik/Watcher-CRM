<?php
/**
 * @var \App\View\AppView $this
 * @var \Radius\Updater\ChangeLog\ChangeLog|null $changelog
 */
?>
<?php if (isset($changelog) && $changelog->getFailedChanges() !== []) : ?>
<p><?= __d('radius', 'The changes could not be applied to these accounts.') ?></p>
<div class="table-responsive">
    <table>
    <thead>
        <tr>
            <th><?= __d('radius', 'Customer') ?></th>
            <th><?= __d('radius', 'Contract') ?></th>
            <th><?= __d('radius', 'RADIUS Username') ?></th>
            <th><?= __d('radius', 'Reason') ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($changelog->getFailedChanges() as $change) : ?>
        <tr>
            <?= $this->element('Radius.Accounts/UpdateRelatedRecordsSummaryCells', ['change' => $change]) ?>
            <td><?= implode('<br>', array_map(h(...), $change->getFailures())) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
    </table>
</div>
<br>
<?php endif; ?>
<?php if (isset($changelog)) : ?>
<div class="table-responsive">
    <table>
    <thead>
        <tr>
            <th><?= __d('radius', 'Customer') ?></th>
            <th><?= __d('radius', 'Contract') ?></th>
            <th><?= __d('radius', 'RADIUS Username') ?></th>
            <th><?= __d('radius', 'RADIUS Checks') . ' - ' . __d('radius', 'Original') ?></th>
            <th><?= __d('radius', 'RADIUS Checks') . ' - ' . __d('radius', 'Changed') ?></th>
            <th><?= __d('radius', 'RADIUS Replies') . ' - ' . __d('radius', 'Original') ?></th>
            <th><?= __d('radius', 'RADIUS Replies') . ' - ' . __d('radius', 'Changed') ?></th>
            <th><?= __d('radius', 'RADIUS User Groups') . ' - ' . __d('radius', 'Original') ?></th>
            <th><?= __d('radius', 'RADIUS User Groups') . ' - ' . __d('radius', 'Changed') ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($changelog->getChanges() as $change) : ?>
        <tr>
            <?= $this->element('Radius.Accounts/UpdateRelatedRecordsSummaryCells', ['change' => $change]) ?>
            <td><pre><?= $change->getRadcheckChange() ?
                h(json_encode($change->getRadcheckChange()->originalToRead(), JSON_PRETTY_PRINT)) : '' ?></pre></td>
            <td><pre><?= $change->getRadcheckChange() ?
                h(json_encode($change->getRadcheckChange()->changedToRead(), JSON_PRETTY_PRINT)) : '' ?></pre></td>
            <td><pre><?= $change->getRadreplyChange() ?
                h(json_encode($change->getRadreplyChange()->getOriginal(), JSON_PRETTY_PRINT)) : '' ?></pre></td>
            <td><pre><?= $change->getRadreplyChange() ?
                h(json_encode($change->getRadreplyChange()->getChanged(), JSON_PRETTY_PRINT)) : '' ?></pre></td>
            <td><pre><?= $change->getRadusergroupChange() ?
                h(json_encode($change->getRadusergroupChange()->getOriginal(), JSON_PRETTY_PRINT)) : '' ?></pre></td>
            <td><pre><?= $change->getRadusergroupChange() ?
                h(json_encode($change->getRadusergroupChange()->getChanged(), JSON_PRETTY_PRINT)) : '' ?></pre></td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
<?php endif; ?>
