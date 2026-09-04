<?php
declare(strict_types=1);

namespace App\Contracts\Proposal;

use Cake\I18n\Date;
use Cake\Utility\Text;
use InvalidArgumentException;
use PhpCollective\DecimalObject\Decimal;

/**
 * One line of what a proposal asks the billing to become.
 *
 * A line either adds something that was not there, replaces something that was, or ends it. Which
 * of the three it is follows from two fields: a line without a billing adds, and a line with one
 * either replaces or - when it says so - only ends.
 *
 * Each line carries its own days, which is what lets one change be several: half price for a year
 * and then full price is two lines, the first replacing what was there and ending after a year, the
 * second picking up where it left off. A line that does not say when it starts starts on the day
 * the proposal takes effect.
 *
 * What replaces carries the whole set of terms rather than only the changed ones. A partial
 * override could not tell "leave the price alone" from "put it back on the price list", and the
 * form fills the whole set in from what is there anyway.
 */
final class ProposedBilling
{
    /**
     * @param string $id This line's own name, so that it can be edited and dropped again.
     * @param string|null $billing_id The billing being replaced or ended; null adds a new one.
     * @param bool $terminates_only Whether the billing only ends and nothing takes its place.
     * @param string|null $service_id The service being billed for.
     * @param string|null $text What to call the line when it is not a service.
     * @param int $quantity How many of it.
     * @param \PhpCollective\DecimalObject\Decimal|null $price An individual price; null takes the price list.
     * @param \PhpCollective\DecimalObject\Decimal|null $fixed_discount A discount in money.
     * @param int|null $percentage_discount A discount in percent.
     * @param \Cake\I18n\Date|null $billing_from The day it starts; null starts with the proposal.
     * @param \Cake\I18n\Date|null $billing_until The day it stops; null runs on.
     * @param bool $separate_invoice Whether the line is invoiced on its own.
     * @param string|null $note Whatever the operator wrote down.
     * @param array<string, mixed>|null $service The service as it stood when it was chosen.
     */
    public function __construct(
        public readonly string $id,
        public readonly ?string $billing_id,
        public readonly bool $terminates_only,
        public readonly ?string $service_id,
        public readonly ?string $text,
        public readonly int $quantity,
        public readonly ?Decimal $price,
        public readonly ?Decimal $fixed_discount,
        public readonly ?int $percentage_discount,
        public readonly ?Date $billing_from,
        public readonly ?Date $billing_until,
        public readonly bool $separate_invoice,
        public readonly ?string $note,
        public readonly ?array $service = null,
    ) {
        if ($this->billing_id === null && $this->terminates_only) {
            throw new InvalidArgumentException('A line that adds nothing cannot end anything either.');
        }
    }

    /**
     * Whether the line puts a billing there that was not there before.
     *
     * @return bool
     */
    public function isAddition(): bool
    {
        return $this->billing_id === null;
    }

    /**
     * Whether the line only ends a billing, with nothing taking its place.
     *
     * @return bool
     */
    public function terminatesOnly(): bool
    {
        return $this->terminates_only;
    }

    /**
     * Whether the line puts a billing in place of, or in addition to, what is there.
     *
     * @return bool
     */
    public function startsABilling(): bool
    {
        return !$this->terminates_only;
    }

    /**
     * The day this line starts, given the day the proposal takes effect.
     *
     * @param \Cake\I18n\Date $effective_from The day the proposal takes effect.
     * @return \Cake\I18n\Date
     */
    public function startsOn(Date $effective_from): Date
    {
        return $this->billing_from ?? $effective_from;
    }

    /**
     * The same line with something else said about it.
     *
     * @param array<string, mixed> $said What is said differently.
     * @return self
     */
    public function with(array $said): self
    {
        return self::fromArray($said + $this->toArray());
    }

    /**
     * Reads one line back from the stored shape.
     *
     * @param array<string, mixed> $line The stored line.
     * @return self
     */
    public static function fromArray(array $line): self
    {
        return new self(
            id: self::text($line, 'id') ?? Text::uuid(),
            billing_id: self::text($line, 'billing_id'),
            terminates_only: (bool)($line['terminates_only'] ?? false),
            service_id: self::text($line, 'service_id'),
            text: self::text($line, 'text'),
            quantity: (int)($line['quantity'] ?? 1),
            price: self::money($line, 'price'),
            fixed_discount: self::money($line, 'fixed_discount'),
            percentage_discount: self::text($line, 'percentage_discount') === null
                ? null
                : (int)$line['percentage_discount'],
            billing_from: self::day($line, 'billing_from'),
            billing_until: self::day($line, 'billing_until'),
            separate_invoice: (bool)($line['separate_invoice'] ?? false),
            note: self::text($line, 'note'),
            service: isset($line['service']) && is_array($line['service']) ? $line['service'] : null,
        );
    }

    /**
     * Writes the line out in the shape it is stored in.
     *
     * Money goes out as a string and dates as ISO text, because JSONB has no decimal type worth
     * trusting with a price.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'billing_id' => $this->billing_id,
            'terminates_only' => $this->terminates_only,
            'service_id' => $this->service_id,
            'text' => $this->text,
            'quantity' => $this->quantity,
            'price' => $this->price?->toString(),
            'fixed_discount' => $this->fixed_discount?->toString(),
            'percentage_discount' => $this->percentage_discount,
            'billing_from' => $this->billing_from?->toDateString(),
            'billing_until' => $this->billing_until?->toDateString(),
            'separate_invoice' => $this->separate_invoice,
            'note' => $this->note,
            'service' => $this->service,
        ];
    }

    /**
     * A stored value read as text, with an empty one meaning nothing was said.
     *
     * @param array<string, mixed> $line The stored line.
     * @param string $key Which value.
     * @return string|null
     */
    private static function text(array $line, string $key): ?string
    {
        $value = $line[$key] ?? null;

        if (is_array($value)) {
            return null;
        }

        return $value === null || $value === '' ? null : (string)$value;
    }

    /**
     * A stored value read as a day.
     *
     * @param array<string, mixed> $line The stored line.
     * @param string $key Which value.
     * @return \Cake\I18n\Date|null
     */
    private static function day(array $line, string $key): ?Date
    {
        $value = $line[$key] ?? null;

        if ($value instanceof Date) {
            return $value;
        }

        $value = self::text($line, $key);

        return $value === null ? null : new Date($value);
    }

    /**
     * A stored value read as money.
     *
     * @param array<string, mixed> $line The stored line.
     * @param string $key Which value.
     * @return \PhpCollective\DecimalObject\Decimal|null
     */
    private static function money(array $line, string $key): ?Decimal
    {
        $value = self::text($line, $key);

        return $value === null ? null : Decimal::create($value);
    }
}
