<?php
declare(strict_types=1);

namespace App\Contracts\Proposal;

use App\Http\Answer;
use App\Model\Entity\Billing;
use App\Model\Entity\Contract;
use App\Model\Entity\ContractVersion;
use App\NMS\Dto\IpAddressRange;
use Cake\Collection\Collection;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Locator\LocatorAwareTrait;
use InvalidArgumentException;

/**
 * How everything stood when a proposal was drawn up.
 *
 * Documents print from this rather than from the live records, so the same paper printed twice is
 * the same paper even after the price list has moved. It is the live state at the moment it was
 * taken, not a projection - what the proposal asks for is applied to it only at printing, which is
 * what lets the changes be edited without the snapshot having to be recomputed.
 *
 * It doubles as the record of what the terms were before, so nothing has to be kept twice: before
 * carrying a proposal over, the billings here are held up against the live ones to see what moved
 * in the meantime.
 */
final class ProposalSnapshot
{
    use LocatorAwareTrait;

    /**
     * The parts a snapshot always carries.
     *
     * @var array<string>
     */
    public const REQUIRED = [
        'contract',
        'customer',
        'version',
        'billings',
    ];

    /**
     * The terms that are held up against the live billing before a proposal is carried over.
     *
     * The same list the billings table refuses to have rewritten once it has been invoiced for,
     * plus the days the line runs between.
     *
     * @var array<string>
     */
    public const BILLING_TERMS = [
        'service_id',
        'quantity',
        'price',
        'fixed_discount',
        'percentage_discount',
        'billing_from',
        'billing_until',
    ];

    /**
     * @param array<string, mixed> $taken What was on record at the time.
     */
    private function __construct(private readonly array $taken)
    {
    }

    /**
     * Reads a snapshot back from the stored shape.
     *
     * @param array<string, mixed> $stored The stored snapshot.
     * @return self
     * @throws \InvalidArgumentException When a part the documents rely on is missing.
     */
    public static function fromArray(array $stored): self
    {
        foreach (self::REQUIRED as $part) {
            if (!array_key_exists($part, $stored)) {
                throw new InvalidArgumentException(sprintf('The snapshot says nothing about %s.', $part));
            }
        }

        return new self($stored);
    }

    /**
     * Writes the snapshot out in the shape it is stored in.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->taken;
    }

    /**
     * One part of the snapshot.
     *
     * @param string $part Which part.
     * @return array<string, mixed>
     */
    public function part(string $part): array
    {
        return (array)($this->taken[$part] ?? []);
    }

    /**
     * The billings as they stood, by their id.
     *
     * @return array<string, array<string, mixed>>
     */
    public function billings(): array
    {
        $found = [];

        foreach ($this->part('billings') as $billing) {
            $billing = (array)$billing;
            $id = $billing['id'] ?? null;

            if ($id !== null) {
                $found[(string)$id] = $billing;
            }
        }

        return $found;
    }

    /**
     * Whether the snapshot knows the given billing.
     *
     * @param string $billing_id Which billing.
     * @return bool
     */
    public function knowsBilling(string $billing_id): bool
    {
        return array_key_exists($billing_id, $this->billings());
    }

    /**
     * Which terms of the given billing have moved since the snapshot was taken.
     *
     * @param string $billing_id Which billing.
     * @param \App\Model\Entity\Billing $live The billing as it stands now.
     * @return array<string> The terms that no longer agree.
     */
    public function billingTermsThatMoved(string $billing_id, Billing $live): array
    {
        $taken = $this->billings()[$billing_id] ?? null;

        if ($taken === null) {
            return self::BILLING_TERMS;
        }

        $moved = [];

        foreach (self::BILLING_TERMS as $term) {
            $before = $taken[$term] ?? null;
            $now = $live->get($term);

            if ((string)$before !== (string)($now ?? '')) {
                $moved[] = $term;
            }
        }

        return $moved;
    }

    /**
     * The billings that are on the contract now but were not when the snapshot was taken.
     *
     * @param iterable<\App\Model\Entity\Billing> $live The billings as they stand now.
     * @return array<string> Their ids.
     */
    public function billingsAddedSince(iterable $live): array
    {
        $known = $this->billings();
        $added = [];

        foreach ($live as $billing) {
            if (!array_key_exists((string)$billing->id, $known)) {
                $added[] = (string)$billing->id;
            }
        }

        return $added;
    }

    /**
     * The contract as it stood, as unsaved records the documents can be printed from.
     *
     * Only the plain fields were kept, so everything the entities work out - which address is the
     * billing one, how it is written, what a line comes to - is worked out again here, by the same
     * code that works it out for the live records. That is what keeps the two from parting company.
     *
     * @return \App\Model\Entity\Contract
     */
    public function hydrate(): Contract
    {
        $customer = $this->part('customer');

        /** @var \App\Model\Entity\Contract $contract */
        $contract = $this->record('Contracts', $this->part('contract'));
        $contract->set('service_type', $this->record('ServiceTypes', $this->nested('contract', 'service_type')));
        $contract->set('installation_address', $this->record(
            'Addresses',
            $this->nested('contract', 'installation_address'),
        ));

        /** @var \App\Model\Entity\Customer $whom */
        $whom = $this->record('Customers', $customer);
        $whom->set('accounting_profile', $this->record(
            'AccountingProfiles',
            $this->nested('customer', 'accounting_profile'),
        ));
        $whom->set('addresses', $this->records('Addresses', (array)($customer['addresses'] ?? [])));
        $whom->set('emails', $this->records('Emails', (array)($customer['emails'] ?? [])));
        $whom->set('phones', $this->records('Phones', (array)($customer['phones'] ?? [])));
        $contract->set('customer', $whom);

        $contract->set('billings', $this->hydrateBillings());
        $contract->set('borrowed_equipments', $this->hydrateEquipments('BorrowedEquipments', 'borrowed_equipments'));
        $contract->set('sold_equipments', $this->hydrateEquipments('SoldEquipments', 'sold_equipments'));
        $contract->set('ip_addresses', $this->hydrateIpAddresses());
        $contract->set('ip_networks', $this->records('IpNetworks', $this->part('ip_networks')));

        $contract->clean();

        return $contract;
    }

    /**
     * The version the proposal belongs to, as it stood.
     *
     * @return \App\Model\Entity\ContractVersion
     */
    public function hydrateVersion(): ContractVersion
    {
        /** @var \App\Model\Entity\ContractVersion $version */
        $version = $this->record('ContractVersions', $this->part('version'));

        return $version;
    }

    /**
     * The version the proposal terminates, as it stood, where there is one.
     *
     * @return \App\Model\Entity\ContractVersion|null
     */
    public function hydrateTerminatedVersion(): ?ContractVersion
    {
        $taken = $this->taken['terminated_version'] ?? null;

        if (!is_array($taken) || $taken === []) {
            return null;
        }

        /** @var \App\Model\Entity\ContractVersion $version */
        $version = $this->record('ContractVersions', $taken);

        return $version;
    }

    /**
     * What was billed for, each line with what it was for.
     *
     * @return array<\App\Model\Entity\Billing>
     */
    private function hydrateBillings(): array
    {
        $billings = [];

        foreach ($this->part('billings') as $taken) {
            $taken = (array)$taken;
            /** @var \App\Model\Entity\Billing $billing */
            $billing = $this->record('Billings', $taken);

            $service = (array)($taken['service'] ?? []);
            if ($service !== []) {
                /** @var \App\Model\Entity\Service $for */
                $for = $this->record('Services', $service);
                $for->set('queue', $this->record('Queues', (array)($service['queue'] ?? [])));
                $billing->set('service', $for);
            }

            $billings[] = $billing;
        }

        return $billings;
    }

    /**
     * Equipment lent or sold, each with what kind it was.
     *
     * @param string $table Which records they are.
     * @param string $part Which part of the snapshot holds them.
     * @return array<\Cake\Datasource\EntityInterface>
     */
    private function hydrateEquipments(string $table, string $part): array
    {
        $equipments = [];

        foreach ($this->part($part) as $taken) {
            $taken = (array)$taken;
            $equipment = $this->record($table, $taken);

            if ($equipment === null) {
                continue;
            }

            $equipment->set('equipment_type', $this->record(
                'EquipmentTypes',
                (array)($taken['equipment_type'] ?? []),
            ));

            $equipments[] = $equipment;
        }

        return $equipments;
    }

    /**
     * The addresses, each carrying the range that was found for it at the time.
     *
     * @return array<\App\Model\Entity\IpAddress>
     */
    private function hydrateIpAddresses(): array
    {
        $addresses = [];

        foreach ($this->part('ip_addresses') as $taken) {
            $taken = (array)$taken;
            /** @var \App\Model\Entity\IpAddress $address */
            $address = $this->record('IpAddresses', $taken);

            $range = (array)($taken['range'] ?? []);
            $address->set('ip_address_ranges', Answer::of(new Collection(
                $range === [] ? [] : [new IpAddressRange(
                    id: '',
                    network: $range['network'] ?? null,
                    gateway: $range['gateway'] ?? null,
                )],
            )));

            $addresses[] = $address;
        }

        return $addresses;
    }

    /**
     * The services the proposal's own lines chose, as unsaved records.
     *
     * A line that puts a different service on a billing brings that service with it: the contract's
     * snapshot was taken before it was chosen and has never heard of it, and the document has to
     * name it and quote its speeds.
     *
     * @param \App\Contracts\Proposal\ProposalChanges $changes What the proposal asks for.
     * @return array<string, \App\Model\Entity\Service> By the id the lines name them under.
     */
    public function servicesChosenBy(ProposalChanges $changes): array
    {
        $services = [];

        foreach ($changes->billings as $line) {
            if ($line->service_id === null || $line->service === null) {
                continue;
            }

            /** @var \App\Model\Entity\Service|null $service */
            $service = $this->record('Services', $line->service);

            if ($service === null) {
                continue;
            }

            $service->set('queue', $this->record('Queues', (array)($line->service['queue'] ?? [])));
            $services[$line->service_id] = $service;
        }

        return $services;
    }

    /**
     * One record, put back together with the types it was kept in.
     *
     * The marshaller is what turns the stored text back into dates, money and enums, which is why
     * the records go through the table rather than being built by hand.
     *
     * @param string $table Which records it is.
     * @param array<string, mixed> $taken What was kept of it.
     * @return \Cake\Datasource\EntityInterface|null Null where nothing was kept.
     */
    private function record(string $table, array $taken): ?EntityInterface
    {
        $fields = array_filter(
            $taken,
            fn(mixed $value, string $field): bool => !is_array($value),
            ARRAY_FILTER_USE_BOTH,
        );

        if ($fields === []) {
            return null;
        }

        $entity = $this->fetchTable($table)->newEntity($fields, [
            'validate' => false,
            'accessibleFields' => ['*' => true],
        ]);
        $entity->clean();
        $entity->setNew(false);

        return $entity;
    }

    /**
     * Every one of them.
     *
     * @param string $table Which records they are.
     * @param array<mixed> $taken What was kept of them.
     * @return array<\Cake\Datasource\EntityInterface>
     */
    private function records(string $table, array $taken): array
    {
        $records = [];

        foreach ($taken as $one) {
            $record = $this->record($table, (array)$one);

            if ($record !== null) {
                $records[] = $record;
            }
        }

        return $records;
    }

    /**
     * A record kept inside another.
     *
     * @param string $part Which part of the snapshot.
     * @param string $within Which record inside it.
     * @return array<string, mixed>
     */
    private function nested(string $part, string $within): array
    {
        return (array)($this->part($part)[$within] ?? []);
    }
}
