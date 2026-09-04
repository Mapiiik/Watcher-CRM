<?php
declare(strict_types=1);

namespace App\Contracts\Proposal;

use Cake\I18n\Date;
use InvalidArgumentException;
use PhpCollective\DecimalObject\Decimal;

/**
 * One line of what a proposal asks the billing to become.
 *
 * A line either adds something that was not there, replaces something that was, or ends it. Which
 * of the three it is follows from two fields: a line without a billing adds, and a line with one
 * either replaces or - when it says so - only ends.
 *
 * What replaces carries the whole set of terms rather than only the changed ones. A partial
 * override could not tell "leave the price alone" from "put it back on the price list", and the
 * form fills the whole set in from the snapshot anyway.
 */
final class ProposedBilling
{
    /**
     * @param string|null $billing_id The billing being replaced or ended; null adds a new one.
     * @param bool $terminates_only Whether the billing only ends and nothing takes its place.
     * @param string|null $service_id The service being billed for.
     * @param string|null $text What to call the line when it is not a service.
     * @param int $quantity How many of it.
     * @param \PhpCollective\DecimalObject\Decimal|null $price An individual price; null takes the price list.
     * @param \PhpCollective\DecimalObject\Decimal|null $fixed_discount A discount in money.
     * @param int|null $percentage_discount A discount in percent.
     * @param bool $separate_invoice Whether the line is invoiced on its own.
     * @param \Cake\I18n\Date|null $billing_until The day the new line stops; null runs on.
     * @param string|null $note Whatever the operator wrote down.
     * @param array<string, mixed>|null $service The service as it stood when it was chosen.
     */
    public function __construct(
        public readonly ?string $billing_id,
        public readonly bool $terminates_only,
        public readonly ?string $service_id,
        public readonly ?string $text,
        public readonly int $quantity,
        public readonly ?Decimal $price,
        public readonly ?Decimal $fixed_discount,
        public readonly ?int $percentage_discount,
        public readonly bool $separate_invoice,
        public readonly ?Date $billing_until,
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
     * Reads one line back from the stored shape.
     *
     * @param array<string, mixed> $line The stored line.
     * @return self
     */
    public static function fromArray(array $line): self
    {
        return new self(
            billing_id: self::text($line, 'billing_id'),
            terminates_only: (bool)($line['terminates_only'] ?? false),
            service_id: self::text($line, 'service_id'),
            text: self::text($line, 'text'),
            quantity: (int)($line['quantity'] ?? 1),
            price: self::money($line, 'price'),
            fixed_discount: self::money($line, 'fixed_discount'),
            percentage_discount: isset($line['percentage_discount'])
                ? (int)$line['percentage_discount']
                : null,
            separate_invoice: (bool)($line['separate_invoice'] ?? false),
            billing_until: isset($line['billing_until'])
                ? new Date((string)$line['billing_until'])
                : null,
            note: self::text($line, 'note'),
            service: isset($line['service']) ? (array)$line['service'] : null,
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
            'billing_id' => $this->billing_id,
            'terminates_only' => $this->terminates_only,
            'service_id' => $this->service_id,
            'text' => $this->text,
            'quantity' => $this->quantity,
            'price' => $this->price?->toString(),
            'fixed_discount' => $this->fixed_discount?->toString(),
            'percentage_discount' => $this->percentage_discount,
            'separate_invoice' => $this->separate_invoice,
            'billing_until' => $this->billing_until?->toDateString(),
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

        return $value === null || $value === '' ? null : (string)$value;
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
