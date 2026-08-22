<?php
declare(strict_types=1);

namespace App\Test\TestCase\BusinessRegister\Source;

use App\BusinessRegister\Provider\SubjectPayloadNormalizer;
use App\BusinessRegister\Source\SourceInterface;
use App\BusinessRegister\Source\VatNumberCheckInterface;
use App\BusinessRegister\VatNumberCheck;
use App\Http\Answer;
use Override;

/**
 * A register that answers whatever the test told it to.
 *
 * The registry builds its sources itself, so what this one answers is said statically before it
 * is reached. Standing in for the real registers keeps these tests off the network entirely,
 * which is the point of the sources being an interface: choosing between registers and filling
 * a form in from one are application logic and can be checked without asking anybody.
 */
class StubSource implements SourceInterface, VatNumberCheckInterface
{
    /**
     * Whether the register claims it can answer.
     *
     * @var bool
     */
    public static bool $configured = true;

    /**
     * The entries it answers a search with.
     *
     * @var list<array<string, mixed>>
     */
    public static array $entries = [];

    /**
     * What it says about a VAT number.
     *
     * @var \App\BusinessRegister\VatNumberCheck|null
     */
    public static ?VatNumberCheck $vatNumberCheck = null;

    /**
     * Whether it is down when asked about a VAT number.
     *
     * @var bool
     */
    public static bool $unreachable = false;

    /**
     * Whether it is down when asked to fetch an entry back.
     *
     * @var bool
     */
    public static bool $unreachableOnReference = false;

    /**
     * Put back what a test found, so the next one starts from the same place.
     *
     * @return void
     */
    public static function reset(): void
    {
        self::$configured = true;
        self::$entries = [];
        self::$vatNumberCheck = null;
        self::$unreachable = false;
        self::$unreachableOnReference = false;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function key(): string
    {
        return 'stub';
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function label(): string
    {
        return 'XX - Stub';
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function isConfigured(): bool
    {
        return self::$configured;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function search(string $query, int $limit = 25): Answer
    {
        return Answer::of(SubjectPayloadNormalizer::subjects(array_slice(self::$entries, 0, $limit)));
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function byReference(string $reference): Answer
    {
        if (self::$unreachableOnReference) {
            return Answer::failed('The stub register is unreachable.');
        }

        foreach (self::$entries as $entry) {
            if (($entry['reference'] ?? null) === $reference) {
                return Answer::of(SubjectPayloadNormalizer::subject($entry));
            }
        }

        return Answer::of(null);
    }

    /**
     * @return \App\Http\Answer<\App\BusinessRegister\VatNumberCheck|null>
     * @inheritDoc
     */
    #[Override]
    public function vatNumberCheck(string $vatNumber): Answer
    {
        if (self::$unreachable) {
            return Answer::failed('The stub register is unreachable.');
        }

        return Answer::of(self::$vatNumberCheck);
    }
}
