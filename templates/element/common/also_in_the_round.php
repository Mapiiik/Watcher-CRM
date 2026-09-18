<?php
/**
 * What else went out in the same envelope and is waiting for the same thing.
 *
 * Said rather than asked: papers that left in one envelope came back in one, so the day is written
 * on all of them together. There is nothing here to tick.
 *
 * @var \App\View\AppView $this
 * @var array<\App\Model\Entity\ContractProposal> $alsoInTheRound
 * @var string $saying What the step is called here.
 */

$alsoInTheRound = $alsoInTheRound ?? [];

if ($alsoInTheRound === []) {
    return;
}
?>
<div class="related">
    <h4><?= __('Also in this customer proposal') ?></h4>
    <p><?= h($saying) ?></p>
    <ul>
        <?php foreach ($alsoInTheRound as $papers) : ?>
        <li><?=
            $this->Html->link(
                $papers->getName(),
                [
                    'controller' => 'ContractProposals',
                    'action' => 'view',
                    $papers->id,
                ],
            )
            ?></li>
        <?php endforeach; ?>
    </ul>
</div>
