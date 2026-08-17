<?php
declare(strict_types=1);

namespace App\Addresses\Check;

use Cake\ORM\Query\SelectQuery;

/**
 * One thing that can be wrong with the addresses on record.
 *
 * A check owns its query and its own way of being listed. The dashboard card asks it how
 * many records it found; the overview lists them. Adding a check is a class here and a
 * template beside it - neither the card nor the overview learns anything about it.
 */
interface AddressCheckInterface
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
     * The element under `templates/element/AddressChecks/` that lists its records.
     *
     * @return string
     */
    public function template(): string;

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
