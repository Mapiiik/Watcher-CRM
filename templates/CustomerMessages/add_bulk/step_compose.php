<?php
/**
 * Bulk message wizard — step 3: compose the message and preview recipients.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Enum\CustomerMessagePurpose $purpose
 * @var \App\Model\Entity\CustomerMessage $customerMessage
 * @var array<\App\Model\Entity\Customer> $customers
 * @var list<array{ap_id: string|null, ap_name: string, rows: list<array{customer: \App\Model\Entity\Customer, contract: \App\Model\Entity\Contract|null, services: list<string>, vip: bool, criticality: \App\Model\Enum\ServiceCriticalityLevel|null}>}> $apGroups
 * @var array{vip: int, critical: int} $flagged
 * @var list<array{number: string|null, name: string|null, errors: string}>|null $saveFailures
 * @var bool $ignoreCustomerConsent
 * @var bool $ignoreContactUse
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('Back'),
                ['action' => 'addBulk', '?' => ['step' => 'filters']],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->Html->link(
                __('Start Over'),
                ['action' => 'addBulk', '?' => ['reset' => 1]],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->link(
                __('List Customer Messages'),
                ['action' => 'index'],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="customerMessages form content">
            <?= $this->Form->create($customerMessage) ?>
            <?php // opt-out checkboxes submit customer ids into send_to[]; varies per selection
                $this->Form->unlockField('send_to'); ?>
            <fieldset>
                <legend><?= __('Bulk Customer Message') . ' — ' . __(
                    'Step {0} of {1}: {2}',
                    3,
                    3,
                    __('Compose'),
                ) ?></legend>
                <p>
                    <?= __('Purpose: {0}', $purpose->label()) ?><br><br>
                    <?php if ($ignoreCustomerConsent) : ?>
                        <strong><?= __('Customer mailing consent is being ignored for this send.') ?></strong><br>
                    <?php endif; ?>
                    <?php if ($ignoreContactUse) : ?>
                        <strong><?= __('Per-contact routing flag is being ignored for this send.') ?></strong><br>
                    <?php endif; ?>
                    <?php if (!$ignoreCustomerConsent && !$ignoreContactUse) : ?>
                        <?= __('Only customers and contacts that consented are included.') ?>
                    <?php endif; ?>
                </p>
            </fieldset>
            <br>
            <?php if ($saveFailures !== null) : ?>
                <fieldset>
                    <legend style="color: darkred;"><?= __('Nothing was sent') ?></legend>
                    <div class="text">
                        <p>
                            <?= __(
                                'The messages are saved together, so none of them was sent. '
                                . 'Fix the problem below and submit again.',
                            ) ?>
                        </p>
                        <br>
                        <?php if ($saveFailures === []) : ?>
                            <p><?= __('No further details are available.') ?></p>
                        <?php else : ?>
                            <div class="table-responsive">
                                <table>
                                    <tr>
                                        <th><?= __('Customer Number') ?></th>
                                        <th><?= __('Customer') ?></th>
                                        <th><?= __('Errors') ?></th>
                                    </tr>
                                    <?php foreach ($saveFailures as $failure) : ?>
                                        <tr>
                                            <td><?= h($failure['number']) ?></td>
                                            <td><?= h($failure['name']) ?></td>
                                            <td><?= $failure['errors'] ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </fieldset>
                <br>
            <?php endif; ?>
            <fieldset>
                <legend><?= __('Customer Message') ?></legend>
                <?php
                    echo $this->Form->control('type');
                    echo $this->Form->control('subject');
                    echo $this->Form->control('body');
                    echo $this->Form->control('body_format');
                ?>
            </fieldset>
            <br>
            <fieldset>
                <legend><?= __('Recipients by access points — {0} customer(s)', count($customers)) ?></legend>
                <div class="text">
                    <p><?= __(
                        'Uncheck a row to skip that contract. A customer with contracts on several access '
                        . 'points is listed under each; the message is sent once if at least one row stays checked.',
                    ) ?></p>

                    <br>

                    <?php if ($apGroups === []) : ?>
                        <p>
                            <?= __(
                                'No customers match the selected filters (or none consented). '
                                . 'Go back and adjust the filters.',
                            ) ?>
                        </p>
                        <br>
                    <?php endif; ?>

                    <?php if ($flagged['vip'] > 0 || $flagged['critical'] > 0) : ?>
                        <p style="color: darkred;">
                            <strong><?= __('Check the wording before sending.') ?></strong><br>
                            <?php if ($flagged['vip'] > 0) : ?>
                                <?= __(
                                    '{0} of the recipients have a guaranteed / VIP contract.',
                                    $flagged['vip'],
                                ) ?><br>
                            <?php endif; ?>
                            <?php if ($flagged['critical'] > 0) : ?>
                                <?= __(
                                    '{0} of the recipients are billed a service above the normal criticality level.',
                                    $flagged['critical'],
                                ) ?>
                            <?php endif; ?>
                        </p>
                        <br>
                    <?php endif; ?>
                </div>

                <?php foreach ($apGroups as $group) : ?>
                    <div class="related">
                        <h5><?= h($group['ap_name']) ?> (<?= count($group['rows']) ?>)</h5>
                        <div class="table-responsive">
                            <table>
                                <tr>
                                    <th><?= __('Send') ?></th>
                                    <th><?= __('Customer') ?></th>
                                    <th><?= __('Customer Number') ?></th>
                                    <th><?= __('Contract Number') ?></th>
                                    <th><?= __('Services') ?></th>
                                    <th><?= __('Flags') ?></th>
                                    <th><?= __('Emails') ?></th>
                                    <th><?= __('Phones') ?></th>
                                </tr>
                                <?php foreach ($group['rows'] as $row) : ?>
                                    <?php
                                        $customer = $row['customer'];
                                        $contract = $row['contract'];
                                        $noContact = $customer->emails === [] && $customer->phones === [];
                                    ?>
                                    <tr<?= $noContact ? ' style="color: darkred;"' : '' ?>>
                                        <td><input type="checkbox" name="send_to[]"
                                            value="<?= h($customer->id) ?>" checked></td>
                                        <td><?= $this->Html->link($customer->name, [
                                            'controller' => 'Customers',
                                            'action' => 'view',
                                            $customer->id,
                                        ]) ?></td>
                                        <td><?= $noContact
                                            ? '<strong>' . h($customer->number) . '</strong>'
                                            : h($customer->number) ?></td>
                                        <td><?= $contract === null
                                            ? '&mdash;'
                                            : $this->Html->link($contract->number ?? __('(no number)'), [
                                                'controller' => 'Contracts',
                                                'action' => 'view',
                                                $contract->id,
                                            ]) ?></td>
                                        <td><?= h(implode(', ', $row['services'])) ?></td>
                                        <td>
                                            <?php if ($row['vip']) : ?>
                                                <strong style="color: darkred;"><?= __('VIP') ?></strong>
                                            <?php endif; ?>
                                            <?php if ($row['criticality'] !== null) : ?>
                                                <strong style="color: darkred;">
                                                    <?= h($row['criticality']->label()) ?>
                                                </strong>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= $customer->emails === []
                                            ? '<strong>&mdash; ' . __('Email Missing') . ' &mdash;</strong>'
                                            : implode('<br>', array_column($customer->emails, 'email')) ?></td>
                                        <td><?= $customer->phones === []
                                            ? '<strong>&mdash; ' . __('Phone Missing') . ' &mdash;</strong>'
                                            : implode('<br>', array_column($customer->phones, 'phone')) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>
            </fieldset>
            <?php // no count here: it would promise the full list even after rows were unchecked ?>
            <?= $this->Form->button(__('Send to the customers checked above')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
