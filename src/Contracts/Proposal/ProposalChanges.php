<?php
declare(strict_types=1);

namespace App\Contracts\Proposal;

use InvalidArgumentException;

/**
 * What a proposal asks to happen once it is signed.
 *
 * The top-level keys are the records being asked: the billing lines, the equipment lent with the
 * contract, the version the proposal belongs to, and the contract itself. A key that is not there
 * means that record is left alone, which is how a proposal that changes nothing looks - and that is
 * the ordinary case, not an odd one. A new contract has its billings in place before the papers are
 * printed, so the proposal behind them is only a stamp.
 *
 * IP addresses will be asked for here in time; the snapshot already carries them, so it will take
 * a value object and a step in applying the changes, and no migration.
 */
final class ProposalChanges
{
    /**
     * The billing lines.
     */
    public const BILLINGS = 'billings';

    /**
     * The equipment lent with the contract.
     */
    public const EQUIPMENTS = 'equipments';

    /**
     * The version the proposal belongs to.
     */
    public const VERSION = 'version';

    /**
     * The contract itself.
     */
    public const CONTRACT = 'contract';

    /**
     * Every record a proposal may ask something of.
     *
     * @var array<string>
     */
    public const DOMAINS = [
        self::BILLINGS,
        self::EQUIPMENTS,
        self::VERSION,
        self::CONTRACT,
    ];

    /**
     * @param array<\App\Contracts\Proposal\ProposedBilling> $billings The billing lines.
     * @param \App\Contracts\Proposal\ProposedVersion $version What the version is asked to become.
     * @param \App\Contracts\Proposal\ProposedContract $contract What the contract is asked to become.
     * @param array<\App\Contracts\Proposal\ProposedEquipment> $equipments The equipment lines.
     */
    private function __construct(
        public readonly array $billings,
        public readonly ProposedVersion $version,
        public readonly ProposedContract $contract,
        public readonly array $equipments = [],
    ) {
    }

    /**
     * A proposal that changes nothing.
     *
     * @return self
     */
    public static function nothing(): self
    {
        return new self([], ProposedVersion::untouched(), ProposedContract::untouched());
    }

    /**
     * Whether the proposal asks for anything at all.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->billings === []
            && $this->equipments === []
            && $this->version->isEmpty()
            && $this->contract->isEmpty();
    }

    /**
     * Whether the papers end anything at all - the version they belong to, or the contract.
     *
     * Ending is one act written in two places, and where the contract ends a rule holds the two
     * dates to the same day. That direction only: a version ending while the contract runs on is
     * how an agreement to end one version and sign another is written, and there the contract
     * carries on. So this is the question a paper asks - it is a termination either way - while
     * {@see \App\Model\Entity\ContractProposal::endsTheContract()} is the one the records ask.
     *
     * @return bool
     */
    public function endsTheContract(): bool
    {
        return $this->version->endsTheVersion() || $this->contract->endsTheContract();
    }

    /**
     * The billings the proposal names, by the billing each line acts on.
     *
     * Lines that add a billing name none, so they are left out.
     *
     * @return array<string, \App\Contracts\Proposal\ProposedBilling>
     */
    public function billingsByBillingId(): array
    {
        $found = [];

        foreach ($this->billings as $line) {
            if (!$line->isAddition()) {
                $found[(string)$line->billing_id] = $line;
            }
        }

        return $found;
    }

    /**
     * One of the billing lines, by its own name.
     *
     * @param string $id Which line.
     * @return \App\Contracts\Proposal\ProposedBilling|null
     */
    public function line(string $id): ?ProposedBilling
    {
        foreach ($this->billings as $line) {
            if ($line->id === $id) {
                return $line;
            }
        }

        return null;
    }

    /**
     * The same changes with one line put in, replacing the one of that name where there is one.
     *
     * @param \App\Contracts\Proposal\ProposedBilling $line The line.
     * @return self
     */
    public function withLine(ProposedBilling $line): self
    {
        $lines = [];
        $replaced = false;

        foreach ($this->billings as $one) {
            if ($one->id === $line->id) {
                $lines[] = $line;
                $replaced = true;

                continue;
            }

            $lines[] = $one;
        }

        if (!$replaced) {
            $lines[] = $line;
        }

        return new self($lines, $this->version, $this->contract, $this->equipments);
    }

    /**
     * The same changes with one line taken out.
     *
     * @param string $id Which line.
     * @return self
     */
    public function withoutLine(string $id): self
    {
        return new self(
            array_values(array_filter(
                $this->billings,
                fn(ProposedBilling $one): bool => $one->id !== $id,
            )),
            $this->version,
            $this->contract,
            $this->equipments,
        );
    }

    /**
     * What the proposal lends.
     *
     * @return list<\App\Contracts\Proposal\ProposedEquipment>
     */
    public function equipmentLent(): array
    {
        return array_values(array_filter(
            $this->equipments,
            fn(ProposedEquipment $line): bool => $line->lends(),
        ));
    }

    /**
     * What the proposal takes back, by the piece of equipment each line names.
     *
     * @return array<string, \App\Contracts\Proposal\ProposedEquipment>
     */
    public function equipmentTakenBack(): array
    {
        $found = [];

        foreach ($this->equipments as $line) {
            if ($line->takesBack()) {
                $found[(string)$line->borrowed_equipment_id] = $line;
            }
        }

        return $found;
    }

    /**
     * Whether equipment goes both ways: something taken back and something else lent.
     *
     * @return bool
     */
    public function exchangesEquipment(): bool
    {
        return $this->equipmentLent() !== [] && $this->equipmentTakenBack() !== [];
    }

    /**
     * One of the equipment lines, by its own name.
     *
     * @param string $id Which line.
     * @return \App\Contracts\Proposal\ProposedEquipment|null
     */
    public function equipmentLine(string $id): ?ProposedEquipment
    {
        foreach ($this->equipments as $line) {
            if ($line->id === $id) {
                return $line;
            }
        }

        return null;
    }

    /**
     * The same changes with one equipment line put in, replacing the one of that name.
     *
     * @param \App\Contracts\Proposal\ProposedEquipment $line The line.
     * @return self
     */
    public function withEquipmentLine(ProposedEquipment $line): self
    {
        $lines = [];
        $replaced = false;

        foreach ($this->equipments as $one) {
            if ($one->id === $line->id) {
                $lines[] = $line;
                $replaced = true;

                continue;
            }

            $lines[] = $one;
        }

        if (!$replaced) {
            $lines[] = $line;
        }

        return new self($this->billings, $this->version, $this->contract, $lines);
    }

    /**
     * The same changes with one equipment line taken out.
     *
     * @param string $id Which line.
     * @return self
     */
    public function withoutEquipmentLine(string $id): self
    {
        return new self(
            $this->billings,
            $this->version,
            $this->contract,
            array_values(array_filter(
                $this->equipments,
                fn(ProposedEquipment $one): bool => $one->id !== $id,
            )),
        );
    }

    /**
     * The same changes with something else said about the version.
     *
     * @param \App\Contracts\Proposal\ProposedVersion $version What the version is asked to become.
     * @return self
     */
    public function withVersion(ProposedVersion $version): self
    {
        return new self($this->billings, $version, $this->contract, $this->equipments);
    }

    /**
     * The same changes with something else said about the contract.
     *
     * @param \App\Contracts\Proposal\ProposedContract $contract What the contract is asked to become.
     * @return self
     */
    public function withContract(ProposedContract $contract): self
    {
        return new self($this->billings, $this->version, $contract, $this->equipments);
    }

    /**
     * Reads the changes back from the stored shape.
     *
     * @param array<string, mixed> $stored The stored changes.
     * @return self
     * @throws \InvalidArgumentException When a record is named that a proposal cannot ask for.
     */
    public static function fromArray(array $stored): self
    {
        foreach (array_keys($stored) as $domain) {
            if (!in_array($domain, self::DOMAINS, true)) {
                throw new InvalidArgumentException(
                    sprintf('A proposal cannot ask anything of %s.', (string)$domain),
                );
            }
        }

        return new self(
            billings: array_map(
                fn(array $line): ProposedBilling => ProposedBilling::fromArray($line),
                array_values((array)($stored[self::BILLINGS] ?? [])),
            ),
            version: ProposedVersion::fromArray((array)($stored[self::VERSION] ?? [])),
            contract: ProposedContract::fromArray((array)($stored[self::CONTRACT] ?? [])),
            equipments: array_map(
                fn(array $line): ProposedEquipment => ProposedEquipment::fromArray($line),
                array_values((array)($stored[self::EQUIPMENTS] ?? [])),
            ),
        );
    }

    /**
     * Writes the changes out in the shape they are stored in.
     *
     * A record that is asked for nothing is left out rather than written as empty, so that reading
     * the stored value back says the same thing it said going in.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $stored = [];

        if ($this->billings !== []) {
            $stored[self::BILLINGS] = array_map(
                fn(ProposedBilling $line): array => $line->toArray(),
                $this->billings,
            );
        }

        if ($this->equipments !== []) {
            $stored[self::EQUIPMENTS] = array_map(
                fn(ProposedEquipment $line): array => $line->toArray(),
                $this->equipments,
            );
        }

        if (!$this->version->isEmpty()) {
            $stored[self::VERSION] = $this->version->toArray();
        }

        if (!$this->contract->isEmpty()) {
            $stored[self::CONTRACT] = $this->contract->toArray();
        }

        return $stored;
    }
}
