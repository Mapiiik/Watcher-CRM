<?php
/**
 * @var \App\View\AppView $this
 * @var int $waiting
 * @var int $notifying
 * @var int $blocking
 * @var array<string, mixed> $url
 * @var int $unanswered
 * @var array<string, mixed> $unanswered_url
 * @var int $unsent
 * @var array<string, mixed> $unsent_url
 * @var int $unfiled
 * @var array<string, mixed> $unfiled_url
 * @var int $customer_unanswered
 * @var array<string, mixed> $customer_unanswered_url
 * @var int $customer_unsent
 * @var array<string, mixed> $customer_unsent_url
 * @var int $customer_unfiled
 * @var array<string, mixed> $customer_unfiled_url
 */

// What is worth showing, widest first. A slice at zero is left out rather than drawn as a
// nought: three figures where two of them say nothing is harder to read at a glance than the
// one figure that does. Every caption agrees with the figure above it.
$slices = [
    [
        $waiting,
        __n(
            'in effect and unsigned, still inside every deadline',
            'in effect and unsigned, still inside every deadline',
            $waiting,
        ),
    ],
    [$notifying, __n('past the reminder deadline', 'past the reminder deadline', $notifying)],
    [$blocking, __n('past the disconnection deadline', 'past the disconnection deadline', $blocking)],
];

// The proposals, each with the listing it belongs to. They are counted apart from the three
// above and link somewhere else, so they carry their own link rather than share the one below.
// Each says whose papers it counts, because the contracts and the customers both have them.
$contract_papers = [
    [
        $unsent,
        __n(
            'contract proposal created and never sent',
            'contract proposals created and never sent',
            $unsent,
        ),
        $unsent_url,
        __('Open the contract proposals nobody has sent'),
    ],
    [
        $unanswered,
        __n(
            'contract proposal sent and still not signed',
            'contract proposals sent and still not signed',
            $unanswered,
        ),
        $unanswered_url,
        __('Open the contract proposals waiting for a signature'),
    ],
    [
        $unfiled,
        __n(
            'contract proposal signed, with the signed documents not filed',
            'contract proposals signed, with the signed documents not filed',
            $unfiled,
        ),
        $unfiled_url,
        __('Open the contract proposals nobody filed'),
    ],
];

$customer_papers = [
    [
        $customer_unsent,
        __n(
            'customer proposal created and never sent',
            'customer proposals created and never sent',
            $customer_unsent,
        ),
        $customer_unsent_url,
        __('Open the customer proposals nobody has sent'),
    ],
    [
        $customer_unanswered,
        __n(
            'customer proposal sent and still not signed',
            'customer proposals sent and still not signed',
            $customer_unanswered,
        ),
        $customer_unanswered_url,
        __('Open the customer proposals waiting for a signature'),
    ],
    [
        $customer_unfiled,
        __n(
            'customer proposal signed, with the signed documents not filed',
            'customer proposals signed, with the signed documents not filed',
            $customer_unfiled,
        ),
        $customer_unfiled_url,
        __('Open the customer proposals nobody filed'),
    ],
];

$contracts_total = array_sum([$waiting, $notifying, $blocking, $unsent, $unanswered, $unfiled]);
$customers_total = array_sum([$customer_unsent, $customer_unanswered, $customer_unfiled]);
?>
<?php if ($contracts_total + $customers_total === 0) : ?>
    <p><?= __('Every running service has a signed contract, and nothing is waiting to be sent.') ?></p>
<?php else : ?>
    <?php if ($contracts_total > 0) : ?>
        <h5><?= __('Contracts') ?></h5>
        <?php if ($waiting > 0 || $notifying > 0 || $blocking > 0) : ?>
            <?php foreach ($slices as [$count, $caption]) : ?>
                <?php if ($count > 0) : ?>
                    <p class="dashboard-figure"><?= h((string)$count) ?></p>
                    <p><?= h($caption) ?></p>
                <?php endif ?>
            <?php endforeach ?>
            <p><?= $this->Html->link(__('Open the contract problems'), $url) ?></p>
        <?php endif ?>
        <?php foreach ($contract_papers as [$count, $caption, $where, $saying]) : ?>
            <?php if ($count > 0) : ?>
                <p class="dashboard-figure"><?= h((string)$count) ?></p>
                <p><?= h($caption) ?></p>
                <p><?= $this->Html->link($saying, $where) ?></p>
            <?php endif ?>
        <?php endforeach ?>
    <?php endif ?>
    <?php if ($customers_total > 0) : ?>
        <?php if ($contracts_total > 0) : ?>
            <hr>
        <?php endif ?>
        <h5><?= __('Customers') ?></h5>
        <?php foreach ($customer_papers as [$count, $caption, $where, $saying]) : ?>
            <?php if ($count > 0) : ?>
                <p class="dashboard-figure"><?= h((string)$count) ?></p>
                <p><?= h($caption) ?></p>
                <p><?= $this->Html->link($saying, $where) ?></p>
            <?php endif ?>
        <?php endforeach ?>
    <?php endif ?>
<?php endif ?>
