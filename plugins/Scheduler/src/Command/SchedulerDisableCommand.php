<?php
declare(strict_types=1);

namespace Scheduler\Command;

use Override;

/**
 * Switches rows of the schedule off.
 */
class SchedulerDisableCommand extends SchedulerEnableCommand
{
    /**
     * @return string
     */
    #[Override]
    public static function defaultName(): string
    {
        return 'scheduler disable';
    }

    /**
     * @return string
     */
    #[Override]
    public static function getDescription(): string
    {
        return 'Switch off the rows of the schedule of the given names.';
    }

    /**
     * @inheritDoc
     */
    #[Override]
    protected function enables(): bool
    {
        return false;
    }
}
