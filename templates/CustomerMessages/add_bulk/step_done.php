<?php
/**
 * Bulk message wizard — done: what the send actually did.
 *
 * The same report is mailed to the operator, so both must say the same thing.
 *
 * @var \App\View\AppView $this
 * @var array{
 *     sent: int,
 *     channel: string,
 *     is_sms: bool,
 *     purpose: string,
 *     subject: string,
 *     body: string,
 *     filters: list<string>,
 *     ignored_customer_consent: bool,
 *     ignored_contact_use: bool,
 *     groups: list<array{ap_id: string|null, ap_name: string, customers: list<array{number: string|null, name: string, contract_number: string|null, services: list<string>, vip: bool, criticality: string|null, recipients: list<string>}>}>,
 *     skipped: list<array{id: string, number: string|null, name: string}>,
 *     dropped: list<array{number: string|null, name: string}>,
 *     flagged: array{vip: int, critical: int},
 *     summary_mailed: bool
 * } $result
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('New Bulk Customer Message'),
                ['action' => 'addBulk'],
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
        <div class="customerMessages content">
            <h4><?= __('Bulk Customer Message') . ' — ' . __('Done') ?></h4>
            <p>
                <?= __('Purpose: {0}', h($result['purpose'])) ?><br>
                <?= __('{0} message(s) queued for sending ({1}).', $result['sent'], h($result['channel'])) ?><br>
                <?php if ($result['summary_mailed']) : ?>
                    <?= __('A summary of this send has been e-mailed to you.') ?>
                <?php else : ?>
                    <?= __('The summary below could not be e-mailed — this page is the only copy.') ?>
                <?php endif; ?>
            </p>
            <br>

            <?php if ($result['flagged']['vip'] > 0 || $result['flagged']['critical'] > 0) : ?>
                <p>
                    <?php if ($result['flagged']['vip'] > 0) : ?>
                        <?= __(
                            '{0} of them have a guaranteed / VIP contract.',
                            $result['flagged']['vip'],
                        ) ?><br>
                    <?php endif; ?>
                    <?php if ($result['flagged']['critical'] > 0) : ?>
                        <?= __(
                            '{0} of them are billed a service above the normal criticality level.',
                            $result['flagged']['critical'],
                        ) ?>
                    <?php endif; ?>
                </p>
                <br>
            <?php endif; ?>

            <h4><?= __('Recipient filters') ?></h4>
            <?php if (
                $result['filters'] === []
                && !$result['ignored_customer_consent']
                && !$result['ignored_contact_use']
) : ?>
                <p><?= __('No filter was applied.') ?></p>
            <?php else : ?>
                <ul>
                    <?php foreach ($result['filters'] as $filter) : ?>
                        <li><?= h($filter) ?></li>
                    <?php endforeach; ?>
                    <?php if ($result['ignored_customer_consent']) : ?>
                        <li><strong><?= __('Customer mailing consent was ignored.') ?></strong></li>
                    <?php endif; ?>
                    <?php if ($result['ignored_contact_use']) : ?>
                        <li><strong><?= __('Per-contact routing flag was ignored.') ?></strong></li>
                    <?php endif; ?>
                </ul>
            <?php endif; ?>
            <br>

            <h4><?= __('Recipients') ?></h4>
            <?php foreach ($result['groups'] as $group) : ?>
                <div class="related">
                    <h5><?= $this->element('AccessPoints/link', [
                        'id' => $group['ap_id'],
                        'name' => $group['ap_name'],
                    ]) ?> (<?= count($group['customers']) ?>)</h5>
                    <div class="table-responsive">
                        <table>
                            <tr>
                                <th><?= __('Customer Number') ?></th>
                                <th><?= __('Customer') ?></th>
                                <th><?= __('Contract Number') ?></th>
                                <th><?= __('Services') ?></th>
                                <th><?= __('Flags') ?></th>
                                <th><?= $result['is_sms'] ? __('Phones') : __('Emails') ?></th>
                            </tr>
                            <?php foreach ($group['customers'] as $customer) : ?>
                                <tr>
                                    <td><?= h($customer['number']) ?></td>
                                    <td><?= h($customer['name']) ?></td>
                                    <td><?= h($customer['contract_number']) ?></td>
                                    <td><?= h(implode(', ', $customer['services'])) ?></td>
                                    <td>
                                        <?php if ($customer['vip']) : ?>
                                            <strong style="color: darkred;"><?= __('VIP') ?></strong>
                                        <?php endif; ?>
                                        <?php if ($customer['criticality'] !== null) : ?>
                                            <strong style="color: darkred;">
                                                <?= h($customer['criticality']) ?>
                                            </strong>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= implode('<br>', array_map('h', $customer['recipients'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
            <br>

            <?php
                $notSent = [
                    ($result['is_sms']
                        ? __('Not sent — no phone number ({0})', count($result['skipped']))
                        : __('Not sent — no e-mail ({0})', count($result['skipped'])))
                        => $result['skipped'],
                    __('Not sent — no longer eligible when sending ({0})', count($result['dropped']))
                        => $result['dropped'],
                ];
                ?>
            <?php foreach ($notSent as $heading => $customers) : ?>
                <?php if ($customers !== []) : ?>
                    <h4><?= h($heading) ?></h4>
                    <div class="table-responsive">
                        <table>
                            <tr>
                                <th><?= __('Customer Number') ?></th>
                                <th><?= __('Customer') ?></th>
                            </tr>
                            <?php foreach ($customers as $customer) : ?>
                                <tr>
                                    <td><?= h($customer['number']) ?></td>
                                    <td><?= isset($customer['id'])
                                        ? $this->Html->link($customer['name'], [
                                            'controller' => 'Customers',
                                            'action' => 'view',
                                            $customer['id'],
                                        ])
                                        : h($customer['name']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                    <br>
                <?php endif; ?>
            <?php endforeach; ?>

            <h4><?= __('Message') ?></h4>
            <p><strong><?= h($result['subject']) ?></strong></p>
            <pre style="white-space: pre-wrap;"><?= h($result['body']) ?></pre>
        </div>
    </div>
</div>
