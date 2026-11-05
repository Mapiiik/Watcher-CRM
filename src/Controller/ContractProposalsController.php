<?php
declare(strict_types=1);

namespace App\Controller;

use App\Contracts\MinimumConnectionPrice;
use App\Contracts\Proposal\ProposalChanges;
use App\Contracts\Proposal\ProposalDrafting;
use App\Contracts\Proposal\ProposalForm;
use App\Contracts\Proposal\ProposalReading;
use App\Contracts\Proposal\ProposalTerm;
use App\Contracts\Proposal\ProposedBilling;
use App\Contracts\Proposal\ProposedBillingForm;
use App\Contracts\Proposal\ProposedEquipment;
use App\Contracts\Proposal\ReadinessChecks;
use App\Contracts\Proposal\SnapshotShape;
use App\Contracts\TheUsualTerm;
use App\Controller\Traits\CommonViewVarListsTrait;
use App\Model\Audit\AuditTrail;
use App\Model\Entity\Billing;
use App\Model\Entity\Contract;
use App\Model\Entity\ContractProposal;
use App\Model\Entity\CustomerProposal;
use App\Model\Entity\EquipmentType;
use App\Model\Enum\CustomerProposalPurpose;
use App\Model\Enum\EquipmentMove;
use App\Model\Enum\ProposalPurpose;
use App\Service\ContractPrint\ContractDocuments;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Response;
use Cake\I18n\Date;
use Cake\I18n\DateTime;
use Cake\ORM\Query\SelectQuery;
use Cake\Utility\Text;
use Cake\Validation\Validation;
use Files\Service\FileStorage;
use Throwable;

/**
 * ContractProposals Controller
 *
 * @property \App\Model\Table\ContractProposalsTable $ContractProposals
 */
class ContractProposalsController extends AppController
{
    use CommonViewVarListsTrait;

    /**
     * Every page here is about one proposal, and the bar over it is what says whose papers
     * these are - so a page asked for without the nesting is sent to where it belongs.
     *
     * @var list<string>
     */
    protected array $nestingAutoFix = [
        'view',
        'recreate',
        'billingLine',
        'equipmentLine',
    ];

    /**
     * @var list<string>
     */
    protected array $nestingAutoAdd = [
        'view',
        'recreate',
        'billingLine',
        'equipmentLine',
    ];

    /**
     * The fields a proposal may have put right without being drawn up again.
     *
     * Nothing is worked out from any of them: they are written where they are read. Everything
     * else the papers say is either photographed or turned into the changes the moment it is
     * first said, and reading it a second time is what would set the two at odds.
     *
     * @var list<string>
     */
    private const MAY_BE_PUT_RIGHT = [
        'note',
        'terminated_contract_number',
        'customer_proposal_id',
    ];

    /**
     * View method
     *
     * @param string|null $id Contract version proposal id.
     * @return void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null): void
    {
        $contractProposal = $this->ContractProposals->get($id, contain: [
            'Contracts' => ['Customers', 'InstallationAddresses', 'ServiceTypes'],
            'ContractVersions',
            'TerminatedContractVersions',
            // The papers say which proposal they go out in, and it names itself by what it holds.
            'CustomerProposals' => ['ContractProposals'],
            'Creators',
            'Modifiers',
            'LeftOutSettlers',
        ]);

        $this->set(compact('contractProposal'));
        $this->setProposalViewVars($contractProposal);
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add(): ?Response
    {
        $proposal = $this->ContractProposals->newEmptyEntity();

        // Which contract the papers are for comes from the nested route the form was opened
        // under; a link from a version's own page settles the version as well.
        foreach (['contract_id' => $this->contract_id, 'contract_version_id' => null] as $what => $known) {
            $named = $known ?? $this->named($what);

            if ($named !== null) {
                $proposal->set($what, $named);
            }
        }

        $named = $this->named('purpose');
        $proposal->set('purpose', ProposalPurpose::tryFrom((string)$named) ?? ProposalPurpose::NewContract);

        // Drawn up from inside a proposal, the papers go out in it and speak about the day it does.
        // Both are only what the form opens with - the operator may say otherwise.
        $customer_proposal = $this->proposalAskedFor();

        if ($customer_proposal !== null) {
            $proposal->set('customer_proposal_id', $customer_proposal->id);
            $proposal->set('effective_from', $customer_proposal->effective_from);
        }

        if ($this->request->is('post')) {
            $proposal = (new ProposalDrafting())->fill($proposal, $this->request->getData(), $this->isARedraw());

            // The lines are put there against the snapshot, so only a submission that got as far
            // as taking one has anything to end. A form asking to be drawn again never takes one,
            // and one that stopped on a question the operator has to answer never got there.
            if (!$this->isARedraw() && !$proposal->hasErrors()) {
                (new ProposalTerm())->endWhatIsBilledFor($proposal);
            }

            // Changing the contract redraws the form so that its versions and services are the
            // ones that contract has; it is not an attempt to save anything yet.
            if (!$this->isARedraw() && $this->saveProposal($proposal)) {
                // A proposal is read after it is written, not the record it hangs on: what was
                // just said about it is the thing worth seeing, and the way back to the card is
                // on the page.
                return $this->redirect(['action' => 'view', $proposal->id]);
            }
        }

        $this->set('contractProposal', $proposal);
        $this->setFormViewVars($proposal);

        return null;
    }

    /**
     * Whether this request is the form asking to be drawn again rather than to be saved.
     *
     * @return bool
     */
    private function isARedraw(): bool
    {
        return $this->getRequest()->getData('refresh') === 'refresh';
    }

    /**
     * Puts the papers together again from the contract as it stands now.
     *
     * What the papers are about - the contract, its version, what they are for, the days they turn
     * on - is settled when they are drawn up. All of it is either photographed or written into the
     * changes the moment it is said, so a second reading of those fields would leave the papers
     * printing one thing and the records doing another. Wanting any of it different is wanting
     * different papers: the way to that is to delete these and draw up new ones.
     *
     * What is left here is what nothing is worked out from - the questions the operator answers,
     * the note, the number on the paper of the contract being ended, and which envelope the papers
     * go out in - and a fresh photograph of the contract, which is the reason to come here at all.
     *
     * The documents drawn from the old photograph go with it, because that is what makes them
     * wrong. Only the ones we drew: an ending is filed with the customer's own notice and with what
     * an office wrote, and neither is ours to throw away.
     *
     * @param string|null $id Contract version proposal id.
     * @return \Cake\Http\Response|null Redirects to the proposal, or renders the form.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function recreate(?string $id = null): ?Response
    {
        $proposal = $this->ContractProposals->get($id, contain: ['CustomerProposals']);

        if (!$this->ContractProposals->mayBeRecreated($proposal)) {
            $this->Flash->error(
                (new ContractDocuments())->linesLock($proposal)?->label()
                    ?? __('This proposal can no longer be changed.'),
            );

            return $this->redirect(['action' => 'view', $id]);
        }

        // Read before anything is written, because the form has to say what stands to be thrown
        // away before the operator agrees to it.
        $drawnFromTheOldOne = (new ContractDocuments())->drawnFrom($proposal);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = $this->request->getData();

            $proposal = $this->ContractProposals->patchEntity(
                $proposal,
                array_intersect_key($data, array_flip(self::MAY_BE_PUT_RIGHT))
                    + ['confirmations' => (new ProposalForm())->confirmationsFrom($data)],
                ['validate' => false],
            );

            $before = $proposal->stateOfThings();
            $drafting = new ProposalDrafting();

            if (!$drafting->takeANewSnapshot($proposal)) {
                $this->Flash->error(__('A new snapshot of this contract cannot be taken. Delete'
                    . ' the proposal and create a new one.'));

                return $this->redirect(['action' => 'view', $id]);
            }

            // Saying yes to it is the whole point of the box, so a submission without it is the
            // operator not having seen what it says.
            if ($drawnFromTheOldOne !== [] && (toBool($data['discard_the_documents'] ?? null) ?? false) !== true) {
                $proposal->setError('discard_the_documents', [
                    __('Confirm that the generated documents may be deleted.'),
                ]);
            }

            $takenBack = $drafting->updateLinesForNewSnapshot($proposal, $before);

            if ($this->saveProposal($proposal)) {
                $this->sayWhatWasTakenBack($takenBack['billings']);
                if ($takenBack['returns'] > 0) {
                    $this->Flash->warning(__('A borrowed equipment the proposal ended is no longer on the'
                        . ' contract, so its line has been taken back.'));
                }
                $this->sayWhatWasDiscarded($this->discardTheDocuments($drawnFromTheOldOne));
                $this->Flash->success(__('The contract proposal has been recreated.'));

                return $this->redirect(['action' => 'view', $id]);
            }
        }

        $contract = (new ProposalDrafting())->contractFor((string)$proposal->contract_id);

        $this->set('contractProposal', $proposal);
        $this->set('questions', $this->readinessQuestions(
            $contract,
            $proposal->keepsVersions() && $proposal->purpose->needsAVersion(),
            $proposal->proposedChanges(),
        ));
        $this->set('wording', ReadinessChecks::wording());
        $this->set('asksAboutTheTerm', $this->ContractProposals->lastDayOfTheTerm($proposal) !== null);
        $this->set('proposals', $this->openProposalsOf($contract->customer_id ?? $this->customer_id));
        $this->set('contractNumbers', $this->numbersOfferedFor($contract));
        $this->set('documentsToDiscard', $drawnFromTheOldOne);

        return null;
    }

    /**
     * Tells the operator how many lines a fresh snapshot took back, where it took any.
     *
     * @param int $takenBack How many.
     * @return void
     */
    private function sayWhatWasTakenBack(int $takenBack): void
    {
        if ($takenBack === 0) {
            return;
        }

        $this->Flash->warning(__n(
            'One line asked about a billing that is no longer on the contract and has'
            . ' been taken back.',
            '{0} lines asked about billings that are no longer on the contract and have'
            . ' been taken back.',
            $takenBack,
            $takenBack,
        ));
    }

    /**
     * Tells the operator how many documents went with the snapshot they were drawn from.
     *
     * @param int $discarded How many.
     * @return void
     */
    private function sayWhatWasDiscarded(int $discarded): void
    {
        if ($discarded === 0) {
            return;
        }

        $this->Flash->warning(__n(
            'One generated document has been deleted with the old snapshot.',
            '{0} generated documents have been deleted with the old snapshot.',
            $discarded,
            $discarded,
        ));
    }

    /**
     * Throws away the documents drawn from the proposal, so that its lines may be changed again.
     *
     * The paper on file says what the lines said when it was drawn, and it is never drawn again,
     * so the lines wait until it is gone. A copy the customer has signed is not ours to throw
     * away, and the operator is told why.
     *
     * @param string|null $id Contract proposal id.
     * @return \Cake\Http\Response|null Redirects to the proposal.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function discardDocuments(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post']);

        $proposal = $this->ContractProposals->get($id, contain: ['CustomerProposals']);

        if (!$this->ContractProposals->mayBeEdited($proposal)) {
            $this->Flash->error(__('This proposal can no longer be changed.'));

            return $this->redirect(['action' => 'view', $id]);
        }

        $lock = (new ContractDocuments())->linesLock($proposal);

        if ($lock !== null && !$lock->mayBeLifted()) {
            $this->Flash->error($lock->label());

            return $this->redirect(['action' => 'view', $id]);
        }

        $discarded = $this->discardTheDocuments((new ContractDocuments())->drawnFrom($proposal));

        if ($discarded > 0) {
            $this->Flash->success(__n(
                'One generated document has been deleted. The lines may be changed again.',
                '{0} generated documents have been deleted. The lines may be changed again.',
                $discarded,
                $discarded,
            ));
        }

        return $this->redirect(['action' => 'view', $id]);
    }

    /**
     * Throws away the documents the old snapshot was printed into.
     *
     * Only once the papers themselves are safely put together again: a saving refused over a
     * question nobody answered is the ordinary way out of this form, and it must not be the way
     * the documents go.
     *
     * @param array<\Files\Model\Entity\FileLink> $documents Which ones.
     * @return int How many went.
     */
    private function discardTheDocuments(array $documents): int
    {
        $storage = new FileStorage();
        $discarded = 0;

        foreach ($documents as $document) {
            try {
                $storage->unlink($document);
                $discarded++;
            } catch (Throwable) {
                // The papers are already right; a file left behind is untidy rather than wrong,
                // and saying so is better than undoing what was just put together.
                $this->Flash->warning(__('A generated document could not be deleted. Please'
                    . ' delete it manually.'));
            }
        }

        return $discarded;
    }

    /**
     * The questions asked before papers go out, where there are any to ask.
     *
     * They are about what goes on paper, and a contract whose service keeps no versions has none.
     *
     * @param \App\Model\Entity\Contract|null $contract The contract, where there is one.
     * @param bool $keepsVersions Whether its service keeps versions.
     * @param \App\Contracts\Proposal\ProposalChanges|null $changes What the proposal asks for, where
     *   it has been drawn up already.
     * @return array<string>
     */
    private function readinessQuestions(
        ?Contract $contract,
        bool $keepsVersions,
        ?ProposalChanges $changes = null,
    ): array {
        return $contract === null || !$keepsVersions
            ? []
            : (new ReadinessChecks())->questionsFor($contract, $changes);
    }

    /**
     * The numbers the paper of an ending may name, which are chosen rather than typed.
     *
     * Contracts concluded before the renumbering carry the customer number, one contract to a
     * customer, so both are worth offering and nothing else ever is.
     *
     * @param \App\Model\Entity\Contract|null $contract The contract, where there is one.
     * @return array<string, string>
     */
    private function numbersOfferedFor(?Contract $contract): array
    {
        $numbers = $contract === null ? [] : array_values(array_unique(array_filter([
            $contract->number,
            $contract->customer->number ?? null,
        ])));

        return array_combine($numbers, $numbers);
    }

    /**
     * Puts a line into the proposal, or changes one that is already there.
     *
     * A line is edited on a page of its own, the same way a billing on a contract is, so that the
     * operator does what they are used to doing. What travels is one line, not a whole table.
     *
     * @param string|null $id Contract version proposal id.
     * @param string|null $line The line being changed; a new one when there is none.
     * @return \Cake\Http\Response|null Redirects when saved, renders the form otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function billingLine(?string $id = null, ?string $line = null): ?Response
    {
        $proposal = $this->openProposal($id);

        if (!$proposal instanceof ContractProposal) {
            return $proposal;
        }

        $changes = $proposal->proposedChanges();
        $edited = $line === null ? null : $changes->line($line);

        if ($line !== null && $edited === null) {
            throw new RecordNotFoundException(__('There is no such line on this proposal.'));
        }

        // Changing something that is already billed for starts from what is there.
        $replaces = $edited->billing_id ?? $this->named('replaces');
        $replaced = $replaces === null ? null : $this->billingOnTheContract($proposal, $replaces);

        $form = new ProposedBillingForm();
        $lastDay = $this->ContractProposals->lastDayOfARunningTerm($proposal);
        $written = null;
        $refused = null;
        $pastTheTerm = null;

        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = $this->request->getData();
            $service_id = $data['service_id'] ?? null;

            $written = $form->read(
                $data + ['billing_id' => $replaces],
                $edited,
                $this->chosenService(is_string($service_id) ? $service_id : null),
                $this->mayGoBelowMinimum(),
            );

            $refused = $this->belowTheMinimum($proposal, $changes->withLine($written), $written);
            $pastTheTerm = $this->pastTheTerm($proposal, $written, $lastDay);

            if ($refused !== null || $pastTheTerm !== null) {
                $this->Flash->error((string)($refused ?? $pastTheTerm));
            } elseif ($this->saveChanges($proposal, $changes->withLine($written))) {
                return $this->redirect(['action' => 'view', $proposal->id]);
            }
        }

        // a refused line comes back as it was typed, not as it was before
        $values = $written?->toArray() ?? $form->fill($edited, $replaced, $lastDay);

        $this->set('contractProposal', $proposal);
        $this->set('line', $edited);
        $this->set('replaced', $replaced);
        $this->set('values', $values);
        $this->set('below_minimum_override', $this->mayGoBelowMinimum());
        $this->set('below_minimum_refused', $refused);
        $this->set('last_day_of_the_term', $lastDay);
        $this->set('past_the_term_refused', $pastTheTerm);
        $this->set('services', $this->servicesFor($proposal, [
            $edited?->service_id,
            $values['service_id'] ?? null,
        ]));

        return null;
    }

    /**
     * Why the line just written may not stand, where it would bill past a fixed term.
     *
     * @param \App\Model\Entity\ContractProposal $proposal The proposal.
     * @param \App\Contracts\Proposal\ProposedBilling $written The line just written.
     * @param \Cake\I18n\Date|null $lastDay The last day of the term, where one is running.
     * @return string|null What the operator is told, or null where the line may stand.
     */
    private function pastTheTerm(ContractProposal $proposal, ProposedBilling $written, ?Date $lastDay): ?string
    {
        if ($lastDay === null || $written->staysWithin($lastDay, $proposal->effective_from)) {
            return null;
        }

        return __('The contract runs for a fixed term until {0}, and nothing is billed past it.', $lastDay);
    }

    /**
     * Why the line just written may not stand, where it prices the connection below the minimum.
     *
     * Asked here because this is the one place a priced line is written, and of that line alone:
     * the lines already standing were asked when they were written, and a minimum raised since is
     * for the preview of the changes to say. What guards the records is the billing itself when the
     * proposal is applied - this only saves the operator finding out there.
     *
     * @param \App\Model\Entity\ContractProposal $proposal The proposal.
     * @param \App\Contracts\Proposal\ProposalChanges $changes What it would ask for with the line in it.
     * @param \App\Contracts\Proposal\ProposedBilling $written The line just written.
     * @return string|null What the operator is told, or null where the line may stand.
     */
    private function belowTheMinimum(
        ContractProposal $proposal,
        ProposalChanges $changes,
        ProposedBilling $written,
    ): ?string {
        /** @var \App\Model\Table\BillingsTable $billings */
        $billings = $this->fetchTable('Billings');
        $minimum = $billings->minimumConnectionPriceOf($proposal->contract_id);

        if ($minimum === null) {
            return null;
        }

        // The proposal as it would read with the line in it, without touching the one being edited.
        $withTheLine = clone $proposal;
        $withTheLine->set('changes', $changes->toArray());

        $below = MinimumConnectionPrice::linesBelow(
            $withTheLine,
            $minimum,
            fn(ProposedBilling $line): bool => $line->id === $written->id,
        );

        return $below === [] ? null : MinimumConnectionPrice::refusal($minimum);
    }

    /**
     * Stops billing for something, with nothing taking its place.
     *
     * @param string|null $id Contract version proposal id.
     * @param string|null $billing_id The billing that is to stop.
     * @return \Cake\Http\Response|null Redirects to the proposal.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function endBilling(?string $id = null, ?string $billing_id = null): ?Response
    {
        $this->request->allowMethod(['post']);

        $proposal = $this->openProposal($id);

        if (!$proposal instanceof ContractProposal) {
            return $proposal;
        }

        $changes = $proposal->proposedChanges();

        // One line to a billing: ending what is already being replaced replaces that line.
        $existing = $changes->billingsByBillingId()[(string)$billing_id] ?? null;
        if ($existing !== null) {
            $changes = $changes->withoutLine($existing->id);
        }

        $line = (new ProposedBillingForm())->ending((string)$billing_id);

        if (!$this->saveChanges($proposal, $changes->withLine($line))) {
            return $this->redirect(['action' => 'view', $proposal->id]);
        }

        // Said here rather than only at the button: a billing that has not begun yet is taken off
        // the contract rather than ended, and this is the moment the operator decided it.
        /** @var \App\Model\Entity\Billing|null $billing */
        $billing = $this->ContractProposals->Contracts->Billings
            ->find()
            ->where(['Billings.id' => (string)$billing_id])
            ->first();

        if ($billing !== null && $line->neverRunsAfterAll($proposal->effective_from, $billing->billing_from)) {
            $this->Flash->info(__(
                'This billing begins on {0}, after the day the proposal takes effect, so it never'
                . ' runs. Applying the changes will remove it from the contract rather than end'
                . ' it.',
                $billing->billing_from,
            ));
        }

        return $this->redirect(['action' => 'view', $proposal->id]);
    }

    /**
     * Writes down that somebody has settled what applying the proposal left out.
     *
     * What was left out stays on the proposal for the record. This only says it has been dealt
     * with, so that the contract stops carrying a finding about it - a job nobody can close is a
     * listing nobody goes on reading.
     *
     * @param string|null $id Contract proposal id.
     * @return \Cake\Http\Response|null Redirects to the proposal.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function settleWhatWasLeftOut(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post']);

        $proposal = $this->ContractProposals->get($id);

        if ($proposal->wasAppliedInFull()) {
            $this->Flash->warning(__('Nothing was left out of this proposal.'));

            return $this->redirect(['action' => 'view', $id]);
        }

        $by = $this->getRequest()->getAttribute('identity')['id'] ?? null;

        $proposal->set('left_out_settled', DateTime::now());
        $proposal->set('left_out_settled_by', is_string($by) ? $by : null);

        if ($this->ContractProposals->save($proposal, ['checkRules' => false])) {
            $this->Flash->success(__('What was left out has been marked as settled.'));
        } else {
            $this->Flash->error(__('It could not be marked as settled. Please, try again.'));
        }

        return $this->redirect(['action' => 'view', $proposal->id]);
    }

    /**
     * Takes a line back out of the proposal, leaving whatever it acted on as it was.
     *
     * @param string|null $id Contract version proposal id.
     * @param string|null $line Which line.
     * @return \Cake\Http\Response|null Redirects to the proposal.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function dropBillingLine(?string $id = null, ?string $line = null): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);

        $proposal = $this->openProposal($id);

        if (!$proposal instanceof ContractProposal) {
            return $proposal;
        }

        $this->saveChanges($proposal, $proposal->proposedChanges()->withoutLine((string)$line));

        return $this->redirect(['action' => 'view', $proposal->id]);
    }

    /**
     * Lends a piece of equipment by the proposal, or changes what an earlier line lends.
     *
     * Edited on a page of its own, the same way a billing line is. The equipment reaches the
     * contract only when the changes are applied, on the day it was handed over.
     *
     * @param string|null $id Contract proposal id.
     * @param string|null $line The line being changed; a new one when there is none.
     * @return \Cake\Http\Response|null Redirects when saved, renders the form otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function equipmentLine(?string $id = null, ?string $line = null): ?Response
    {
        $proposal = $this->openProposal($id);

        if (!$proposal instanceof ContractProposal) {
            return $proposal;
        }

        $changes = $proposal->proposedChanges();
        $edited = $line === null ? null : $changes->equipmentLine($line);

        if ($line !== null && ($edited === null || !$edited->lends())) {
            throw new RecordNotFoundException(__('There is no such line on this proposal.'));
        }

        $values = $edited?->toArray() ?? [];

        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = $this->request->getData();
            $values = array_intersect_key($data, array_flip(['equipment_type_id', 'serial_number', 'note']));
            $type = $this->equipmentTypeFor((string)($data['equipment_type_id'] ?? ''));

            if ($type === null) {
                $this->Flash->error(__('Choose the equipment type.'));
            } else {
                $written = new ProposedEquipment(
                    id: $edited->id ?? Text::uuid(),
                    move: EquipmentMove::Lend,
                    borrowed_equipment_id: null,
                    equipment_type_id: (string)$type->id,
                    serial_number: $this->filledIn($data['serial_number'] ?? null),
                    equipment_type: $type->extract(SnapshotShape::EQUIPMENT_TYPE),
                    note: $this->filledIn($data['note'] ?? null),
                );

                if ($this->saveChanges($proposal, $changes->withEquipmentLine($written))) {
                    return $this->redirect(['action' => 'view', $proposal->id]);
                }
            }
        }

        $this->set('contractProposal', $proposal);
        $this->set('line', $edited);
        $this->set('values', $values);
        $this->set('equipmentTypes', $this->ContractProposals->Contracts->BorrowedEquipments->EquipmentTypes
            ->find('list', order: ['name']));

        return null;
    }

    /**
     * Takes back a piece of equipment the contract has lent, by the proposal.
     *
     * @param string|null $id Contract proposal id.
     * @param string|null $borrowed_equipment_id What is taken back.
     * @return \Cake\Http\Response|null Redirects to the proposal.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function returnEquipment(?string $id = null, ?string $borrowed_equipment_id = null): ?Response
    {
        $this->request->allowMethod(['post']);

        $proposal = $this->openProposal($id);

        if (!$proposal instanceof ContractProposal) {
            return $proposal;
        }

        $changes = $proposal->proposedChanges();

        // One line to a piece: taking back what is already being taken back changes nothing.
        if (!isset($changes->equipmentTakenBack()[(string)$borrowed_equipment_id])) {
            $this->saveChanges($proposal, $changes->withEquipmentLine(new ProposedEquipment(
                id: Text::uuid(),
                move: EquipmentMove::Return,
                borrowed_equipment_id: (string)$borrowed_equipment_id,
                equipment_type_id: null,
            )));
        }

        return $this->redirect(['action' => 'view', $proposal->id]);
    }

    /**
     * Takes an equipment line back out of the proposal, leaving the contract's equipment as it is.
     *
     * @param string|null $id Contract proposal id.
     * @param string|null $line Which line.
     * @return \Cake\Http\Response|null Redirects to the proposal.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function dropEquipmentLine(?string $id = null, ?string $line = null): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);

        $proposal = $this->openProposal($id);

        if (!$proposal instanceof ContractProposal) {
            return $proposal;
        }

        $this->saveChanges($proposal, $proposal->proposedChanges()->withoutEquipmentLine((string)$line));

        return $this->redirect(['action' => 'view', $proposal->id]);
    }

    /**
     * One kind of equipment, as it stands now.
     *
     * @param string $id Which kind.
     * @return \App\Model\Entity\EquipmentType|null
     */
    private function equipmentTypeFor(string $id): ?EquipmentType
    {
        if (!Validation::uuid($id)) {
            return null;
        }

        /** @var \App\Model\Entity\EquipmentType|null $type */
        $type = $this->ContractProposals->Contracts->BorrowedEquipments->EquipmentTypes
            ->find()
            ->where(['EquipmentTypes.id' => $id])
            ->first();

        return $type;
    }

    /**
     * What a field of the form says, with nothing written meaning nothing said.
     *
     * @param mixed $value What the field sent.
     * @return string|null
     */
    private function filledIn(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    /**
     * The proposal, if its lines may still be changed.
     *
     * Where a paper drawn from it stands in the way, the operator is told which and what to do.
     *
     * @param string|null $id Contract version proposal id.
     * @return \App\Model\Entity\ContractProposal|\Cake\Http\Response|null
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    private function openProposal(?string $id): ContractProposal|Response|null
    {
        $proposal = $this->ContractProposals->get($id, contain: [
            'Contracts' => ['ServiceTypes', 'InstallationAddresses'],
            'CustomerProposals',
        ]);

        if (!$this->ContractProposals->mayBeEdited($proposal)) {
            $this->Flash->error(__('This proposal can no longer be changed.'));

            return $this->redirect(['action' => 'view', $id]);
        }

        $lock = (new ContractDocuments())->linesLock($proposal);

        if ($lock !== null) {
            $this->Flash->error($lock->label());

            return $this->redirect(['action' => 'view', $id]);
        }

        return $proposal;
    }

    /**
     * Saves what the proposal now asks for.
     *
     * @param \App\Model\Entity\ContractProposal $proposal The proposal.
     * @param \App\Contracts\Proposal\ProposalChanges $changes What it asks for.
     * @return bool
     */
    private function saveChanges(ContractProposal $proposal, ProposalChanges $changes): bool
    {
        $proposal = $this->ContractProposals->patchEntity($proposal, [
            'changes' => $changes->toArray(),
        ]);

        if ($this->ContractProposals->save($proposal)) {
            $this->Flash->success(__('The proposal has been saved.'));

            return true;
        }

        $this->flashValidationErrors($proposal->getErrors());
        $this->Flash->error(__('The proposal could not be saved. Please, try again.'));

        return false;
    }

    /**
     * One of the billings the proposal's snapshot took down.
     *
     * @param \App\Model\Entity\ContractProposal $proposal The proposal.
     * @param string $billing_id Which billing.
     * @return \App\Model\Entity\Billing|null
     */
    private function billingOnTheContract(
        ContractProposal $proposal,
        string $billing_id,
    ): ?Billing {
        foreach ($proposal->stateOfThings()->hydrate()->billings as $billing) {
            if ((string)$billing->id === $billing_id) {
                return $billing;
            }
        }

        return null;
    }

    /**
     * The services a line on this proposal may be for.
     *
     * What is no longer sold is left out, the same as on a billing added to a contract - a proposal
     * is where new arrangements are made, and offering a tariff nobody may take is offering to make
     * a mistake.
     *
     * Except what is already chosen. Changing a line on a service that has since been retired is
     * exactly what somebody comes to this page for, and a list without it would quietly move them
     * onto another tariff.
     *
     * @param \App\Model\Entity\ContractProposal $proposal The proposal.
     * @param array<int|string|null> $keep Services already chosen, which stay on the list whatever
     *   they are.
     * @return \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Service>
     */
    private function servicesFor(ContractProposal $proposal, array $keep = []): SelectQuery
    {
        $contract = (new ProposalDrafting())->contractFor((string)$proposal->contract_id);
        $keep = array_values(array_filter($keep));

        $query = $this->ContractProposals->Contracts->Billings->Services
            ->find('list', order: ['name'])
            ->where($contract === null ? [] : ['OR' => [
                'Services.service_type_id' => $contract->service_type_id,
                'Services.service_type_id IS' => null,
            ]]);

        $offered = ['Services.currently_offered' => true];

        return $query->where($keep === []
            ? $offered
            : ['OR' => [$offered, ['Services.id IN' => $keep]]]);
    }

    /**
     * Delete method
     *
     * Afterwards the reader is on the proposal put to the customer that held these papers, which
     * is still there. What could not be deleted leaves them on the papers themselves.
     *
     * @param string|null $id Contract version proposal id.
     * @return \Cake\Http\Response|null Redirects to the proposal that held it, or back to it.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $this->getRequest()->allowMethod(['post', 'delete']);
        $proposal = $this->ContractProposals->get($id, contain: ['CustomerProposals']);

        if (!$this->ContractProposals->delete($proposal)) {
            $this->flashValidationErrors($proposal->getErrors());
            $this->Flash->error(__('The proposal could not be deleted. Please, try again.'));

            return $this->redirect(['action' => 'view', $id]);
        }

        $this->Flash->success(__('The proposal has been deleted.'));

        return $this->redirect([
            'controller' => 'CustomerProposals',
            'action' => 'view',
            $proposal->customer_proposal_id,
            'customer_id' => $proposal->customer_proposal->customer_id,
        ]);
    }

    /**
     * Gives up on the proposal, which touches nothing else - the live records never moved.
     *
     * @param string|null $id Contract version proposal id.
     * @return \Cake\Http\Response|null Redirects to the proposal.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function revoke(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post']);

        $proposal = $this->ContractProposals->get($id, contain: ['CustomerProposals']);

        if (!$proposal->isOpen()) {
            $this->Flash->warning(__('This proposal has already been settled.'));

            return $this->redirect(['action' => 'view', $id]);
        }

        $proposal->revoked = DateTime::now();
        $proposal->revoked_by = $this->getRequest()->getAttribute('identity')['id'] ?? null;

        if ($this->ContractProposals->save($proposal, ['checkRules' => false])) {
            $this->Flash->success(__('The proposal has been revoked.'));
        } else {
            $this->flashValidationErrors($proposal->getErrors());
            $this->Flash->error(__('The proposal could not be revoked. Please, try again.'));
        }

        return $this->redirect(['action' => 'view', $id]);
    }

    /**
     * One of the things a link may have settled before the form was opened.
     *
     * @param string $what Which one.
     * @return string|null
     */
    private function named(string $what): ?string
    {
        $named = $this->getRequest()->getQuery($what);

        return is_string($named) && $named !== '' ? $named : null;
    }

    /**
     * Saves the proposal, having first asked the contract whether it is ready to have papers drawn
     * up for it at all.
     *
     * @param \App\Model\Entity\ContractProposal $proposal The proposal.
     * @return bool
     */
    private function saveProposal(ContractProposal $proposal): bool
    {
        if ($proposal->getErrors() !== []) {
            $this->flashValidationErrors($proposal->getErrors());

            return false;
        }

        $contract = (new ProposalDrafting())->contractFor((string)$proposal->contract_id);

        // The questions are about what goes on paper, and a contract that keeps no versions has none.
        if ($contract !== null && $proposal->keepsVersions() && $proposal->purpose->needsAVersion()) {
            $unanswered = (new ReadinessChecks())
                ->unansweredFor($contract, $proposal->confirmations(), $proposal->proposedChanges());

            if ($unanswered !== []) {
                // Each answer is a box on the form named after this column, so the complaint lands
                // on the box rather than on nothing the operator can see.
                $wording = ReadinessChecks::wording();

                foreach ($unanswered as $question) {
                    $proposal->setError('confirmations.' . $question, [
                        $wording[$question] ?? __('Please answer this before the proposal is'
                            . ' sent.'),
                    ]);
                }

                $this->Flash->error(__('The contract is not ready for a contract proposal.'));

                return false;
            }
        }

        // Nothing written inside a transaction reaches the audit log on its own, and the proposal
        // a proposal is put into is written with it.
        $trail = new AuditTrail();

        $saved = $this->ContractProposals->getConnection()->transactional(
            fn(): bool => $this->giveItAProposalToBePartOf($proposal, $trail)
                && (bool)$this->ContractProposals->save($proposal, $trail->options()),
        );

        if ($saved) {
            $trail->flush($this->ContractProposals, $proposal);

            $this->Flash->success(__('The proposal has been saved.'));

            return true;
        }

        $this->flashValidationErrors($proposal->getErrors());
        $this->Flash->error(__('The proposal could not be saved. Please, try again.'));

        return false;
    }

    /**
     * The service a line chose, as it stands now.
     *
     * A line that puts a different service on a billing keeps it with it, because the contract's
     * own snapshot was taken before it was chosen.
     *
     * @param string|null $id Which service.
     * @return array<string, mixed>|null
     */
    private function chosenService(?string $id): ?array
    {
        if ($id === null || $id === '') {
            return null;
        }

        $services = $this->ContractProposals->Contracts->Billings->Services
            ->find()
            ->contain(['ConnectionProfiles'])
            ->where(['Services.id' => $id])
            ->first();

        if ($services === null) {
            return null;
        }

        return $services->extract(SnapshotShape::SERVICE)
            + ['connection_profile' => $services->connection_profile?->extract(SnapshotShape::CONNECTION_PROFILE)];
    }

    /**
     * What the form needs to draw itself.
     *
     * @param \App\Model\Entity\ContractProposal $proposal The proposal.
     * @return void
     */
    private function setFormViewVars(ContractProposal $proposal): void
    {
        $contract = (new ProposalDrafting())->contractFor(
            (string)($proposal->contract_id ?? $this->contract_id ?? $this->named('contract_id') ?? ''),
        );

        // Named the way every other form names a contract: its number, the service and where it is.
        $contracts = $this->contractsList();
        if ($this->customer_id !== null) {
            $contracts->where(['Contracts.customer_id' => $this->customer_id]);
        }

        // A contract whose service keeps no versions offers none, and asks nothing about paper.
        $keepsVersions = $contract?->service_type->have_contract_versions ?? true;

        $versions = $this->ContractProposals->ContractVersions
            ->find('list', valueField: 'name')
            ->where($contract === null || !$keepsVersions
                ? ['1 = 0']
                : ['ContractVersions.contract_id' => $contract->id])
            ->orderBy(['ContractVersions.valid_from' => 'DESC']);

        $questions = $this->readinessQuestions(
            $contract,
            $keepsVersions && ($proposal->purpose ?? ProposalPurpose::NewContract)->needsAVersion(),
        );
        $contractNumbers = $this->numbersOfferedFor($contract);

        $version = (new ProposalDrafting())->versionFor((string)$proposal->contract_version_id);

        // The day the field falls back on when it is left empty, so the hint can name it.
        $effectiveFromDefault = $version?->valid_from;

        // The day a minimum term usually runs to, offered rather than typed. Counted from the day
        // the papers take effect, which is what the operator has said by now or the version says.
        $takesEffect = $proposal->effective_from ?? $effectiveFromDefault;
        $obligationOffered = $takesEffect === null ? null : TheUsualTerm::from($takesEffect);

        $purpose = $proposal->purpose ?? ProposalPurpose::NewContract;
        $purposes = ProposalPurpose::options();

        // A new contract replaces an earlier version only once that one is on record and signed,
        // so the form says what is missing where the operator chooses it.
        $replacing = $purpose === ProposalPurpose::NewContract && $keepsVersions && $contract !== null;
        $nothingToReplace = $replacing && $versions->count() === 0;
        $toReplace = $replacing
            ? (new ProposalDrafting())->versionFor((string)$proposal->terminates_contract_version_id)
            : null;
        $unconcludedToReplace = $toReplace?->conclusion_date === null ? $toReplace : null;

        // The envelopes of this customer that are still open, so papers drawn up now can go out
        // with whatever else is already waiting to.
        $proposals = $this->openProposalsOf($contract->customer_id ?? $this->customer_id);

        // And what a proposal drawn up here and now would ask of the customer themselves, which
        // is most often nothing: the papers of the contract are the point of it.
        $proposalPurposes = CustomerProposalPurpose::forContractProposals();

        $this->set(compact(
            'contracts',
            'keepsVersions',
            'versions',
            'questions',
            'contractNumbers',
            'purpose',
            'purposes',
            'proposals',
            'proposalPurposes',
            'effectiveFromDefault',
            'obligationOffered',
            'nothingToReplace',
            'unconcludedToReplace',
        ));
        $this->set('wording', ReadinessChecks::wording());
    }

    /**
     * Makes sure the papers are part of a proposal, drawing one up where they are not.
     *
     * There is one proposal and it is put to the customer; what it does for a contract is a part
     * of it. Papers drawn up from the contract rather than from a proposal get one of their own,
     * so that nothing ever stands outside one - and the form says what that one asks of the
     * customer themselves, which is most often nothing at all.
     *
     * @param \App\Model\Entity\ContractProposal $proposal The papers.
     * @param \App\Model\Audit\AuditTrail $trail What the writes of this transaction are logged under.
     * @return bool
     */
    private function giveItAProposalToBePartOf(ContractProposal $proposal, AuditTrail $trail): bool
    {
        if ($proposal->customer_proposal_id !== null) {
            return true;
        }

        $contract = $this->ContractProposals->Contracts
            ->find()
            ->where(['Contracts.id' => $proposal->contract_id])
            ->first();

        if ($contract === null) {
            return true;
        }

        $proposals = $this->ContractProposals->CustomerProposals;
        $customer_proposal = $proposals->newEmptyEntity();

        $customer_proposal->set('customer_id', $contract->customer_id);
        $customer_proposal->set('effective_from', $proposal->effective_from);
        $customer_proposal->set('purpose', CustomerProposalPurpose::tryFrom(
            (string)$this->getRequest()->getData('new_proposal_purpose'),
        ));

        if (!$proposals->save($customer_proposal, $trail->options())) {
            $proposal->setError('customer_proposal_id', [
                __('A customer proposal for this contract proposal could not be created.'),
            ]);

            return false;
        }

        $proposal->set('customer_proposal_id', $customer_proposal->id);

        return true;
    }

    /**
     * The proposal the form was opened from, where it was opened from one.
     *
     * @return \App\Model\Entity\CustomerProposal|null
     */
    private function proposalAskedFor(): ?CustomerProposal
    {
        $id = $this->named('customer_proposal_id') ?? $this->getRequest()->getQuery('proposal_id');

        if (!is_string($id) || $id === '') {
            return null;
        }

        /** @var \App\Model\Entity\CustomerProposal|null $proposal */
        $proposal = $this->ContractProposals->CustomerProposals
            ->find()
            ->where(['CustomerProposals.id' => $id])
            ->first();

        return $proposal;
    }

    /**
     * The customer's proposals that have not been settled, as a list to pick from.
     *
     * @param string|null $customer_id Whose proposals.
     * @return array<string, string>
     */
    private function openProposalsOf(?string $customer_id): array
    {
        if ($customer_id === null) {
            return [];
        }

        $proposals = $this->ContractProposals->CustomerProposals
            ->find('open')
            ->contain(['ContractProposals'])
            ->where(['CustomerProposals.customer_id' => $customer_id])
            ->orderByDesc('CustomerProposals.effective_from');

        $found = [];

        foreach ($proposals as $customer_proposal) {
            if ($customer_proposal->purpose !== null && !$customer_proposal->purpose->comesBackSigned()) {
                continue;
            }

            // One that has gone out takes nothing more, so offering it only sets up a refusal.
            if (!$this->ContractProposals->CustomerProposals->mayBeEdited($customer_proposal)) {
                continue;
            }

            $found[(string)$customer_proposal->id] = sprintf(
                '%s - %s',
                $customer_proposal->effective_from,
                $customer_proposal->whatItIsFor(),
            );
        }

        return $found;
    }

    /**
     * What the detail of a proposal needs to draw itself.
     *
     * @param \App\Model\Entity\ContractProposal $proposal The proposal.
     * @return void
     */
    private function setProposalViewVars(ContractProposal $proposal): void
    {
        $this->set((new ProposalReading())->of($proposal));
        $this->set('mayBeRecreated', $this->ContractProposals->mayBeRecreated($proposal));
        $this->set('mayBeDeleted', $this->ContractProposals->mayBeDeleted($proposal));
        // Only the count: the table itself is drawn by a cell, which asks for what it draws.
        $this->set('filed', (new ContractDocuments())->filedAgainst([$proposal])[$proposal->id] ?? []);
    }
}
