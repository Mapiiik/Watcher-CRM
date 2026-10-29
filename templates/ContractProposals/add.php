<?php
/**
 * Drawing up the papers for a contract: what they are for, and what they say about the version and
 * the contract. What is billed for is not here - each line of that is edited on a page of its own,
 * from the proposal's own table, the same way a billing on a contract is.
 *
 * What is asked follows the purpose. Asking everything at once was how this began, and it put an
 * agreement to end a contract behind two checkboxes, two dates that had to match, and a question
 * about a fixed term that an ending is not.
 *
 * All of it is asked only while the papers are being drawn up. Every field here settles something
 * that is then photographed or written into the changes, so papers already written are put right
 * on a page of their own, which asks for none of this.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ContractProposal $contractProposal
 * @var \Cake\Collection\CollectionInterface<string, string>|array<string> $contracts
 * @var \Cake\Collection\CollectionInterface<string, string>|array<string> $versions
 * @var bool|null $keepsVersions Whether the contract's service keeps versions at all.
 * @var array<string, string> $proposals
 * @var array<string, string> $proposalPurposes
 * @var array<string> $questions
 * @var array<string, string> $wording
 * @var array<string, string> $contractNumbers
 * @var array<string, string> $purposes
 * @var \App\Model\Enum\ProposalPurpose $purpose
 * @var \Cake\I18n\Date|null $effectiveFromDefault
 * @var \Cake\I18n\Date|null $obligationOffered The day a minimum term usually runs to.
 * @var bool|null $nothingToReplace Whether the contract has no version a new one could replace.
 * @var \App\Model\Entity\ContractVersion|null $unconcludedToReplace The version chosen to be replaced,
 *   where nobody recorded when it was signed.
 */

use App\Model\Enum\ProposalPurpose;

// What the form has already been told, which on a form being drawn again is what it was told
// before it was drawn - so a box that was ticked stays ticked.
$changes = $contractProposal->proposedChanges();

$ending = $purpose === ProposalPurpose::Termination;
// An exchange of borrowed equipments changes neither the contract nor its version.
$exchange = !$purpose->needsAVersion();
$keepsVersions ??= true;
$endsOn = $changes->version->names('valid_until')
    ? $changes->version->get('valid_until')
    : null;
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?php
            // Where these papers live. The address the form was opened under comes along, so it
            // lands on the customer or the contract being worked on.
            ?>
            <?= $this->AuthLink->link(
                __('Documents'),
                ['plugin' => null, 'controller' => 'Documents', 'action' => 'manage'],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="contractProposals form content">
            <?= $this->Form->create($contractProposal) ?>
            <fieldset>
                <?= $this->legend(__('Add Contract Proposal')) ?>
                <?php
                // The purpose, the contract and the version all redraw the form when they change, and the
                // field they add to do it is not one the form declared - so it is unlocked whichever of them
                // is on the page.
                $this->Form->unlockField('refresh');

                // There is one proposal and these papers are a part of it, so it is the first thing asked.
                // Left empty they get one of their own, which is how papers drawn up from the contract rather
                // than from a proposal still end up inside one.
                if ($proposals !== []) {
                    echo $this->Form->control('customer_proposal_id', [
                        'options' => $proposals,
                        'empty' => __('A new customer proposal'),
                        'label' => __('Part of the Customer Proposal'),
                        'onchange' => $this::REFRESH_ON_CHANGE,
                        'help' => __('Contract proposals that are part of one customer proposal are sent'
                            . ' and signed together.'),
                    ]);
                }

                // Where they are getting a proposal of their own, what that one asks of the customer
                // themselves is asked here - most often nothing, since the papers of the contract are why it
                // is being drawn up at all.
                if ($contractProposal->customer_proposal_id === null) {
                    echo $this->Form->control('new_proposal_purpose', [
                        'options' => $proposalPurposes,
                        'empty' => __('No purpose of its own'),
                        'label' => __('Purpose of the New Customer Proposal'),
                    ]);
                }

                echo $this->Form->control('purpose', [
                    'options' => $purposes,
                    'label' => __('Purpose'),
                    'onchange' => $this::REFRESH_ON_CHANGE,
                ]);

                // Which contract the papers are about is asked even where the address already says it. Every
                // way in reaches the same form and the address is only a line above it, so a field that came
                // and went with the route is one the operator would look for and not find.
                echo $this->Form->control('contract_id', [
                    'options' => $contracts,
                    'empty' => true,
                    'onchange' => $this::REFRESH_ON_CHANGE,
                ]);
                // A new contract may be put on paper before the version it is about exists: left empty, the
                // version comes into being when the papers are applied. Everything else is about a
                // version that is already there.
                if ($exchange) {
                    echo '<p>' . __('An exchange of borrowed equipments changes neither the contract nor its'
                        . ' version. The equipment is added and ended on the borrowed equipment lines,'
                        . ' once the proposal is created.') . '</p>';
                } elseif ($keepsVersions) {
                    echo $this->Form->control('contract_version_id', [
                        'options' => $versions,
                        'empty' => true,
                        'label' => __('Contract Version'),
                        'onchange' => $this::REFRESH_ON_CHANGE,
                        'required' => !$purpose->mayStartAVersion(),
                        'help' => $purpose->mayStartAVersion()
                            ? __('If left empty, the version is created when the changes are applied.')
                            : null,
                    ]);
                } else {
                    // Some services are only passed on, and the customer's contract is with the provider.
                    echo '<p>' . __('The service type of this contract does not use contract versions, so'
                        . ' no documents are generated for this proposal.') . '</p>';
                }

                // Without a version there is no day to take, so it is asked for here as well.
                $asksForTheDay = !$exchange && (
                    $purpose->asksForItsOwnDay()
                    || ($purpose->mayStartAVersion() && $contractProposal->contract_version_id === null)
                    || (!$keepsVersions && !$ending)
                );

                // A change is agreed while the version runs, so it says its own day. A new contract starts
                // with its version, and an ending says the day it ends on - both are worked out rather than
                // asked, because asking twice would only invite the two to disagree.
                if ($asksForTheDay) {
                    echo $this->Form->control('effective_from', [
                        'label' => __('Effective From'),
                        // The day a minimum term is offered from, so saying it draws the offer again - once
                        // the writing is finished, because a date field says it changed while a year is still
                        // half typed.
                        'onblur' => $this::REFRESH_ON_LEAVING,
                        // Left empty it follows the version, so it is not filled in ahead of time: a day put
                        // there for the operator would stay behind when they chose another version.
                        'required' => !$keepsVersions,
                        'help' => match (true) {
                            !$keepsVersions => __('The day this contract proposal takes effect.'),
                            $effectiveFromDefault === null => __('The day this contract proposal takes effect,'
                                . ' and the day the version starts on.'),
                            default => __(
                                'The day this contract proposal takes effect. If left empty, the start of'
                                . ' the version is used ({0}).',
                                $effectiveFromDefault,
                            ),
                        },
                    ]);
                } elseif (!$ending && !$exchange) {
                    echo '<p>' . __('This contract proposal takes effect with the contract version'
                        . ' it is for.') . '</p>';
                }

                // Only a new contract may end an earlier version of the same contract, which is the one paper
                // that does both at once. A change leaves the version where it is, by definition.
                if ($purpose === ProposalPurpose::NewContract && $keepsVersions) {
                    echo $this->Form->control('terminates_contract_version_id', [
                        'options' => $versions,
                        'empty' => true,
                        'label' => __('Terminates Contract Version'),
                        // Naming one is what makes the number below worth asking for, so saying it
                        // draws the form again.
                        'onchange' => $this::REFRESH_ON_CHANGE,
                        'help' => !empty($nothingToReplace)
                            ? __('No version of this contract is on record. To replace an earlier'
                                . ' contract, add its version first, with the day it was concluded.')
                            : null,
                    ]);

                    // An old contract often has its version without the day it was signed, and a
                    // version nobody signed cannot be replaced. Put right in a window of its own,
                    // after which this page is read again.
                    if (!empty($unconcludedToReplace)) {
                        echo $this->Html->div('message warning', __(
                            'The version being replaced has no conclusion date. Fill it in from the'
                            . ' signed contract before this proposal is saved. {0}',
                            $this->AuthLink->link(
                                __('Edit the Version'),
                                [
                                    'controller' => 'ContractVersions',
                                    'action' => 'edit',
                                    $unconcludedToReplace->id,
                                ],
                                ['class' => 'win-link'],
                            ),
                        ), ['role' => 'status']);
                    }
                }

                // The number goes on the paper of whatever is being ended, so it is asked only where
                // something is - an ending, or a new contract replacing an earlier version. The same
                // two cases the rule that demands it asks about.
                if ($keepsVersions && ($ending || $contractProposal->terminates_contract_version_id !== null)) {
                    echo $this->Form->control('terminated_contract_number', [
                        'options' => $contractNumbers,
                        'empty' => true,
                        'label' => __('Number of the contract being terminated'),
                    ]);
                }

                // A new contract replacing an earlier one takes the connection over as it is, unless
                // it is set up again - for another technology, say - and that is charged for.
                $replacing = $contractProposal->terminates_contract_version_id !== null;
                if ($purpose === ProposalPurpose::NewContract && $replacing) {
                    echo $this->Form->control('reactivates', [
                        'type' => 'checkbox',
                        'label' => __('The connection is set up again, and the activation fee is charged'),
                        'help' => __('Without it the new contract takes the connection over as it is, and'
                            . ' its documents charge no activation fee.'),
                    ]);
                }
                echo $this->Form->control('note');
                ?>
            </fieldset>

            <?php if ($ending) : ?>
            <fieldset>
                <legend><?= __('When it ends') ?></legend>
                <?php
                echo $this->Form->control('ends_on', [
                    'type' => 'date',
                    'empty' => true,
                    'value' => $endsOn,
                    'label' => __('Last day of the service'),
                    'help' => $keepsVersions
                        ? __('The version and its billing end on this day.')
                        : __('The contract and its billing end on this day.'),
                ]);
                if ($keepsVersions) {
                    echo $this->Form->control('version_only', [
                        'type' => 'checkbox',
                        'checked' => $changes->version->endsTheVersion()
                            && !$changes->contract->endsTheContract(),
                        'label' => __('End this version only, and leave the contract running'),
                        'help' => __('Use this when one version ends and another follows it.'),
                    ]);
                }
                ?>
            </fieldset>
            <?php elseif ($keepsVersions && $purpose->mayChangeTheTerm() && !$exchange) : ?>
            <fieldset>
                <legend><?= __('Contract Version') ?></legend>
                <p><?= __('Only what is ticked here is changed. The rest is left as it stands.') ?></p>
                <br>
                <?php
                foreach (['valid_until', 'obligation_until'] as $field) {
                    $named = $changes->version->names($field);
                    $id = 'version-change-' . str_replace('_', '-', $field);

                    // A minimum term usually runs the same length, so it is offered rather than typed. Two
                    // gestures, neither of them surprising: the box says there is one, the offer fills the day
                    // in. What is offered is counted in PHP, so the reader sees the very day before clicking -
                    // and if the day the papers take effect moves, the form is drawn again and so is this.
                    $offer = $field === 'obligation_until' && $obligationOffered !== null
                        ? $this->element('Contracts/usual_term_offer', [
                            'offered' => $obligationOffered,
                            'box' => 'version-change-named-' . str_replace('_', '-', $field),
                            'day' => $id,
                            'alsoEnabled' => [],
                        ])
                        : '';

                    // The box turns its own day on and off, the way the dates on a contract version do.
                    echo $this->Form->control("version_change_named.{$field}", [
                        'type' => 'checkbox',
                        'checked' => $named,
                        'label' => [
                            'text' => h($field === 'valid_until'
                                ? __('Change the day the version stops being valid')
                                : __('Change the day the obligation runs out')) . $offer,
                            'escape' => false,
                        ],
                        'onclick' => sprintf('document.getElementById("%s").disabled = !this.checked;', $id),
                    ]);

                    // A disabled field sends nothing, so an empty one is sent in its place - and form
                    // protection is told, because it counts what the markup declared.
                    echo $this->Form->hidden("version_change.{$field}", ['value' => '']);
                    echo $this->Form->control("version_change.{$field}", [
                        'id' => $id,
                        'type' => 'date',
                        'empty' => true,
                        'value' => $named ? $changes->version->get($field) : null,
                        'label' => false,
                        'disabled' => !$named,
                    ]);
                    $this->Form->unlockField("version_change.{$field}");
                }
                ?>
            </fieldset>
            <?php endif; ?>

            <?php if ($questions !== []) : ?>
            <fieldset>
                <legend><?= __('Before the contract proposal is created') ?></legend>
                <?php foreach ($questions as $question) : ?>
                    <?= $this->Form->control("confirmations.{$question}", [
                        'type' => 'checkbox',
                        'checked' => $contractProposal->confirmations()->confirms($question),
                        'label' => $wording[$question] ?? $question,
                    ]) ?>
                <?php endforeach; ?>
            </fieldset>
            <?php endif; ?>

            <?php
            // An end date on a version is also how an ending and a superseded version are recorded, so a paper
            // meant to run for a fixed term is said out loud rather than assumed. An ending never asks.
            if (!$ending && !$exchange && $keepsVersions) {
                echo $this->Form->control('confirmations.fixed_term', [
                    'type' => 'checkbox',
                    'checked' => $contractProposal->confirmations()->confirms('fixed_term'),
                    'label' => __('This is a fixed-term contract, and the obligation runs to the end of it.'),
                ]);
            }
            ?>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
