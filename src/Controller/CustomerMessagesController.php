<?php
declare(strict_types=1);

namespace App\Controller;

use App\BulkMessages\BulkRecipientFilterRegistry;
use App\BulkMessages\Filter\ContractScopedFilterInterface;
use App\BulkMessages\Filter\CustomerScopedFilterInterface;
use App\Controller\Traits\CommonViewVarListsTrait;
use App\Model\Entity\Contract;
use App\Model\Entity\Customer;
use App\Model\Entity\CustomerMessage;
use App\Model\Enum\CustomerMessageBodyFormat;
use App\Model\Enum\CustomerMessageDeliveryStatus;
use App\Model\Enum\CustomerMessageDirection;
use App\Model\Enum\CustomerMessagePurpose;
use App\Model\Enum\CustomerMessageType;
use App\Model\Enum\ServiceCriticalityLevel;
use App\NMS\ApiClient as NMSApiClient;
use App\Service\OperatorReport;
use Cake\Http\Response;
use Cake\Http\Session;
use Cake\Log\Log;
use Cake\Mailer\Mailer;
use Cake\ORM\Query\SelectQuery;
use Cake\Utility\Text;
use Settings\Utility\Settings;
use SplObjectStorage;
use Throwable;

/**
 * CustomerMessages Controller
 *
 * @property \App\Model\Table\CustomerMessagesTable $CustomerMessages
 * @phpstan-type BulkRecipientRow array{
 *     customer: \App\Model\Entity\Customer,
 *     contract: \App\Model\Entity\Contract|null,
 *     services: list<string>,
 *     vip: bool,
 *     criticality: \App\Model\Enum\ServiceCriticalityLevel|null
 * }
 * @phpstan-type BulkSendReport array{
 *     sent: int,
 *     channel: string,
 *     is_sms: bool,
 *     purpose: string,
 *     subject: string,
 *     body: string,
 *     filters: list<string>,
 *     ignored_customer_consent: bool,
 *     ignored_contact_use: bool,
 *     groups: list<array{
 *         ap_id: string|null,
 *         ap_name: string,
 *         customers: list<array{
 *             number: string|null,
 *             name: string,
 *             contract_number: string|null,
 *             services: list<string>,
 *             vip: bool,
 *             criticality: string|null,
 *             recipients: list<string>
 *         }>
 *     }>,
 *     skipped: list<array{id: string, number: string|null, name: string}>,
 *     dropped: list<array{number: string|null, name: string}>,
 *     flagged: array{vip: int, critical: int},
 *     summary_mailed: bool
 * }
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
            $customerMessage = $this->CustomerMessages->patchEntity($customerMessage, $this->request->getData());
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
        $customers = $this->findBulkCustomers(
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
        $dropped = $this->findDroppedRecipients($sendTo, $customers);

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
            [
                // saveMany audit options kept intentionally:
                // - mapiiik/audit-log (5.x, 6.x) logs nothing without them
                // - even audit-stash 2.0.1+ groups the batch under one transaction id only
                //   when they're passed (otherwise each record gets its own)
                '_auditQueue' => new SplObjectStorage(),
                '_auditTransaction' => Text::uuid(),
            ],
        );

        if (!$saved) {
            $this->Flash->error(__('The customer messages could not be saved. Please, try again.'));
            // saveMany runs in a transaction, so nothing at all was written
            $this->reportBulkSaveFailure($customerMessages, $messaged, $customerMessage);

            return false;
        }

        $report = $this->buildBulkSendReport(
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
     * Selected recipients that the send-time query no longer returns.
     *
     * @param list<string> $sendTo Customer ids the operator kept checked.
     * @param array<\App\Model\Entity\Customer> $customers Recipients resolved at send time.
     * @return list<array{number: string|null, name: string}>
     */
    private function findDroppedRecipients(array $sendTo, array $customers): array
    {
        $resolved = [];
        foreach ($customers as $customer) {
            $resolved[(string)$customer->id] = true;
        }

        $droppedIds = array_values(array_diff(array_unique($sendTo), array_keys($resolved)));
        if ($droppedIds === []) {
            return [];
        }

        // they are gone from the recipient query, so their names have to come
        // from the customers table directly
        $dropped = [];
        foreach (
            $this->CustomerMessages->Customers
                ->find()
                ->where(['Customers.id IN' => $droppedIds])
                ->all() as $customer
        ) {
            $dropped[] = [
                'number' => $customer->number,
                'name' => $customer->name,
            ];
        }

        return $dropped;
    }

    /**
     * Assemble the record of what a bulk send did: the message itself, the
     * filters that picked the recipients, and every recipient grouped by access
     * point with their services, flags and the addresses the message went to.
     *
     * The same structure feeds the done step and the summary e-mail, so both
     * always tell the same story.
     *
     * @param \App\Model\Enum\CustomerMessagePurpose $purpose Selected purpose.
     * @param array{purpose?: int, filters?: array<string, mixed>, preview?: list<string>, ignore_customer_consent?: bool, ignore_contact_use?: bool} $state Wizard state.
     * @param \App\BulkMessages\BulkRecipientFilterRegistry $registry Filter registry.
     * @param \App\Model\Entity\CustomerMessage $customerMessage Composed message.
     * @param list<\App\Model\Entity\Customer> $messaged Customers a message was built for.
     * @param list<\App\Model\Entity\CustomerMessage> $customerMessages Their messages, in the same order.
     * @param list<array{id: string, number: string|null, name: string}> $skipped Selected but without a usable contact.
     * @param list<array{number: string|null, name: string}> $dropped Selected but no longer eligible.
     * @return BulkSendReport
     */
    private function buildBulkSendReport(
        CustomerMessagePurpose $purpose,
        array $state,
        BulkRecipientFilterRegistry $registry,
        CustomerMessage $customerMessage,
        array $messaged,
        array $customerMessages,
        array $skipped,
        array $dropped,
    ): array {
        // the entity normalises contacts into plain addresses, which is exactly
        // what the report should show
        $recipientsByCustomer = [];
        foreach ($customerMessages as $index => $message) {
            $customer = $messaged[$index] ?? null;
            if ($customer !== null) {
                $recipientsByCustomer[(string)$customer->id] = array_values(
                    array_map('strval', $message->recipients),
                );
            }
        }

        $apNames = NMSApiClient::getAccessPointsList(onlyActive: false)->or([]);
        $groups = [];
        foreach ($this->groupCustomersByAccessPoint($messaged, $apNames) as $group) {
            $customers = [];
            foreach ($group['rows'] as $row) {
                $customers[] = [
                    'number' => $row['customer']->number,
                    'name' => $row['customer']->name,
                    'contract_number' => $row['contract']?->number,
                    'services' => $row['services'],
                    'vip' => $row['vip'],
                    'criticality' => $row['criticality']?->label(),
                    'recipients' => $recipientsByCustomer[(string)$row['customer']->id] ?? [],
                ];
            }

            $groups[] = [
                'ap_id' => $group['ap_id'],
                'ap_name' => $group['ap_name'],
                'customers' => $customers,
            ];
        }

        return [
            'sent' => count($customerMessages),
            'channel' => $customerMessage->type->label(),
            'is_sms' => $customerMessage->type === CustomerMessageType::Sms,
            'purpose' => $purpose->label(),
            'subject' => (string)$customerMessage->subject,
            'body' => (string)$customerMessage->body,
            'filters' => $registry->describeFilters($purpose, $state['filters'] ?? []),
            'ignored_customer_consent' => ($state['ignore_customer_consent'] ?? false) === true,
            'ignored_contact_use' => ($state['ignore_contact_use'] ?? false) === true,
            'groups' => $groups,
            'skipped' => $skipped,
            'dropped' => $dropped,
            'flagged' => $this->countFlaggedCustomers($messaged),
            // the caller flips this once the summary has actually gone out
            'summary_mailed' => false,
        ];
    }

    /**
     * Names of the services a contract currently bills.
     *
     * @param \App\Model\Entity\Contract|null $contract Contract to inspect.
     * @return list<string>
     */
    private function contractServiceNames(?Contract $contract): array
    {
        if ($contract === null) {
            return [];
        }

        $names = [];
        foreach ($contract->billings as $billing) {
            $name = $billing->service->name ?? null;
            if (is_string($name) && $name !== '' && !in_array($name, $names, true)) {
                $names[] = $name;
            }
        }

        return $names;
    }

    /**
     * Mail the send report to the operator who sent it, plus the reporting
     * addresses the console commands already use.
     *
     * With no address to send to there is nothing to do but log it — the
     * messages themselves are already saved either way.
     *
     * @param BulkSendReport $report Report to send.
     * @return bool True when the summary was handed to the mailer.
     */
    private function mailBulkSendReport(array $report): bool
    {
        $identity = $this->getRequest()->getAttribute('identity');
        $addresses = [];

        $operator = $identity['email'] ?? null;
        if (is_string($operator) && $operator !== '') {
            $addresses[] = $operator;
        }

        foreach (OperatorReport::recipients() as $address) {
            if (!in_array($address, $addresses, true)) {
                $addresses[] = $address;
            }
        }

        if ($addresses === []) {
            Log::warning('Bulk customer message summary has no recipient address, not sending it.');
            $this->Flash->warning(__(
                'The messages were sent, but there is no address to e-mail the summary to.',
            ));

            return false;
        }

        $mailer = new Mailer('default');
        foreach ($addresses as $address) {
            $mailer->addTo($address);
        }

        $mailer->setSubject(__(
            'Bulk customer message: {0} — {1} message(s) sent',
            $report['purpose'],
            $report['sent'],
        ));

        try {
            $mailer->deliver($this->renderBulkSendReport($report));
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
     * Render the send report as the plain-text body of the summary e-mail.
     *
     * @param BulkSendReport $report Report to render.
     * @return string
     */
    private function renderBulkSendReport(array $report): string
    {
        $lines = [
            __('Purpose: {0}', $report['purpose']),
            __('Channel: {0}', $report['channel']),
            __('{0} message(s) queued for sending.', $report['sent']),
            '',
            __('Recipient filters:'),
        ];

        $lines = array_merge(
            $lines,
            $report['filters'] === []
                ? ['- ' . __('No filter was applied.')]
                : array_map(static fn(string $filter): string => '- ' . $filter, $report['filters']),
        );

        if ($report['ignored_customer_consent']) {
            $lines[] = '- ' . __('Customer mailing consent was ignored.');
        }
        if ($report['ignored_contact_use']) {
            $lines[] = '- ' . __('Per-contact routing flag was ignored.');
        }

        $lines[] = '';
        $lines[] = __('Recipients:');
        foreach ($report['groups'] as $group) {
            $lines[] = '';
            $lines[] = $group['ap_name'] . ' (' . count($group['customers']) . ')';
            foreach ($group['customers'] as $customer) {
                $flags = [];
                if ($customer['vip']) {
                    $flags[] = __('VIP');
                }
                if ($customer['criticality'] !== null) {
                    $flags[] = $customer['criticality'];
                }

                $lines[] = '  ' . implode(' | ', array_filter([
                    trim($customer['number'] . ' ' . $customer['name']),
                    $customer['contract_number'],
                    implode(', ', $customer['services']),
                    implode(', ', $customer['recipients']),
                    $flags === [] ? null : '!! ' . implode(', ', $flags),
                ]));
            }
        }

        foreach (
            [
                __('Not sent — no usable contact:') => $report['skipped'],
                __('Not sent — no longer eligible when sending:') => $report['dropped'],
            ] as $heading => $customers
        ) {
            if ($customers === []) {
                continue;
            }

            $lines[] = '';
            $lines[] = $heading;
            foreach ($customers as $customer) {
                $lines[] = '  ' . trim($customer['number'] . ' ' . $customer['name']);
            }
        }

        $lines[] = '';
        $lines[] = str_repeat('-', 60);
        $lines[] = __('Subject: {0}', $report['subject']);
        $lines[] = '';
        $lines[] = $report['body'];

        return implode(PHP_EOL, $lines);
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
     * Resolve the eligible recipient customers for the given purpose/filters.
     *
     * Contained emails/phones are restricted to those flagged for the purpose,
     * and customers to those who gave mailing consent. The two overrides bypass
     * these independently (customer mailing consent vs. per-contact routing).
     * With no filter selected the message targets everyone who consented (or the
     * whole customer base when consent is overridden too).
     *
     * @param \App\Model\Enum\CustomerMessagePurpose $purpose Selected purpose.
     * @param array<string, mixed> $filters Submitted filter values keyed by filter key.
     * @param \App\BulkMessages\BulkRecipientFilterRegistry $registry Filter registry.
     * @param bool $ignoreCustomerConsent Whether to bypass the customer mailing consent (agree_mailing_*).
     * @param bool $ignoreContactUse Whether to bypass the per-contact routing flag (use_for_*).
     * @return array<\App\Model\Entity\Customer>
     */
    private function findBulkCustomers(
        CustomerMessagePurpose $purpose,
        array $filters,
        BulkRecipientFilterRegistry $registry,
        bool $ignoreCustomerConsent,
        bool $ignoreContactUse,
    ): array {
        $conditions = [];
        // conditions applied to the *contained* Contracts so the preview's
        // access-point grouping hides contracts a filter excludes (e.g. a
        // non-active / non-billed contract state) instead of surfacing them
        $containedContractConditions = [];
        foreach ($filters as $key => $value) {
            $filter = $registry->get($key);
            if ($filter === null) {
                continue;
            }
            if ($filter instanceof ContractScopedFilterInterface) {
                // contract-scoped filters are correlated on a single contract
                // below (not added as independent per-filter customer conditions)
                $contained = $filter->containedContractConditions($value);
                if ($contained !== null) {
                    $containedContractConditions[] = $contained;
                }
            } elseif ($filter instanceof CustomerScopedFilterInterface) {
                $filterConditions = $filter->conditions($value);
                if ($filterConditions !== null) {
                    $conditions[] = $filterConditions;
                }
            }
        }

        // contract-scoped filters must be satisfied by the *same* contract: a
        // customer qualifies only when one contract matches all of them together
        // (e.g. an active contract *on* the selected access point), never when
        // different contracts each satisfy a different filter
        if ($containedContractConditions !== []) {
            $conditions[] = [
                'Customers.id IN' => $this->CustomerMessages->Customers->Contracts
                    ->find()
                    ->select(['customer_id'])
                    ->distinct()
                    ->where($containedContractConditions),
            ];
        }

        // mailing consent (unless overridden). With no filter selected this is
        // the only constraint; with consent also overridden the message targets
        // the entire customer base — intentional (e.g. a marketing campaign).
        if (!$ignoreCustomerConsent) {
            $conditions[] = ['Customers.' . $purpose->customerConsentField() => true];
        }

        $useField = $purpose->contactUseField();
        $contactFinder = function (SelectQuery $query, string $alias) use ($ignoreContactUse, $useField): SelectQuery {
            if ($ignoreContactUse) {
                return $query;
            }

            return $query->where([$alias . '.' . $useField => true]);
        };

        return $this->CustomerMessages->Customers->find()
            ->contain([
                'Emails' => fn(SelectQuery $q): SelectQuery => $contactFinder($q, 'Emails'),
                'Phones' => fn(SelectQuery $q): SelectQuery => $contactFinder($q, 'Phones'),
                // contracts drive the access-point grouping in the preview;
                // any contract-scoped filter (e.g. contract state) narrows them
                // so excluded contracts never surface a customer under an AP
                'Contracts' => function (SelectQuery $q) use ($containedContractConditions): SelectQuery {
                    $q = $q
                        ->select([
                            'Contracts.id',
                            'Contracts.customer_id',
                            'Contracts.access_point_id',
                            // shown (and linked) in the preview so the operator
                            // can tell a customer's contracts apart
                            'Contracts.number',
                            // guaranteed / VIP flag, warned about before sending
                            'Contracts.vip',
                        ])
                        // the billed services carry the second flag (their
                        // criticality level); only non-historical billings say
                        // anything about what the customer has today
                        ->contain([
                            'Billings' => [
                                'finder' => 'activeOrFuture',
                                'fields' => [
                                    'Billings.id',
                                    'Billings.contract_id',
                                    'Billings.service_id',
                                ],
                                'Services' => [
                                    'fields' => [
                                        'Services.id',
                                        'Services.name',
                                        'Services.criticality_level',
                                    ],
                                ],
                            ],
                        ]);

                    return $containedContractConditions === []
                        ? $q
                        : $q->where($containedContractConditions);
                },
            ])
            ->where($conditions)
            ->orderBy([
                'Customers.company',
                'Customers.last_name',
                'Customers.first_name',
            ])
            ->toArray();
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
        $customers = $this->findBulkCustomers(
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
        // narrowed the contained contracts in findBulkCustomers(), so grouping
        // only ever sees contracts the active filters allow
        $apGroups = $this->groupCustomersByAccessPoint($customers, $apNames);

        // remember what was offered, so the send can tell whether the recipient
        // set changed underneath the operator
        $state['preview'] = array_map(static fn($customer): string => (string)$customer->id, $customers);
        $this->getRequest()->getSession()->write(self::BULK_WIZARD_STATE_KEY, $state);

        $this->set([
            'purpose' => $purpose,
            'customers' => $customers,
            'apGroups' => $apGroups,
            'flagged' => $this->countFlaggedCustomers($customers),
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
     * Group recipients by the access point(s) of their contracts, one row per
     * contract — a customer with contracts on several access points is listed
     * under each (so it is visible everywhere it belongs). The opt-out checkbox
     * of every row submits the customer id, so unchecking one row still sends as
     * long as another stays checked; the send step then builds a single message
     * per customer. Contracts without an access point, and customers without any
     * contract at all, form a trailing group.
     *
     * @param array<\App\Model\Entity\Customer> $customers Deduplicated recipients.
     * @param array<array-key, string> $apNames Access point id => name map.
     * @return list<array{ap_id: string|null, ap_name: string, rows: list<BulkRecipientRow>}>
     */
    private function groupCustomersByAccessPoint(array $customers, array $apNames): array
    {
        /** @var array<string, list<BulkRecipientRow>> $byAccessPoint */
        $byAccessPoint = [];
        /** @var list<BulkRecipientRow> $withoutAccessPoint */
        $withoutAccessPoint = [];

        foreach ($customers as $customer) {
            if ($customer->contracts === []) {
                $withoutAccessPoint[] = $this->buildRecipientRow($customer, null);

                continue;
            }

            foreach ($customer->contracts as $contract) {
                $apId = $contract->access_point_id;
                $row = $this->buildRecipientRow($customer, $contract);
                if (is_string($apId) && $apId !== '') {
                    $byAccessPoint[$apId][] = $row;
                } else {
                    $withoutAccessPoint[] = $row;
                }
            }
        }

        $groups = [];
        foreach ($byAccessPoint as $apId => $rows) {
            $groups[] = [
                'ap_id' => $apId,
                'ap_name' => $apNames[$apId] ?? __('Unknown access point'),
                'rows' => $rows,
            ];
        }
        usort($groups, static fn(array $a, array $b): int => strnatcasecmp($a['ap_name'], $b['ap_name']));

        if ($withoutAccessPoint !== []) {
            $groups[] = [
                'ap_id' => null,
                'ap_name' => __('No access point'),
                'rows' => $withoutAccessPoint,
            ];
        }

        return $groups;
    }

    /**
     * Build one preview row: the customer as seen through one of its contracts,
     * carrying the two flags the operator must notice before sending.
     *
     * @param \App\Model\Entity\Customer $customer Recipient customer.
     * @param \App\Model\Entity\Contract|null $contract Contract the row stands for, if any.
     * @return BulkRecipientRow
     */
    private function buildRecipientRow(Customer $customer, ?Contract $contract): array
    {
        return [
            'customer' => $customer,
            'contract' => $contract,
            'services' => $this->contractServiceNames($contract),
            'vip' => $contract?->vip === true,
            'criticality' => $this->highestServiceCriticality($contract),
        ];
    }

    /**
     * Highest above-normal criticality level among the services the contract
     * currently bills, or null when it bills nothing noteworthy.
     *
     * @param \App\Model\Entity\Contract|null $contract Contract to inspect.
     * @return \App\Model\Enum\ServiceCriticalityLevel|null
     */
    private function highestServiceCriticality(?Contract $contract): ?ServiceCriticalityLevel
    {
        if ($contract === null) {
            return null;
        }

        $highest = null;
        foreach ($contract->billings as $billing) {
            $level = $billing->service->criticality_level ?? null;
            if (!$level instanceof ServiceCriticalityLevel || $level === ServiceCriticalityLevel::Normal) {
                continue;
            }
            if ($highest === null || $level->value > $highest->value) {
                $highest = $level;
            }
        }

        return $highest;
    }

    /**
     * How many of the given customers carry each flag — counted per customer, so
     * someone with two VIP contracts is one person to double-check, not two.
     *
     * @param array<\App\Model\Entity\Customer> $customers Recipients to inspect.
     * @return array{vip: int, critical: int}
     */
    private function countFlaggedCustomers(array $customers): array
    {
        $vip = 0;
        $critical = 0;
        foreach ($customers as $customer) {
            $isVip = false;
            $isCritical = false;
            foreach ($customer->contracts as $contract) {
                $isVip = $isVip || $contract->vip === true;
                $isCritical = $isCritical || $this->highestServiceCriticality($contract) !== null;
            }

            $vip += (int)$isVip;
            $critical += (int)$isCritical;
        }

        return [
            'vip' => $vip,
            'critical' => $critical,
        ];
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
