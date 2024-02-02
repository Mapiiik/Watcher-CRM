<?php
/**
 * @var \App\View\AppView $this
 * @var \Radius\Updater\ChangeLog\ChangeLog|null $changelog
 */
?>
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
            <td><?= $this->Html
                ->link(
                    $change->getCustomer()->name,
                    [
                        '_full' => true,
                        'plugin' => null,
                        'controller' => 'Customers',
                        'action' => 'view',
                        $change->getCustomer()->id,
                    ]
                ) ?></td>
            <td><?= $this->Html
                ->link(
                    $change->getContract()->name,
                    [
                        '_full' => true,
                        'plugin' => null,
                        'controller' => 'Contracts',
                        'action' => 'view',
                        'customer_id' => $change->getContract()->customer_id,
                        $change->getContract()->id,
                    ]
                ) ?></td>
            <td><?= $this->Html
                ->link(
                    $change->getAccount()->username,
                    [
                        '_full' => true,
                        'plugin' => 'Radius',
                        'controller' => 'Accounts',
                        'action' => 'view',
                        'customer_id' => $change->getAccount()->customer_id,
                        'contract_id' => $change->getAccount()->contract_id,
                        $change->getAccount()->id,
                    ]
                ) ?></td>
            <td><pre><?= $change->getRadcheckChange() ?
                h(json_encode($change->getRadcheckChange()->getOriginal(), JSON_PRETTY_PRINT)) : '' ?></pre></td>
            <td><pre><?= $change->getRadcheckChange() ?
                h(json_encode($change->getRadcheckChange()->getChanged(), JSON_PRETTY_PRINT)) : '' ?></pre></td>
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
