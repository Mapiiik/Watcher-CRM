<?php
/**
 * How far in the page is, and the way back out.
 *
 * The nesting is the address, so popping out is dropping the innermost part of it - each step
 * links to itself and lets go of everything under it. The last step is where the page already is,
 * so it is said rather than offered.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Customer $customer
 * @var \App\Model\Entity\Contract|null $contract
 * @var \App\Model\Entity\ContractVersion|null $version
 * @var \App\Model\Entity\ContractProposal|\App\Model\Entity\CustomerProposal|null $round
 */

use App\Model\Entity\ContractProposal;
use App\Model\Entity\CustomerProposal;

$steps = [];

$steps[] = [
    'said' => $customer->getName(),
    'url' => [
        'action' => 'manage',
        'contract_id' => null,
        '?' => [],
    ],
];

if ($contract !== null) {
    $steps[] = [
        'said' => $contract->getName(),
        'url' => [
            'action' => 'manage',
            '?' => [],
        ],
    ];
}

if ($version !== null) {
    $steps[] = [
        'said' => (string)$version->name,
        'url' => [
            'action' => 'manage',
            'contract_version_id' => $version->id,
            '?' => [],
        ],
    ];
}

// Papers of a contract are a part of a proposal, so the way out of them runs through it - the
// proposal is a step of its own even when what is open is one of its parts.
$proposal = $round instanceof CustomerProposal ? $round : $round?->customer_proposal;

if ($proposal !== null) {
    $steps[] = [
        'said' => __('{0} from {1}', [$proposal->whatItIsFor(), $proposal->effective_from]),
        'url' => [
            'action' => 'manage',
            '?' => ['proposal_id' => $proposal->id, 'agenda' => 'CustomerProposals'],
        ],
    ];
}

if ($round instanceof ContractProposal) {
    $steps[] = [
        'said' => $round->getName(),
        'url' => null,
    ];
}

$here = array_key_last($steps);
$path = [];

foreach ($steps as $at => $step) {
    $path[] = $at === $here || $step['url'] === null
        ? h($step['said'])
        : $this->Html->link($step['said'], $step['url']);
}
?>
<h6 class="whereabouts"><?= implode(' &rsaquo; ', $path) ?></h6>
