<?php
declare(strict_types=1);

namespace App\Controller;

use App\Addresses\Resolver as AddressesResolver;
use App\Bulk\BulkRecipientFilterRegistry;
use App\Bulk\Filter\AccessPointFilter;
use App\Controller\Traits\CommonViewVarListsTrait;
use App\Model\Entity\CustomerMessage;
use App\Model\Enum\CustomerMessageDeliveryStatus;
use App\Model\Enum\CustomerMessageDirection;
use App\Model\Enum\CustomerMessagePurpose;
use App\Model\Enum\CustomerMessageType;
use App\Model\Table\LabelsTable;
use App\NMS\ApiClient as NMSApiClient;
use Cake\Http\Response;
use Cake\Http\Session;
use Cake\ORM\Query\SelectQuery;
use Cake\Utility\Text;
use Cake\Validation\Validation;
use RuntimeException;
use SplObjectStorage;

/**
 * CustomerMessages Controller
 *
 * @property \App\Model\Table\CustomerMessagesTable $CustomerMessages
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
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function addBulk(): ?Response
    {
        // load labels
        $labelsTable = $this->fetchTable(LabelsTable::class);
        $labels = $labelsTable->find('list', order: [
            'name',
        ])->all();

        // Load addresses from national address registry for existing installation addresses
        /** @var \Cake\Datasource\ResultSetInterface<int, \App\Model\Entity\Address> $installationAddresses */
        $installationAddresses = $this->CustomerMessages->Customers->Contracts->InstallationAddresses
            ->find()
            ->where([
                'address_registry_source IS NOT' => null,
                'address_registry_reference IS NOT' => null,
            ])
            ->all();

        $registryAddresses = [];
        try {
            $registryAddresses = AddressesResolver::dropdownMap($installationAddresses);
        } catch (RuntimeException $e) {
            $this->Flash->warning(__(
                'Could not retrieve addresses from national address registry: {0}',
                $e->getMessage(),
            ));
        }

        // customers filter
        $customersFilter = [];

        $labelId = $this->getRequest()->getQuery('label_id');
        if (is_string($labelId) && Validation::uuid($labelId)) {
            $filterQuery = $labelsTable->CustomerLabels->find()
                ->select([
                    'customer_id',
                ])
                ->distinct()
                ->where([
                    'CustomerLabels.label_id IS' => $labelId,
                ]);

            $customersFilter[] = [
                'Customers.id IN' => $filterQuery,
            ];
            unset($filterQuery);
        }

        $accessPointId = $this->getRequest()->getQuery('access_point_id');
        if (is_string($accessPointId) && Validation::uuid($accessPointId)) {
            $filterQuery = $this->CustomerMessages->Customers->Contracts->find()
                ->select([
                    'customer_id',
                ])
                ->distinct()
                ->where([
                    'Contracts.access_point_id IS' => $accessPointId,
                ]);

            $customersFilter[] = [
                'Customers.id IN' => $filterQuery,
            ];
            unset($filterQuery);
        }

        $registryAddressId = $this->getRequest()->getQuery('registry_address_id');
        if (is_string($registryAddressId)) {
            // expect format "source|reference", e.g. "cz|12345678"
            [
                $address_registry_source,
                $address_registry_reference,
            ] = explode('|', $registryAddressId, limit: 2) + [null, null];

            $filterQuery = $this->CustomerMessages->Customers->Contracts->find()
                ->select([
                    'customer_id',
                ])
                ->contain([
                    'InstallationAddresses',
                ])
                ->distinct()
                ->where([
                    'InstallationAddresses.address_registry_reference IS' => $address_registry_reference,
                    'InstallationAddresses.address_registry_source IS' => $address_registry_source,
                ]);

            $customersFilter[] = [
                'Customers.id IN' => $filterQuery,
            ];
            unset($filterQuery);
        }

        if ($customersFilter !== []) {
            $customers = $this->CustomerMessages->Customers->find()
            ->contain([
                'Emails',
                'Phones',
            ])
            ->where($customersFilter)
            ->orderBy([
                'Customers.company',
                'Customers.last_name',
                'Customers.first_name',
            ]);
        } else {
            $customers = [];
        }
        /** @var iterable<\App\Model\Entity\Customer> $customers */

        $customerMessage = $this->CustomerMessages->newEmptyEntity();
        if ($this->request->is('post')) {
            if (empty($customers)) {
                $this->Flash->error(__('No customers were selected.'));
            } else {
                $customerMessage = $this->CustomerMessages->patchEntity($customerMessage, $this->request->getData());

                $customerMessage->direction = CustomerMessageDirection::Outgoing;
                $customerMessage->delivery_status = CustomerMessageDeliveryStatus::Pending;

                $customerMessages = [];
                foreach ($customers as $customer) {
                    $thisMessage = clone $customerMessage;
                    $thisMessage->customer_id = $customer->id;
                    $thisMessage->recipients = match ($thisMessage->type) {
                        CustomerMessageType::Sms => $customer->phones,
                        CustomerMessageType::Email,
                        CustomerMessageType::EmailContracts,
                        CustomerMessageType::EmailInvoices,
                        CustomerMessageType::EmailSupport => $customer->emails,
                    };

                    // skip messages without recipients
                    if (empty($thisMessage->recipients)) {
                        $this->Flash->warning(__('No contact was found for customer number {number}.', [
                            'number' => $customer->number,
                        ]));

                        continue;
                    }

                    $customerMessages[] = $thisMessage;
                    unset($thisMessage);
                }

                if (
                    $this->CustomerMessages->saveMany(
                        $customerMessages,
                        [
                            // saveMany audit options kept intentionally:
                            // - mapiiik/audit-log (5.x, 6.x) logs nothing without them
                            // - even audit-stash 2.0.1+ groups the batch under one transaction id only
                            //   when they're passed (otherwise each record gets its own)
                            '_auditQueue' => new SplObjectStorage(),
                            '_auditTransaction' => Text::uuid(),
                        ],
                    )
                ) {
                    $this->Flash->success(__('The bulk customer message has been saved.'));

                    return $this->afterAddRedirect(['action' => 'index']);
                }
                $this->Flash->error(__('The bulk customer message could not be saved. Please, try again.'));
            }
        }
        $this->set(compact(
            'customerMessage',
            'labels',
            'registryAddresses',
            'customers',
        ));

        // load access points from NMS if possible (only active)
        $this->setAccessPointsViewVarList(onlyActive: true);

        return null;
    }

    /**
     * Add Bulk method (wizard)
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
    public function addBulkNew(): ?Response
    {
        $session = $this->getRequest()->getSession();

        // allow starting over from any step
        if ($this->getRequest()->getQuery('reset') !== null) {
            $session->delete(self::BULK_WIZARD_STATE_KEY);

            return $this->redirect(['action' => 'addBulkNew']);
        }

        /** @var array{purpose?: int, filters?: array<string, mixed>, ignore_customer_consent?: bool, ignore_contact_use?: bool} $state */
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
                return $this->redirect(['action' => 'addBulkNew']);
            }
            $this->set('result', $result);
            $this->viewBuilder()->setTemplate('add_bulk_new/step_done');

            return null;
        }

        if ($purpose !== null && $step === 'filters') {
            $this->prepareBulkFilterStep($purpose, $registry, $state);
            $this->viewBuilder()->setTemplate('add_bulk_new/step_filters');
        } elseif ($purpose !== null && $step === 'compose') {
            $this->prepareBulkComposeStep($purpose, $registry, $state);
            $this->viewBuilder()->setTemplate('add_bulk_new/step_compose');
        } else {
            $this->prepareBulkPurposeStep();
            $this->viewBuilder()->setTemplate('add_bulk_new/step_purpose');
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
     * @param array{purpose?: int, filters?: array<string, mixed>, ignore_customer_consent?: bool, ignore_contact_use?: bool} $state Current state.
     * @param \App\Model\Enum\CustomerMessagePurpose|null $purpose Selected purpose (from state).
     * @param \App\Bulk\BulkRecipientFilterRegistry $registry Filter registry.
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

                return $this->redirect(['action' => 'addBulkNew']);
            }
            // changing the purpose resets any downstream selections
            $session->write(self::BULK_WIZARD_STATE_KEY, ['purpose' => $selected->value]);

            return $this->redirect(['action' => 'addBulkNew', '?' => ['step' => 'filters']]);
        }

        if ($purpose === null) {
            return $this->redirect(['action' => 'addBulkNew']);
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

            return $this->redirect(['action' => 'addBulkNew', '?' => ['step' => 'compose']]);
        }

        if ($step === 'compose') {
            if ($this->saveBulkMessages($purpose, $state, $registry)) {
                $session->delete(self::BULK_WIZARD_STATE_KEY);

                // the summary is shown on the done step (read from the session)
                return $this->redirect(['action' => 'addBulkNew', '?' => ['step' => 'done']]);
            }

            // fall through: caller re-renders the compose step with errors
            return null;
        }

        return $this->redirect(['action' => 'addBulkNew']);
    }

    /**
     * Build and persist one message per eligible recipient customer.
     *
     * The patched (and possibly invalid) message entity is exposed to the view
     * as `customerMessage` so validation errors are shown on re-render.
     *
     * @param \App\Model\Enum\CustomerMessagePurpose $purpose Selected purpose.
     * @param array{purpose?: int, filters?: array<string, mixed>, ignore_customer_consent?: bool, ignore_contact_use?: bool} $state Wizard state.
     * @param \App\Bulk\BulkRecipientFilterRegistry $registry Filter registry.
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
        $sendTo = is_array($sendTo) ? array_map('strval', $sendTo) : [];

        $customerMessages = [];
        // selected customers with no eligible contact for this channel, kept for
        // the post-send summary (e.g. an SMS send lists everyone with no phone)
        $skipped = [];
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
            return false;
        }

        // stash a one-shot summary for the done step (post/redirect/get)
        $this->getRequest()->getSession()->write(self::BULK_RESULT_KEY, [
            'sent' => count($customerMessages),
            'channel' => $customerMessage->type->label(),
            'is_sms' => $customerMessage->type === CustomerMessageType::Sms,
            'skipped' => $skipped,
        ]);

        return true;
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
     * @param \App\Bulk\BulkRecipientFilterRegistry $registry Filter registry.
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
        foreach ($filters as $key => $value) {
            $filter = $registry->get($key);
            if ($filter === null) {
                continue;
            }
            $filterConditions = $filter->conditions($value);
            if ($filterConditions !== null) {
                $conditions[] = $filterConditions;
            }
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
                // contracts drive the access-point grouping in the preview
                'Contracts' => fn(SelectQuery $q): SelectQuery => $q->select([
                    'Contracts.id',
                    'Contracts.customer_id',
                    'Contracts.access_point_id',
                ]),
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
     * @param \App\Bulk\BulkRecipientFilterRegistry $registry Filter registry.
     * @param array{purpose?: int, filters?: array<string, mixed>, ignore_customer_consent?: bool, ignore_contact_use?: bool} $state Wizard state.
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
     * @param \App\Bulk\BulkRecipientFilterRegistry $registry Filter registry.
     * @param array{purpose?: int, filters?: array<string, mixed>, ignore_customer_consent?: bool, ignore_contact_use?: bool} $state Wizard state.
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

        // preserve a patched entity across a failed submit; otherwise start fresh
        if (!$this->viewBuilder()->getVar('customerMessage') instanceof CustomerMessage) {
            $this->set('customerMessage', $this->CustomerMessages->newEmptyEntity());
        }

        $apNames = NMSApiClient::getAccessPointsList(onlyActive: false) ?? [];

        // when the access point filter is active, restrict the preview grouping
        // to the access points it allows (selection + cascade subtree), so a
        // customer's contracts on other access points don't surface groups the
        // filter would exclude
        $allowedApIds = null;
        $apFilter = $registry->get('access_point');
        if (isset($state['filters']['access_point']) && $apFilter instanceof AccessPointFilter) {
            $matched = $apFilter->matchedAccessPointIds($state['filters']['access_point']);
            if ($matched !== []) {
                $allowedApIds = array_fill_keys($matched, true);
            }
        }

        $this->set([
            'purpose' => $purpose,
            'customers' => $customers,
            'apGroups' => $this->groupCustomersByAccessPoint($customers, $apNames, $allowedApIds),
            'ignoreCustomerConsent' => $ignoreCustomerConsent,
            'ignoreContactUse' => $ignoreContactUse,
        ]);
    }

    /**
     * Group recipients by the access point(s) of their contracts, one row per
     * contract — a customer with contracts on several access points is listed
     * under each (so it is visible everywhere it belongs). The opt-out checkbox
     * of every row submits the customer id, so unchecking one row still sends as
     * long as another stays checked; the send step then builds a single message
     * per customer. Customers without an access point form a trailing group.
     *
     * @param array<\App\Model\Entity\Customer> $customers Deduplicated recipients.
     * @param array<array-key, string> $apNames Access point id => name map.
     * @param array<string, true>|null $allowedApIds When set, only these access points are grouped
     *   (the active access point filter's scope); contracts elsewhere are ignored.
     * @return list<array{ap_id: string|null, ap_name: string, customers: list<\App\Model\Entity\Customer>}>
     */
    private function groupCustomersByAccessPoint(array $customers, array $apNames, ?array $allowedApIds = null): array
    {
        /** @var array<string, list<\App\Model\Entity\Customer>> $byAccessPoint */
        $byAccessPoint = [];
        /** @var list<\App\Model\Entity\Customer> $withoutAccessPoint */
        $withoutAccessPoint = [];

        foreach ($customers as $customer) {
            $placed = false;
            foreach ($customer->contracts as $contract) {
                $apId = $contract->access_point_id;
                if (!is_string($apId) || $apId === '') {
                    continue;
                }
                if ($allowedApIds !== null && !isset($allowedApIds[$apId])) {
                    // outside the active access point filter's scope
                    continue;
                }
                $byAccessPoint[$apId][] = $customer;
                $placed = true;
            }

            if (!$placed) {
                $withoutAccessPoint[] = $customer;
            }
        }

        $groups = [];
        foreach ($byAccessPoint as $apId => $groupCustomers) {
            $groups[] = [
                'ap_id' => $apId,
                'ap_name' => $apNames[$apId] ?? __('Unknown access point'),
                'customers' => $groupCustomers,
            ];
        }
        usort($groups, static fn(array $a, array $b): int => strnatcasecmp($a['ap_name'], $b['ap_name']));

        if ($withoutAccessPoint !== []) {
            $groups[] = [
                'ap_id' => null,
                'ap_name' => __('No access point'),
                'customers' => $withoutAccessPoint,
            ];
        }

        return $groups;
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
