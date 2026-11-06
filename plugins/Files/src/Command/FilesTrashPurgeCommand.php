<?php
declare(strict_types=1);

namespace Files\Command;

use App\Service\ErrorReport;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Files\Service\Trash;
use Override;

/**
 * FilesTrashPurge command.
 *
 * Throws away what has been in the trash for long enough, and the content nothing wants any more.
 * Nothing is lost without it, the trash only grows.
 */
class FilesTrashPurgeCommand extends Command
{
    /**
     * How long the trash keeps what was removed.
     *
     * @var int
     */
    private const DAYS = 30;

    /**
     * The name of this command.
     *
     * @var string
     */
    protected string $name = 'files_trash_purge';

    /**
     * Get the default command name.
     *
     * @return string
     */
    #[Override]
    public static function defaultName(): string
    {
        return 'files_trash_purge';
    }

    /**
     * Get the command description.
     *
     * @return string
     */
    #[Override]
    public static function getDescription(): string
    {
        return 'Throws away what has been in the trash for long enough.';
    }

    /**
     * Hook method for defining this command's option parser.
     *
     * @see https://book.cakephp.org/5/en/console-commands/commands.html#defining-arguments-and-options
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
     * @return \Cake\Console\ConsoleOptionParser The built parser.
     */
    #[Override]
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser = parent::buildOptionParser($parser);

        $parser->setDescription(__d('files', 'Throws away what has been in the trash for long enough.'));

        $parser->addOption('days', [
            'help' => __d('files', 'How many days the trash keeps what was removed.'),
            'default' => (string)self::DAYS,
        ]);

        return $parser;
    }

    /**
     * Implement this method with your command's logic.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int|null The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $days = (string)$args->getOption('days');

        if (!ctype_digit($days) || (int)$days < 1) {
            $io->error(__d('files', 'The number of days has to be a whole number of at least 1.'));

            return static::CODE_ERROR;
        }

        $result = (new Trash())->purge((int)$days);

        $io->out(__d('files', '{0} thrown away.', $result['deleted']));

        if ($result['failed'] === 0) {
            return static::CODE_SUCCESS;
        }

        $said = __d('files', 'The trash could not let go of {0} of what had expired.', $result['failed']);

        $io->error($said);
        ErrorReport::send(__d('files', 'Emptying the trash failed'), $said);

        return static::CODE_ERROR;
    }
}
