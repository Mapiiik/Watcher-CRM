<?php
declare(strict_types=1);

namespace App\Check;

use Cake\ORM\Query\SelectQuery;

/**
 * One thing that can be wrong with the records on file.
 *
 * A check owns its query and its own way of being listed. The dashboard card asks it how
 * many records it found; the overview lists them. Adding a check is a class beside the
 * others and a template beside theirs - neither the card nor the overview learns anything
 * about it.
 *
 * What a family of checks is about - addresses, contracts, customers - is settled by its
 * registry rather than here, so the card and the overview are written once.
 */
interface CheckInterface
{
    /**
     * What this check is called in URLs, template names and anchors.
     *
     * @return string
     */
    public function id(): string;

    /**
     * The heading this check is listed under.
     *
     * @return string
     */
    public function title(): string;

    /**
     * What it says when the check comes back empty.
     *
     * @return string
     */
    public function emptyMessage(): string;

    /**
     * The records that fail the check.
     *
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    public function find(): SelectQuery;

    /**
     * How many records fail it.
     *
     * @return int
     */
    public function count(): int;

    /**
     * The element that lists its records, named within the directory its family keeps.
     *
     * @return string
     */
    public function template(): string;

    /**
     * That same element, named the way a template asks for one.
     *
     * A page showing findings from more than one family - a customer's card carries both what
     * is wrong with their contracts and what is wrong with their addresses - cannot know which
     * directory to look in, so the check says.
     *
     * @return string
     */
    public function element(): string;

    /**
     * Whether the check can answer the question it was given.
     *
     * A check asked about one record it has no way to narrow to would answer about the whole
     * file instead, so a registry asked about a record only offers the checks that can speak
     * about it.
     *
     * @return bool
     */
    public function answersWhatWasAsked(): bool;

    /**
     * Whether the dashboard card counts this one.
     *
     * A check the card would only ever shout about belongs in the overview alone.
     *
     * @return bool
     */
    public function onDashboard(): bool;

    /**
     * Whether the overview leaves this one switched off until it is asked for.
     *
     * This is for what is worth looking at rather than worth fixing - findings that are not
     * faults, and would bury the ones that are.
     *
     * @return bool
     */
    public function optional(): bool;
}
