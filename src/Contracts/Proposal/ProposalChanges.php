<?php
declare(strict_types=1);

namespace App\Contracts\Proposal;

use InvalidArgumentException;

/**
 * What a proposal asks to happen once it is signed.
 *
 * The top-level keys are the records being asked: the billing lines, the version the proposal
 * belongs to, and the contract itself. A key that is not there means that record is left alone,
 * which is how a proposal that changes nothing looks - and that is the ordinary case, not an odd
 * one. A new contract has its billings in place before the papers are printed, so the proposal
 * behind them is only a stamp.
 *
 * Equipment and IP addresses will be asked for here in time; the snapshot already carries them,
 * so it will take a value object and a step in the transfer, and no migration.
 */
final class ProposalChanges
{
    /**
     * The billing lines.
     */
    public const BILLINGS = 'billings';

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
        self::VERSION,
        self::CONTRACT,
    ];

    /**
     * @param array<\App\Contracts\Proposal\ProposedBilling> $billings The billing lines.
     * @param \App\Contracts\Proposal\ProposedVersion $version What the version is asked to become.
     * @param \App\Contracts\Proposal\ProposedContract $contract What the contract is asked to become.
     */
    private function __construct(
        public readonly array $billings,
        public readonly ProposedVersion $version,
        public readonly ProposedContract $contract,
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
            && $this->version->isEmpty()
            && $this->contract->isEmpty();
    }

    /**
     * Whether the proposal brings the contract to an end.
     *
     * Ending is one act written in two places, and a rule holds the two dates to the same day, so
     * asking either one answers for both.
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

        if (!$this->version->isEmpty()) {
            $stored[self::VERSION] = $this->version->toArray();
        }

        if (!$this->contract->isEmpty()) {
            $stored[self::CONTRACT] = $this->contract->toArray();
        }

        return $stored;
    }
}
