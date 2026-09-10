<?php
declare(strict_types=1);

namespace App\Model\Enum;

use App\Model\Enum\Trait\EnumOptionsTrait;
use Cake\Database\Type\EnumLabelInterface;
use Override;

/**
 * DocumentVariant Enum
 *
 * Whose signatures a paper on file carries, and where it came from.
 *
 * Not what happened to it: a copy we printed with our signature already on it and a copy we sent
 * blank are both things we sent, so telling them apart by the sending tells them apart by nothing.
 * What separates every paper we keep is who has signed it, and whether we made it or it came back.
 */
enum DocumentVariant: string implements EnumLabelInterface
{
    use EnumOptionsTrait;

    case Generated = 'generated';
    case GeneratedSignedByUs = 'generated-signed-by-us';
    case ReceivedSignedByCustomer = 'received-signed-by-customer';
    case ReceivedSignedByBoth = 'received-signed-by-both';

    /**
     * @return string
     */
    #[Override]
    public function label(): string
    {
        return match ($this) {
            self::Generated => __('Drawn up, unsigned'),
            self::GeneratedSignedByUs => __('Drawn up, signed by us'),
            self::ReceivedSignedByCustomer => __('Came back signed by the customer'),
            self::ReceivedSignedByBoth => __('Signed by both'),
        };
    }

    /**
     * Whether a paper in this hand carries the customer's own signature.
     *
     * Asked wherever the question is whether the papers are in order, so that adding a role later
     * settles it in one place rather than in every check that cares.
     *
     * @return bool
     */
    public function carriesTheCustomersSignature(): bool
    {
        return in_array($this, [
            self::ReceivedSignedByCustomer,
            self::ReceivedSignedByBoth,
        ], true);
    }

    /**
     * Whether we made this paper rather than received it.
     *
     * What we made is frozen once it is on file - it is handed back rather than drawn again - so
     * this is also the question of whether letting go of it puts a document back within reach.
     *
     * @return bool
     */
    public function isDrawnUpByUs(): bool
    {
        return in_array($this, [
            self::Generated,
            self::GeneratedSignedByUs,
        ], true);
    }

    /**
     * Whether this paper came back to us rather than being made by us.
     *
     * @return bool
     */
    public function isReceived(): bool
    {
        return !$this->isDrawnUpByUs();
    }

    /**
     * Which paper a request to print asks for.
     *
     * @param bool $signed Whether the operator asked for a copy with our signature on it.
     * @return self
     */
    public static function forPrinting(bool $signed): self
    {
        return $signed ? self::GeneratedSignedByUs : self::Generated;
    }

    /**
     * The ones a scan can be filed as, for a form to offer.
     *
     * Nobody uploads a paper we drew up - that arrives by being printed - so the two we made are
     * not on offer.
     *
     * @return array<string, string>
     */
    public static function received(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            if ($case->isReceived()) {
                $options[$case->value] = $case->label();
            }
        }

        return $options;
    }
}
