<?php
declare(strict_types=1);

namespace App\Test\TestCase\BusinessRegister\Source;

use App\BusinessRegister\Source\SourceInterface;
use App\BusinessRegister\Source\VatNumberCheckInterface;
use App\BusinessRegister\VatNumberCheck;
use Override;
use RuntimeException;

/**
 * A register that is never reachable.
 *
 * Standing beside one that answers, it is what shows that a register being down is not taken for
 * its answer - the rest are asked all the same.
 */
class BrokenSource implements SourceInterface, VatNumberCheckInterface
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function key(): string
    {
        return 'broken';
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function label(): string
    {
        return 'XX - Broken';
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function isConfigured(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function search(string $query, int $limit = 25): array
    {
        throw new RuntimeException('The broken register is unreachable.');
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function byReference(string $reference): ?array
    {
        throw new RuntimeException('The broken register is unreachable.');
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function vatNumberCheck(string $vatNumber): ?VatNumberCheck
    {
        throw new RuntimeException('The broken register is unreachable.');
    }
}
