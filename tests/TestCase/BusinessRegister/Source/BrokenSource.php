<?php
declare(strict_types=1);

namespace App\Test\TestCase\BusinessRegister\Source;

use App\BusinessRegister\Source\SourceInterface;
use App\BusinessRegister\Source\VatNumberCheckInterface;
use App\Http\Answer;
use Override;

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
    public function search(string $query, int $limit = 25): Answer
    {
        return Answer::failed('The broken register is unreachable.');
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function byReference(string $reference): Answer
    {
        return Answer::failed('The broken register is unreachable.');
    }

    /**
     * @return \App\Http\Answer<\App\BusinessRegister\VatNumberCheck|null>
     * @inheritDoc
     */
    #[Override]
    public function vatNumberCheck(string $vatNumber): Answer
    {
        return Answer::failed('The broken register is unreachable.');
    }
}
