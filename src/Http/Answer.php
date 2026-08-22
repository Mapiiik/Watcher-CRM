<?php
declare(strict_types=1);

namespace App\Http;

use Closure;
use RuntimeException;

/**
 * What came of asking another service something.
 *
 * Every client this application talks out through hands one of these back, so that a caller never
 * has to remember which client throws and which returns nothing. There is one thing a client
 * cannot know - whether it is being called by a command that should stop when the other side is
 * down, or by a page that must draw itself anyway - so it does not decide. It reports, and the
 * caller says what that is worth: {@see self::orFail()} where the run should end, {@see self::ok()}
 * where the page goes on without the answer.
 *
 * Three states, not two. An installation that was never given the address of a service has not
 * failed to reach it; nobody asked. Saying so is what keeps a page from being covered in remarks
 * about a system that this installation does not have.
 *
 * What the answer carries is named, so that a caller reading it is held to what the client said it
 * would hand over. An answer that came to nothing carries null, which is why {@see $data} is only
 * ever the named thing where {@see self::ok()} says there is one - the two questions are asked
 * together, and asking the second without the first is what static analysis catches.
 *
 * An answer only ever hands its data out, never takes any in, so a reading that came to nothing
 * stands in for one of any shape - which is what lets a client return the same failure whatever
 * it was going to answer with.
 *
 * @template-covariant TData
 */
final readonly class Answer
{
    /**
     * @param mixed $data What the other side said, null where it said nothing.
     * @param string|null $failure Why the asking came to nothing, null where it did not.
     * @param bool $asked Whether anything was asked at all.
     * @phpstan-param TData|null $data
     */
    private function __construct(
        public mixed $data,
        public ?string $failure,
        public bool $asked,
    ) {
    }

    /**
     * What the other side answered.
     *
     * @template TGiven
     * @param mixed $data The answer, read into whatever shape the caller works in.
     * @return self<TGiven>
     * @phpstan-param TGiven $data
     */
    public static function of(mixed $data): self
    {
        return new self($data, null, true);
    }

    /**
     * A question that went unanswered, and why.
     *
     * @param string $failure What went wrong, in a line fit for a log.
     * @return self<never>
     */
    public static function failed(string $failure): self
    {
        /** @var self<never> $answer */
        $answer = new self(null, $failure, true);

        return $answer;
    }

    /**
     * A question nobody asked, because there is nothing configured to ask.
     *
     * @return self<never>
     */
    public static function notAsked(): self
    {
        /** @var self<never> $answer */
        $answer = new self(null, null, false);

        return $answer;
    }

    /**
     * The same non-answer another reading came to, for a caller that cannot go on without it.
     *
     * A client that asks two things in turn hands the first failure out as its own, and what it
     * was going to answer with is not what the first reading was going to answer with - so the
     * failure is passed on rather than returned, which is the whole of the difference.
     *
     * @param self<mixed> $other The reading that came to nothing.
     * @return self<never>
     */
    public static function sameFailure(self $other): self
    {
        /** @var self<never> $answer */
        $answer = new self(null, $other->failure, $other->asked);

        return $answer;
    }

    /**
     * Whether there is an answer to work with.
     *
     * @return bool
     * @phpstan-assert-if-true TData $this->data
     */
    public function ok(): bool
    {
        return $this->asked && $this->failure === null;
    }

    /**
     * Whether the other side was asked and did not answer.
     *
     * This is the one worth telling the operator about. Not having been asked is not a failure,
     * and an answer of nothing is an answer.
     *
     * @return bool
     */
    public function unanswered(): bool
    {
        return $this->asked && $this->failure !== null;
    }

    /**
     * The answer, or an exception for a caller whose run the failure should end.
     *
     * Not having been asked ends the run too: a command told to do something with a service this
     * installation was never given cannot do it, and saying nothing would look like it had.
     *
     * @param string|null $whenNotAsked What to say where nothing was asked.
     * @return mixed
     * @throws \RuntimeException
     * @phpstan-return TData
     */
    public function orFail(?string $whenNotAsked = null): mixed
    {
        if ($this->ok()) {
            return $this->data;
        }

        throw new RuntimeException(
            $this->failure ?? $whenNotAsked ?? __('The service is not configured.'),
        );
    }

    /**
     * The answer, or what to fall back on where there is none.
     *
     * @template TFallback
     * @param mixed $fallback What to hand back instead.
     * @return mixed
     * @phpstan-param TFallback $fallback
     * @phpstan-return TData|TFallback
     */
    public function or(mixed $fallback): mixed
    {
        return $this->ok() ? $this->data : $fallback;
    }

    /**
     * The same answer with its data read into something else, leaving a failure alone.
     *
     * This is how a client turns what arrived into what it hands over without writing the three
     * states out again in every method.
     *
     * @template TRead
     * @param \Closure $read How to read the data.
     * @return self<TRead>
     * @phpstan-param \Closure(TData): TRead $read
     */
    public function map(Closure $read): self
    {
        return $this->ok() ? self::of($read($this->data)) : self::sameFailure($this);
    }
}
