<?php
declare(strict_types=1);

namespace Scheduler\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use JsonException;
use Override;
use Scheduler\Service\Schedule;

/**
 * Writes the schedule of an installation from a file, with every row left off.
 */
class SchedulerImportCommand extends Command
{
    /**
     * @return string
     */
    #[Override]
    public static function defaultName(): string
    {
        return 'scheduler import';
    }

    /**
     * @return string
     */
    #[Override]
    public static function getDescription(): string
    {
        return 'Write rows of the schedule from a JSON file, without switching any of them on.';
    }

    /**
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined.
     * @return \Cake\Console\ConsoleOptionParser
     */
    #[Override]
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        return parent::buildOptionParser($parser)
            ->addArgument('file', [
                'help' => __d('scheduler', 'The JSON file, rows described as in config/scheduler.php.'),
                'required' => true,
            ]);
    }

    /**
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io.
     * @return int
     */
    #[Override]
    public function execute(Arguments $args, ConsoleIo $io): int
    {
        $file = (string)$args->getArgument('file');
        $content = is_file($file) ? file_get_contents($file) : false;

        if ($content === false) {
            $io->error(__d('scheduler', 'The file {0} cannot be read.', $file));

            return static::CODE_ERROR;
        }

        try {
            $descriptions = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            $io->error(__d('scheduler', 'The file {0} is not JSON: {1}', $file, $e->getMessage()));

            return static::CODE_ERROR;
        }

        $result = (new Schedule())->import($descriptions);

        foreach ($result['added'] as $name) {
            $io->out(__d('scheduler', 'Added to the schedule: {0}', $name));
        }
        foreach ($result['changed'] as $name) {
            $io->out(__d('scheduler', 'Changed in the schedule: {0}', $name));
        }
        foreach ($result['skipped'] as $name) {
            $io->warning(__d('scheduler', 'Left alone, it is switched on: {0}', $name));
        }

        return static::CODE_SUCCESS;
    }
}
