<?php
declare(strict_types=1);

namespace App\Check;

/**
 * What a family of checks was asked about, and how widely.
 *
 * Every check is given the same three answers, and they travel together: a check asked about one
 * contract while keeping to what is running is answering one question, not three. Passing them as
 * one thing is what lets a registry hand the same scope to twenty checks without writing it out
 * twenty times - and what makes a fourth question, if one is ever asked, a change in one place
 * rather than in every check there is.
 *
 * The three readings themselves belong to {@see \App\Check\AbstractCheck}: whether a check can
 * answer what it was given, and how its query is narrowed, is the check's business rather than
 * the scope's.
 */
final readonly class CheckScope
{
    /**
     * @param bool $ignore_inactive Whether the checks keep to what is running. Each applies it to
     *   its own subject, so that the answer is about the record being reported rather than about
     *   something else its contract happens to have. Off, they report the history as well, which
     *   is what putting the history straight needs and what daily work does not.
     * @param string|null $contract_id The one contract being asked about, where there is one.
     *   This is what lets a contract show its own findings.
     * @param string|null $customer_id The one customer being asked about, where there is one.
     *   This is what lets a customer show the findings on every contract they hold.
     */
    public function __construct(
        public bool $ignore_inactive = true,
        public ?string $contract_id = null,
        public ?string $customer_id = null,
    ) {
    }
}
