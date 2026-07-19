<?php
/**
 * Bulk message wizard — done: summary of the send.
 *
 * @var \App\View\AppView $this
 * @var array{sent: int, channel: string, is_sms: bool, skipped: list<array{id: string, number: string, name: string}>} $result
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('New Bulk Customer Message'),
                ['action' => 'addBulkNew'],
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
            <h3><?= __('Bulk Customer Message') . ' — ' . __('Done') ?></h3>
            <p><?= __('{0} message(s) queued for sending ({1}).', $result['sent'], h($result['channel'])) ?></p>

            <?php if ($result['skipped'] === []) : ?>
                <p><?= __('All selected customers received the message.') ?></p>
            <?php else : ?>
                <hr />
                <h4><?= $result['is_sms']
                    ? __('Skipped — no phone number ({0})', count($result['skipped']))
                    : __('Skipped — no e-mail ({0})', count($result['skipped'])) ?></h4>
                <p>
                    <?= __(
                        'These customers were selected but had no eligible contact for this channel, '
                        . 'so no message was sent to them — handle them individually.',
                    ) ?>
                </p>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Customer Number') ?></th>
                            <th><?= __('Customer') ?></th>
                        </tr>
                        <?php foreach ($result['skipped'] as $customer) : ?>
                            <tr>
                                <td><?= h($customer['number']) ?></td>
                                <td><?= $this->Html->link($customer['name'], [
                                    'controller' => 'Customers',
                                    'action' => 'view',
                                    $customer['id'],
                                ]) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
