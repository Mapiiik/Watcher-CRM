<?php
declare(strict_types=1);

namespace App\BulkMessages;

use App\BulkMessages\Filter\ContractScopedFilterInterface;
use App\BulkMessages\Filter\CustomerScopedFilterInterface;
use App\Model\Entity\Contract;
use App\Model\Entity\Customer;
use App\Model\Enum\CustomerMessagePurpose;
use App\Model\Enum\ServiceCriticalityLevel;
use App\Model\Table\CustomersTable;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Query\SelectQuery;

/**
 * Who a bulk message goes to, and how the list of them is shown.
 *
 * @phpstan-type BulkRecipientRow array{
 *     customer: \App\Model\Entity\Customer,
 *     contract: \App\Model\Entity\Contract|null,
 *     services: list<string>,
 *     vip: bool,
 *     criticality: \App\Model\Enum\ServiceCriticalityLevel|null
 * }
 */
final class BulkRecipients
{
    use LocatorAwareTrait;

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
    public function findCustomers(
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
                'Customers.id IN' => $this->customers()->Contracts
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

        /** @var array<\App\Model\Entity\Customer> $found */
        $found = $this->customers()->find()
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

        return $found;
    }

    /**
     * Selected recipients that the send-time query no longer returns.
     *
     * @param list<string> $sendTo Customer ids the operator kept checked.
     * @param array<\App\Model\Entity\Customer> $customers Recipients resolved at send time.
     * @return list<array{number: string|null, name: string}>
     */
    public function findDropped(array $sendTo, array $customers): array
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
            $this->customers()
                ->find()
                ->where(['Customers.id IN' => $droppedIds])
                ->all() as $customer
        ) {
            /** @var \App\Model\Entity\Customer $customer */
            $dropped[] = [
                'number' => $customer->number,
                'name' => $customer->name,
            ];
        }

        return $dropped;
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
    public function groupByAccessPoint(array $customers, array $apNames): array
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
     * How many of the given customers carry each flag — counted per customer, so
     * someone with two VIP contracts is one person to double-check, not two.
     *
     * @param array<\App\Model\Entity\Customer> $customers Recipients to inspect.
     * @return array{vip: int, critical: int}
     */
    public function countFlagged(array $customers): array
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
     * @return \App\Model\Table\CustomersTable
     */
    private function customers(): CustomersTable
    {
        return $this->fetchTable(CustomersTable::class);
    }
}
