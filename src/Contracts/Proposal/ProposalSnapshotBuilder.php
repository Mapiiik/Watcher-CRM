<?php
declare(strict_types=1);

namespace App\Contracts\Proposal;

use App\Model\Entity\Contract;
use App\Model\Entity\ContractVersion;
use App\Model\Enum\IpAddressTypeOfUse;
use BackedEnum;
use Cake\Datasource\EntityInterface;
use Cake\I18n\Date;
use Cake\I18n\DateTime;
use PhpCollective\DecimalObject\Decimal;

/**
 * Takes down how everything stood, so that the documents have something to print from that will
 * not move under them.
 *
 * The contract is expected to come in loaded the way printing loads it; nothing is fetched here
 * except the address ranges, which are not on file at all but asked of another system, and which
 * therefore have to be asked now rather than at every printing.
 */
final class ProposalSnapshotBuilder
{
    /**
     * What everything says right now.
     *
     * @param \App\Model\Entity\Contract $contract The contract, loaded as printing loads it.
     * @param \App\Model\Entity\ContractVersion $version The version the proposal belongs to.
     * @param \App\Model\Entity\ContractVersion|null $terminated The version it terminates, if any.
     * @return array<string, mixed>
     */
    public function take(
        Contract $contract,
        ContractVersion $version,
        ?ContractVersion $terminated = null,
    ): array {
        return [
            'contract' => $this->contract($contract),
            'customer' => $this->customer($contract),
            'version' => $this->fields($version, SnapshotShape::VERSION),
            'terminated_version' => $terminated === null
                ? null
                : $this->fields($terminated, SnapshotShape::VERSION),
            'billings' => $this->billings($contract),
            'borrowed_equipments' => $this->equipments($contract->borrowed_equipments ?? []),
            'sold_equipments' => $this->equipments($contract->sold_equipments ?? []),
            'ip_addresses' => $this->ipAddresses($contract),
            'ip_networks' => $this->each($contract->ip_networks ?? [], SnapshotShape::IP_NETWORK),
        ];
    }

    /**
     * The contract, with what it needs to work out its own answers.
     *
     * @param \App\Model\Entity\Contract $contract The contract.
     * @return array<string, mixed>
     */
    private function contract(Contract $contract): array
    {
        return $this->fields($contract, SnapshotShape::CONTRACT) + [
            'service_type' => isset($contract->service_type)
                ? $this->fields($contract->service_type, SnapshotShape::SERVICE_TYPE)
                : null,
            'installation_address' => isset($contract->installation_address)
                ? $this->fields($contract->installation_address, SnapshotShape::ADDRESS)
                : null,
        ];
    }

    /**
     * The customer, with the addresses the contract picks from and the ways of reaching them.
     *
     * @param \App\Model\Entity\Contract $contract The contract.
     * @return array<string, mixed>
     */
    private function customer(Contract $contract): array
    {
        $customer = $contract->customer;

        return $this->fields($customer, SnapshotShape::CUSTOMER) + [
            'accounting_profile' => isset($customer->accounting_profile)
                ? $this->fields($customer->accounting_profile, SnapshotShape::ACCOUNTING_PROFILE)
                : null,
            'addresses' => $this->each($customer->addresses ?? [], SnapshotShape::ADDRESS),
            'emails' => $this->each($customer->emails ?? [], SnapshotShape::EMAIL),
            'phones' => $this->each($customer->phones ?? [], SnapshotShape::PHONE),
        ];
    }

    /**
     * What is billed for, and what each line is for.
     *
     * @param \App\Model\Entity\Contract $contract The contract.
     * @return array<int, array<string, mixed>>
     */
    private function billings(Contract $contract): array
    {
        $taken = [];

        foreach ($contract->billings ?? [] as $billing) {
            $service = $billing->service ?? null;

            $taken[] = $this->fields($billing, SnapshotShape::BILLING) + [
                'service' => $service === null
                    ? null
                    : $this->fields($service, SnapshotShape::SERVICE) + [
                        'queue' => isset($service->queue)
                            ? $this->fields($service->queue, SnapshotShape::QUEUE)
                            : null,
                    ],
            ];
        }

        return $taken;
    }

    /**
     * Equipment lent or sold, with what kind it is.
     *
     * @param iterable<\Cake\Datasource\EntityInterface> $equipments What is on the contract.
     * @return array<int, array<string, mixed>>
     */
    private function equipments(iterable $equipments): array
    {
        $taken = [];

        foreach ($equipments as $equipment) {
            $taken[] = $this->fields($equipment, SnapshotShape::EQUIPMENT) + [
                'equipment_type' => isset($equipment->equipment_type)
                    ? $this->fields($equipment->equipment_type, SnapshotShape::EQUIPMENT_TYPE)
                    : null,
            ];
        }

        return $taken;
    }

    /**
     * The addresses, each with the range it sits in.
     *
     * The range is the one thing here that is not on file: it is asked of the network management
     * system, and asking it now is what keeps a reprint from depending on that system being up.
     *
     * @param \App\Model\Entity\Contract $contract The contract.
     * @return array<int, array<string, mixed>>
     */
    private function ipAddresses(Contract $contract): array
    {
        $taken = [];

        foreach ($contract->ip_addresses ?? [] as $address) {
            $range = null;

            if ($address->type_of_use === IpAddressTypeOfUse::CustomerManually) {
                $found = $address->ip_address_ranges->data?->first();
                $range = $found === null
                    ? null
                    : ['network' => $found->network, 'gateway' => $found->gateway];
            }

            $taken[] = $this->fields($address, SnapshotShape::IP_ADDRESS) + ['range' => $range];
        }

        return $taken;
    }

    /**
     * The named fields of every one of them.
     *
     * @param iterable<\Cake\Datasource\EntityInterface> $entities The records.
     * @param array<string> $shape Which fields.
     * @return array<int, array<string, mixed>>
     */
    private function each(iterable $entities, array $shape): array
    {
        $taken = [];

        foreach ($entities as $entity) {
            $taken[] = $this->fields($entity, $shape);
        }

        return $taken;
    }

    /**
     * The named fields of one record, in a shape JSONB can be trusted with.
     *
     * @param \Cake\Datasource\EntityInterface $entity The record.
     * @param array<string> $shape Which fields.
     * @return array<string, mixed>
     */
    private function fields(EntityInterface $entity, array $shape): array
    {
        $taken = [];

        foreach ($shape as $field) {
            $taken[$field] = $this->plain($entity->get($field));
        }

        return $taken;
    }

    /**
     * One value, written so that it comes back the same.
     *
     * Money goes out as a string and dates as ISO text; JSONB has no decimal type worth trusting
     * with a price, and a date read back as a number would say nothing.
     *
     * @param mixed $value What the record holds.
     * @return mixed
     */
    private function plain(mixed $value): mixed
    {
        return match (true) {
            $value instanceof Decimal => $value->toString(),
            $value instanceof Date => $value->toDateString(),
            $value instanceof DateTime => $value->toIso8601String(),
            $value instanceof BackedEnum => $value->value,
            default => $value,
        };
    }
}
