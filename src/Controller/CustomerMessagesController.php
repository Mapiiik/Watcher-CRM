<?php
declare(strict_types=1);

namespace App\Controller;

use App\BulkMessages\BulkRecipientFilterRegistry;
use App\BulkMessages\BulkRecipients;
use App\BulkMessages\BulkSendSummary;
use App\Controller\Traits\CommonViewVarListsTrait;
use App\Model\Audit\AuditTrail;
use App\Model\Entity\CustomerMessage;
use App\Model\Enum\CustomerMessageBodyFormat;
use App\Model\Enum\CustomerMessageDeliveryStatus;
use App\Model\Enum\CustomerMessageDirection;
use App\Model\Enum\CustomerMessagePurpose;
use App\Model\Enum\CustomerMessageType;
use App\NMS\ApiClient as NMSApiClient;
use Cake\Http\Response;
use Cake\Http\Session;
use Cake\Log\Log;
use Settings\Utility\Settings;
use Throwable;

/**
 * CustomerMessages Controller
 *
 * @property \App\Model\Table\CustomerMessagesTable $CustomerMessages
 * @phpstan-import-type SendSummary from \App\BulkMessages\BulkSendSummary
 */
class CustomerMessagesController extends AppController
{
    use CommonViewVarListsTrait;

    /**
     * Session key holding the bulk wizard state.
     *
     * @var string
     */
    private const BULK_WIZARD_STATE_KEY = 'CustomerMessages.bulkWizard';

    /**
     * Session key holding the one-shot bulk send result (for the done step).
     *
     * @var string
     */
    private const BULK_RESULT_KEY = 'CustomerMessages.bulkResult';

    /**
     * Index method
     *
     * @return void Renders view
     */
    public function index(): void
    {
        // filter
        $conditions = [];
        if ($this->customer_id !== null) {
            $conditions = ['CustomerMessages.customer_id' => $this->customer_id];
        }

        // search
        $search = $this->getRequest()->getQuery('search');
        if (!empty($search)) {
            $conditions[] = [
                'OR' => [
                    'CustomerMessages.subject ILIKE' => '%' . trim((string)$search) . '%',
                    'CustomerMessages.body ILIKE' => '%' . trim((string)$search) . '%',
                ],
            ];
        }

        $query = $this->CustomerMessages->find()
            ->contain([
                'Customers',
            ])
            ->where($conditions);

        $customerMessages = $this->paginate($query, [
            'order' => [
                'CustomerMessages.created' => 'DESC',
            ],
        ]);

        $this->set(compact('customerMessages'));
    }

    /**
     * View method
     *
     * @param string|null $id Customer Message id.
     * @return void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null): void
    {
        $customerMessage = $this->CustomerMessages->get($id, contain: [
            'Customers',
            'Creators',
            'Modifiers',
        ]);
        $this->set(compact('customerMessage'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add(): ?Response
    {
        $customerMessage = $this->CustomerMessages->newEmptyEntity();
        if ($this->request->is('post')) {
            $customerMessage = $this->CustomerMessages->patchEntity(
                $customerMessage,
                $this->dataWithNesting($this->CustomerMessages, $this->request->getData()),
            );
            if ($this->CustomerMessages->save($customerMessage)) {
                $this->Flash->success(__('The customer message has been saved.'));

                return $this->afterAddRedirect(['action' => 'view', $customerMessage->id]);
            }
            $this->Flash->error(__('The customer message could not be saved. Please, try again.'));
        }
        $customers = $this->CustomerMessages->Customers->find('list', order: [
            'company',
            'last_name',
            'first_name',
        ])->all();
        $this->set(compact('customerMessage', 'customers'));

        return null;
    }

    /**
     * Add Bulk method
     *
     * Multi-step wizard: pick a message purpose, narrow recipients with the
     * filters offered for that purpose, then compose and send. Recipient
     * selection honours the customer's mailing consent and each contact's
     * routing flags, unless the consent override is enabled.
     *
     * State is kept in the session between steps.
     *
     * @return \Cake\Http\Response|null Redirects on step change / successful add, renders view otherwise.
     */
    public function addBulk(): ?Response
    {
        $session = $this->getRequest()->getSession();

        // allow starting over from any step
        if ($this->getRequest()->getQuery('reset') !== null) {
            $session->delete(self::BULK_WIZARD_STATE_KEY);

            return $this->redirect(['action' => 'addBulk']);
        }

        /** @var array{purpose?: int, filters?: array<string, mixed>, preview?: list<string>, ignore_customer_consent?: bool, ignore_contact_use?: bool} $state */
        $state = (array)$session->read(self::BULK_WIZARD_STATE_KEY, []);
        $purpose = isset($state['purpose']) ? CustomerMessagePurpose::tryFrom($state['purpose']) : null;

        $registry = new BulkRecipientFilterRegistry($this->CustomerMessages);

        // handle step submissions — the submitted step is derived from the URL
        // query (each step's form posts to its own URL), not a hidden field, to
        // avoid a FormProtection pitfall where a locked field value equal to
        // another field's name drops that field from the security token.
        if ($this->request->is('post')) {
            $response = $this->handleBulkWizardPost(
                (string)($this->getRequest()->getQuery('step') ?? 'purpose'),
                $state,
                $purpose,
                $registry,
                $session,
            );
            if ($response instanceof Response) {
                return $response;
            }
            // a non-redirect return means the compose step failed validation;
            // re-read the (unchanged) state and fall through to re-render it
            $state = (array)$session->read(self::BULK_WIZARD_STATE_KEY, []);
            $purpose = isset($state['purpose']) ? CustomerMessagePurpose::tryFrom($state['purpose']) : null;
        }

        // render the requested step (falling back to purpose selection)
        $step = (string)($this->getRequest()->getQuery('step') ?? 'purpose');

        if ($step === 'done') {
            // one-shot send summary (post/redirect/get); safe to refresh
            $result = $session->consume(self::BULK_RESULT_KEY);
            if (!is_array($result)) {
                return $this->redirect(['action' => 'addBulk']);
            }
            $this->set('result', $result);
            $this->viewBuilder()->setTemplate('add_bulk/step_done');

            return null;
        }

        if ($purpose !== null && $step === 'filters') {
            $this->prepareBulkFilterStep($purpose, $registry, $state);
            $this->viewBuilder()->setTemplate('add_bulk/step_filters');
        } elseif ($purpose !== null && $step === 'compose') {
            $this->prepareBulkComposeStep($purpose, $registry, $state);
            $this->viewBuilder()->setTemplate('add_bulk/step_compose');
        } else {
            $this->prepareBulkPurposeStep();
            $this->viewBuilder()->setTemplate('add_bulk/step_purpose');
        }

        return null;
    }

    /**
     * Handle a POST from one of the wizard steps.
     *
     * Returns a redirect Response on success/advance, or null when the compose
     * step failed and the caller should re-render it with validation errors.
     *
     * @param string $step Submitted step name.
     * @param array{purpose?: int, filters?: array<string, mixed>, preview?: list<string>, ignore_customer_consent?: bool, ignore_contact_use?: bool} $state Current state.
     * @param \App\Model\Enum\CustomerMessagePurpose|null $purpose Selected purpose (from state).
     * @param \App\BulkMessages\BulkRecipientFilterRegistry $registry Filter registry.
     * @param \Cake\Http\Session $session Session instance.
     * @return \Cake\Http\Response|null
     */
    private function handleBulkWizardPost(
        string $step,
        array $state,
        ?CustomerMessagePurpose $purpose,
        BulkRecipientFilterRegistry $registry,
        Session $session,
    ): ?Response {
        if ($step === 'purpose') {
            $selected = CustomerMessagePurpose::tryFrom((int)$this->request->getData('purpose'));
            if ($selected === null) {
                $this->Flash->error(__('Please select a message purpose.'));

                return $this->redirect(['action' => 'addBulk']);
            }
            // changing the purpose resets any downstream selections, seeded with
            // the filter defaults so they hold even for a wizard that jumps
            // straight to the compose step without submitting the filter form
            $session->write(self::BULK_WIZARD_STATE_KEY, [
                'purpose' => $selected->value,
                'filters' => $registry->defaultsForPurpose($selected),
            ]);

            return $this->redirect(['action' => 'addBulk', '?' => ['step' => 'filters']]);
        }

        if ($purpose === null) {
            return $this->redirect(['action' => 'addBulk']);
        }

        if ($step === 'filters') {
            $data = $this->request->getData();
            $filters = [];
            foreach ($registry->forPurpose($purpose) as $filter) {
                $value = $filter->buildValue($data);
                if ($value !== null) {
                    $filters[$filter->id()] = $value;
                }
            }
            $state['filters'] = $filters;
            $state['ignore_customer_consent'] = (bool)$this->request->getData('ignore_customer_consent');
            $state['ignore_contact_use'] = (bool)$this->request->getData('ignore_contact_use');
            $session->write(self::BULK_WIZARD_STATE_KEY, $state);

            return $this->redirect(['action' => 'addBulk', '?' => ['step' => 'compose']]);
        }

        if ($step === 'compose') {
            if ($this->saveBulkMessages($purpose, $state, $registry)) {
                $session->delete(self::BULK_WIZARD_STATE_KEY);

                // the summary is shown on the done step (read from the session)
                return $this->redirect(['action' => 'addBulk', '?' => ['step' => 'done']]);
            }

            // fall through: caller re-renders the compose step with errors
            return null;
        }

        return $this->redirect(['action' => 'addBulk']);
    }

    /**
     * Build and persist one message per eligible recipient customer.
     *
     * The patched (and possibly invalid) message entity is exposed to the view
     * as `customerMessage` so validation errors are shown on re-render.
     *
     * @param \App\Model\Enum\CustomerMessagePurpose $purpose Selected purpose.
     * @param array{purpose?: int, filters?: array<string, mixed>, preview?: list<string>, ignore_customer_consent?: bool, ignore_contact_use?: bool} $state Wizard state.
     * @param \App\BulkMessages\BulkRecipientFilterRegistry $registry Filter registry.
     * @return bool True when messages were saved.
     */
    private function saveBulkMessages(
        CustomerMessagePurpose $purpose,
        array $state,
        BulkRecipientFilterRegistry $registry,
    ): bool {
        $customers = (new BulkRecipients())->findCustomers(
            $purpose,
            $state['filters'] ?? [],
            $registry,
            $state['ignore_customer_consent'] ?? false,
            $state['ignore_contact_use'] ?? false,
        );

        $customerMessage = $this->CustomerMessages->newEmptyEntity();
        $customerMessage = $this->CustomerMessages->patchEntity($customerMessage, $this->request->getData());
        $customerMessage->direction = CustomerMessageDirection::Outgoing;
        $customerMessage->delivery_status = CustomerMessageDeliveryStatus::Pending;

        // keep the entity around for error display on re-render
        $this->set('customerMessage', $customerMessage);

        if ($customers === []) {
            $this->Flash->error(__('No customers were selected.'));

            return false;
        }

        if (!$customerMessage->type instanceof CustomerMessageType) {
            $this->Flash->error(__('Please select a valid message type.'));

            return false;
        }

        // the same for every customer, so it is said once beside the field
        if ($customerMessage->getError('body_format') !== []) {
            $this->Flash->error(__('The customer messages could not be saved. Please, try again.'));

            return false;
        }

        // per-contract opt-out from the preview: each checked row submits the
        // customer id into send_to[], so a customer is included as soon as at
        // least one of its rows stayed checked (duplicate ids are harmless).
        $sendTo = $this->request->getData('send_to');
        $sendTo = is_array($sendTo) ? array_values(array_map('strval', $sendTo)) : [];

        // the recipients are resolved again here, so the set can differ from the
        // one the operator approved (a contract terminated, a billing ended in
        // between). Narrow to what the preview actually offered and report the
        // difference rather than letting it change the send silently.
        $previewed = array_map('strval', $state['preview'] ?? []);
        if ($previewed !== []) {
            $sendTo = array_values(array_intersect($sendTo, $previewed));
        }
        $dropped = (new BulkRecipients())->findDropped($sendTo, $customers);

        $customerMessages = [];
        // selected customers with no eligible contact for this channel, kept for
        // the post-send summary (e.g. an SMS send lists everyone with no phone)
        $skipped = [];
        // customers actually messaged, so the summary flags what really went out
        // rather than what the preview offered
        $messaged = [];
        foreach ($customers as $customer) {
            // skip customers the operator deselected entirely
            if (!in_array((string)$customer->id, $sendTo, true)) {
                continue;
            }

            // channel decides which contacts the message goes to
            $recipients = $customerMessage->type === CustomerMessageType::Sms
                ? $customer->phones
                : $customer->emails;

            if ($recipients === []) {
                $skipped[] = [
                    'id' => (string)$customer->id,
                    'number' => $customer->number,
                    'name' => $customer->name,
                ];

                continue;
            }

            $thisMessage = clone $customerMessage;
            $thisMessage->customer_id = $customer->id;
            $thisMessage->recipients = $recipients;

            $customerMessages[] = $thisMessage;
            $messaged[] = $customer;
            unset($thisMessage);
        }

        if ($customerMessages === []) {
            $this->Flash->error(__('No recipients with a valid contact were found.'));

            return false;
        }

        $saved = (bool)$this->CustomerMessages->saveMany(
            $customerMessages,
            AuditTrail::batchOptions(),
        );

        if (!$saved) {
            $this->Flash->error(__('The customer messages could not be saved. Please, try again.'));
            // saveMany runs in a transaction, so nothing at all was written
            $this->reportBulkSaveFailure($customerMessages, $messaged, $customerMessage);

            return false;
        }

        $report = (new BulkSendSummary())->build(
            $purpose,
            $state,
            $registry,
            $customerMessage,
            $messaged,
            $customerMessages,
            $skipped,
            $dropped,
        );

        // the done step must not claim a summary was mailed when it was not
        $report['summary_mailed'] = $this->mailBulkSendReport($report);

        // stash a one-shot summary for the done step (post/redirect/get)
        $this->getRequest()->getSession()->write(self::BULK_RESULT_KEY, $report);

        return true;
    }

    /**
     * Mail the send report to the operator who sent it, plus the reporting
     * addresses the console commands already use.
     *
     * With no address to send to there is nothing to do but log it — the
     * messages themselves are already saved either way.
     *
     * @param SendSummary $report Report to send.
     * @return bool True when the summary was handed to the mailer.
     */
    private function mailBulkSendReport(array $report): bool
    {
        $summary = new BulkSendSummary();
        $addresses = $summary->recipients($this->getRequest()->getAttribute('identity')['email'] ?? null);

        if ($addresses === []) {
            Log::warning('Bulk customer message summary has no recipient address, not sending it.');
            $this->Flash->warning(__(
                'The messages were sent, but there is no address to e-mail the summary to.',
            ));

            return false;
        }

        try {
            $summary->mail($report, $addresses);
        } catch (Throwable $e) {
            // the messages themselves are already saved and will go out, so a
            // broken summary must not take the send down with it
            Log::error('Error sending the bulk customer message summary: ' . $e->getMessage());
            $this->Flash->error(__(
                'The messages were sent, but the summary could not be e-mailed to you: {0}',
                $e->getMessage(),
            ));

            return false;
        }

        return true;
    }

    /**
     * Explain a failed bulk save on the re-rendered compose step.
     *
     * The messages that failed validation are listed with their recipient, and
     * the first set of errors is copied onto the entity backing the form so the
     * offending field is highlighted too. A failure with no per-message errors
     * (a database or rule failure) still yields an empty list, which the view
     * turns into a "no details available" note.
     *
     * @param list<\App\Model\Entity\CustomerMessage> $customerMessages Messages that were attempted, in order.
     * @param list<\App\Model\Entity\Customer> $messaged Their recipients, in the same order.
     * @param \App\Model\Entity\CustomerMessage $customerMessage Entity backing the compose form.
     * @return void
     */
    private function reportBulkSaveFailure(
        array $customerMessages,
        array $messaged,
        CustomerMessage $customerMessage,
    ): void {
        $failures = [];
        foreach ($customerMessages as $index => $message) {
            $errors = $message->getErrors();
            if ($errors === []) {
                continue;
            }

            if ($failures === []) {
                // every message carries the same operator input, so the first
                // set of errors is what the form needs to show
                $customerMessage->setErrors($errors);
            }

            $customer = $messaged[$index] ?? null;
            $failures[] = [
                'number' => $customer?->number,
                'name' => $customer?->name,
                'errors' => $this->formatValidationErrors($errors),
            ];
        }

        $this->set('saveFailures', $failures);
    }

    /**
     * Set view vars for the purpose-selection step.
     *
     * @return void
     */
    private function prepareBulkPurposeStep(): void
    {
        $purposes = [];
        foreach (CustomerMessagePurpose::cases() as $case) {
            $purposes[$case->value] = $case->label();
        }

        $this->set(compact('purposes'));
    }

    /**
     * Set view vars for the filter step.
     *
     * @param \App\Model\Enum\CustomerMessagePurpose $purpose Selected purpose.
     * @param \App\BulkMessages\BulkRecipientFilterRegistry $registry Filter registry.
     * @param array{purpose?: int, filters?: array<string, mixed>, preview?: list<string>, ignore_customer_consent?: bool, ignore_contact_use?: bool} $state Wizard state.
     * @return void
     */
    private function prepareBulkFilterStep(
        CustomerMessagePurpose $purpose,
        BulkRecipientFilterRegistry $registry,
        array $state,
    ): void {
        $filterControls = [];
        foreach ($registry->forPurpose($purpose) as $filter) {
            $value = $state['filters'][$filter->id()] ?? null;
            foreach ($filter->controls($value) as $control) {
                $filterControls[] = $control;
            }
            // surface an unavailable data source (e.g. NMS / address registry down)
            $warning = $filter->warning();
            if ($warning !== null) {
                $this->Flash->warning($warning);
            }
        }

        $this->set([
            'purpose' => $purpose,
            'filterControls' => $filterControls,
            'ignoreCustomerConsent' => $state['ignore_customer_consent'] ?? false,
            'ignoreContactUse' => $state['ignore_contact_use'] ?? false,
        ]);
    }

    /**
     * Set view vars for the compose/preview step.
     *
     * @param \App\Model\Enum\CustomerMessagePurpose $purpose Selected purpose.
     * @param \App\BulkMessages\BulkRecipientFilterRegistry $registry Filter registry.
     * @param array{purpose?: int, filters?: array<string, mixed>, preview?: list<string>, ignore_customer_consent?: bool, ignore_contact_use?: bool} $state Wizard state.
     * @return void
     */
    private function prepareBulkComposeStep(
        CustomerMessagePurpose $purpose,
        BulkRecipientFilterRegistry $registry,
        array $state,
    ): void {
        $ignoreCustomerConsent = $state['ignore_customer_consent'] ?? false;
        $ignoreContactUse = $state['ignore_contact_use'] ?? false;
        $customers = (new BulkRecipients())->findCustomers(
            $purpose,
            $state['filters'] ?? [],
            $registry,
            $ignoreCustomerConsent,
            $ignoreContactUse,
        );

        // preserve a patched entity across a failed submit; otherwise start
        // fresh and prefill the purpose's default template
        if (!$this->viewBuilder()->getVar('customerMessage') instanceof CustomerMessage) {
            $customerMessage = $this->CustomerMessages->newEmptyEntity();
            $this->applyPurposeComposeDefaults($customerMessage, $purpose);
            $this->set('customerMessage', $customerMessage);
        }

        // only a failed submit fills this in
        if (!is_array($this->viewBuilder()->getVar('saveFailures'))) {
            $this->set('saveFailures', null);
        }

        $answer = NMSApiClient::getAccessPointsList(onlyActive: false);
        if (!$answer->ok()) {
            // without them every group falls back to "unknown", which would go
            // unnoticed in both the preview and the summary e-mail
            $this->Flash->warning(__('The access points list could not be loaded. Please, try again.'));
        }
        $apNames = $answer->or([]);

        // contract-scoped filters (access point, contract state) have already
        // narrowed the contained contracts in findCustomers(), so grouping
        // only ever sees contracts the active filters allow
        $apGroups = (new BulkRecipients())->groupByAccessPoint($customers, $apNames);

        // remember what was offered, so the send can tell whether the recipient
        // set changed underneath the operator
        $state['preview'] = array_map(static fn($customer): string => (string)$customer->id, $customers);
        $this->getRequest()->getSession()->write(self::BULK_WIZARD_STATE_KEY, $state);

        $this->set([
            'purpose' => $purpose,
            'customers' => $customers,
            'apGroups' => $apGroups,
            'flagged' => (new BulkRecipients())->countFlagged($customers),
            'ignoreCustomerConsent' => $ignoreCustomerConsent,
            'ignoreContactUse' => $ignoreContactUse,
        ]);
    }

    /**
     * Prefill a fresh compose entity with the purpose's default subject/body.
     *
     * Defaults come from the Settings plugin (DB overlay > config/settings.php),
     * so operators can customise the template without touching code.
     *
     * @param \App\Model\Entity\CustomerMessage $customerMessage Fresh message entity.
     * @param \App\Model\Enum\CustomerMessagePurpose $purpose Selected purpose.
     * @return void
     */
    private function applyPurposeComposeDefaults(
        CustomerMessage $customerMessage,
        CustomerMessagePurpose $purpose,
    ): void {
        $path = 'core.customer_messages.' . $purpose->settingsKey();

        $customerMessage->type = $purpose->defaultType();
        $customerMessage->subject = Settings::getString($path . '.subject');
        $customerMessage->body = Settings::getString($path . '.body_text');
        $customerMessage->body_format = CustomerMessageBodyFormat::Plaintext;
    }

    /**
     * Edit method
     *
     * @param string|null $id Customer Message id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null): ?Response
    {
        $customerMessage = $this->CustomerMessages->get($id, contain: []);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $customerMessage = $this->CustomerMessages->patchEntity($customerMessage, $this->request->getData());
            if ($this->CustomerMessages->save($customerMessage)) {
                $this->Flash->success(__('The customer message has been saved.'));

                return $this->afterEditRedirect(['action' => 'view', $customerMessage->id]);
            }
            $this->Flash->error(__('The customer message could not be saved. Please, try again.'));
        }
        $customers = $this->CustomerMessages->Customers->find('list', order: [
            'company',
            'last_name',
            'first_name',
        ])->all();
        $this->set(compact('customerMessage', 'customers'));

        return null;
    }

    /**
     * Delete method
     *
     * @param string|null $id Customer Message id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $customerMessage = $this->CustomerMessages->get($id);
        if ($this->CustomerMessages->delete($customerMessage)) {
            $this->Flash->success(__('The customer message has been deleted.'));
        } else {
            $this->flashValidationErrors($customerMessage->getErrors());
            $this->Flash->error(__('The customer message could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
