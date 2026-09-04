<?php
declare(strict_types=1);

namespace App\Contracts\Proposal;

use InvalidArgumentException;

/**
 * What the operator confirmed against the readiness checks.
 *
 * These questions used to be asked at every printing and the answers thrown away, so a paper
 * reprinted a year later asked again about equipment that had since been handed back. They belong
 * to the moment the proposal is drawn up: asked once, answered once, and kept.
 *
 * An unanswered question is not the same as one answered "no" - the first blocks saving, the
 * second is a confirmation that something is deliberately absent.
 */
final class ProposalConfirmations
{
    /**
     * The customer has equipment of their own, so none needs to be lent.
     */
    public const OWN_EQUIPMENT = 'own_equipment';

    /**
     * The customer uses no IP addresses.
     */
    public const NO_IP_ADDRESSES = 'does_not_use_ip_addresses';

    /**
     * The customer uses no RADIUS account.
     */
    public const NO_RADIUS = 'does_not_use_radius';

    /**
     * The version ends on a given day and the paper is meant to be a fixed-term contract.
     */
    public const FIXED_TERM = 'fixed_term';

    /**
     * Every question that may be put to the operator.
     *
     * @var array<string>
     */
    public const QUESTIONS = [
        self::OWN_EQUIPMENT,
        self::NO_IP_ADDRESSES,
        self::NO_RADIUS,
        self::FIXED_TERM,
    ];

    /**
     * What to call one of these where somebody is reading rather than answering it.
     *
     * @param string $question Which question.
     * @return string
     */
    public static function label(string $question): string
    {
        return match ($question) {
            self::OWN_EQUIPMENT => __('The customer has equipment of their own'),
            self::NO_IP_ADDRESSES => __('The customer uses no IP addresses'),
            self::NO_RADIUS => __('The customer uses no RADIUS account'),
            self::FIXED_TERM => __('A fixed-term contract, the obligation running to the end of it'),
            default => $question,
        };
    }

    /**
     * @param array<string, bool> $answers Only the questions that were answered.
     */
    private function __construct(private readonly array $answers)
    {
    }

    /**
     * Nothing answered yet.
     *
     * @return self
     */
    public static function none(): self
    {
        return new self([]);
    }

    /**
     * Whether the given question was answered yes.
     *
     * @param string $question Which question.
     * @return bool
     */
    public function confirms(string $question): bool
    {
        return ($this->answers[$question] ?? false) === true;
    }

    /**
     * Which of the given questions still has no answer.
     *
     * @param array<string> $asked The questions this proposal has to answer.
     * @return array<string>
     */
    public function unanswered(array $asked): array
    {
        return array_values(array_filter(
            $asked,
            fn(string $question): bool => !$this->confirms($question),
        ));
    }

    /**
     * The same answers with one more given.
     *
     * @param string $question Which question.
     * @param bool $answer What was answered.
     * @return self
     */
    public function with(string $question, bool $answer): self
    {
        self::assertKnown($question);

        return new self([$question => $answer] + $this->answers);
    }

    /**
     * Reads the answers back from the stored shape.
     *
     * @param array<string, mixed> $stored The stored answers.
     * @return self
     * @throws \InvalidArgumentException When a question is not one that is ever asked.
     */
    public static function fromArray(array $stored): self
    {
        $answers = [];

        foreach ($stored as $question => $answer) {
            self::assertKnown((string)$question);

            $answers[(string)$question] = (bool)$answer;
        }

        return new self($answers);
    }

    /**
     * Writes the answers out in the shape they are stored in.
     *
     * @return array<string, bool>
     */
    public function toArray(): array
    {
        return $this->answers;
    }

    /**
     * @param string $question Which question.
     * @return void
     * @throws \InvalidArgumentException When it is not one that is ever asked.
     */
    private static function assertKnown(string $question): void
    {
        if (!in_array($question, self::QUESTIONS, true)) {
            throw new InvalidArgumentException(sprintf('Nobody asks about %s.', $question));
        }
    }
}
