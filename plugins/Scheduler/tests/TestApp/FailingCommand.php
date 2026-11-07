<?php
declare(strict_types=1);

namespace Scheduler\Test\TestApp;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Override;

/**
 * A job that always fails, the way a command says so.
 */
class FailingCommand extends Command
{
    /**
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io.
     * @return int
     */
    #[Override]
    public function execute(Arguments $args, ConsoleIo $io): int
    {
        return static::CODE_ERROR;
    }
}
