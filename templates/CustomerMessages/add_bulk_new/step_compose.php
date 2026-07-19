<?php
/**
 * Bulk message wizard — step 3: compose the message and preview recipients.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Enum\CustomerMessagePurpose $purpose
 * @var \App\Model\Entity\CustomerMessage $customerMessage
 * @var array<\App\Model\Entity\Customer> $customers
 * @var list<array{ap_id: string|null, ap_name: string, customers: list<\App\Model\Entity\Customer>}> $apGroups
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
                ['action' => 'addBulkNew', '?' => ['step' => 'filters']],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->Html->link(
                __('Start Over'),
                ['action' => 'addBulkNew', '?' => ['reset' => 1]],
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
                <legend><?= __('Bulk Customer Message') . ' — ' . __('Step {0} of {1}: {2}', 3, 3, __('Compose')) ?></legend>
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
                <hr>
                <?php
                    echo $this->Form->control('type');
                    echo $this->Form->control('subject');
                    echo $this->Form->control('body');
                    echo $this->Form->control('body_format');
                ?>
            </fieldset>

            <hr />
            <h4><?= __('Recipients by access points — {0} customer(s)', count($customers)) ?></h4>
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
            </div>

            <?php foreach ($apGroups as $group) : ?>
                <div class="related">
                    <h5><?= h($group['ap_name']) ?> (<?= count($group['customers']) ?>)</h5>
                    <div class="table-responsive">
                        <table>
                            <tr>
                                <th><?= __('Send') ?></th>
                                <th><?= __('Customer') ?></th>
                                <th><?= __('Customer Number') ?></th>
                                <th><?= __('Emails') ?></th>
                                <th><?= __('Phones') ?></th>
                            </tr>
                            <?php foreach ($group['customers'] as $customer) : ?>
                                <?php $noContact = $customer->emails === [] && $customer->phones === []; ?>
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

            <?= $this->Form->button(__('Send to {0} customer(s)', count($customers))) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
