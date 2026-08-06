<?php
declare(strict_types=1);

namespace App\Command;

use App\Model\Table\FulltextSearchCustomersTable;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Log\Log;
use Override;
use Throwable;

/**
 * Rebuild fulltext search customers command.
 *
 * Builds every customer's search document again from scratch. The application keeps them up to
 * date as it saves (see `FulltextSearchCustomersBehavior`), so this is the safety net rather
 * than the mechanism: it is what puts the table right after a write that never went through the ORM,
 * after a restore, and after `CUSTOMER_SERIES` has been changed - the customer number is part
 * of the document, so every document built before such a change answers to a number nobody has
 * any more.
 *
 * Costs about 226 ms over 7500 customers and is safe to run at any time - it writes the same
 * documents the application would have written, so a run that fails changes nothing and the
 * next one puts it right.
 */
class RebuildFulltextSearchCustomersCommand extends Command
{
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

        $parser->setDescription(__(
            'Builds the document every customer is found by in the advanced search again.',
        ));

        return $parser;
    }

    /**
     * Implement this method with your command's logic.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int|null|void The exit code or null for success
     */
    #[Override]
    public function execute(Arguments $args, ConsoleIo $io)
    {
        try {
            $fulltextSearch = $this->fetchTable(FulltextSearchCustomersTable::class);

            // measured rather than read off the clock: the clock is fixed under the test runner
            $startedAt = microtime(true);
            $documents = $fulltextSearch->rebuild();

            $io->helper('Table')->output([
                [__('Documents'), __('Took')],
                [
                    (string)$documents,
                    sprintf('%.0f ms', (microtime(true) - $startedAt) * 1000),
                ],
            ]);

            return static::CODE_SUCCESS;
        } catch (Throwable $e) {
            Log::error('Error during the customers fulltext search rebuild: ' . $e->getMessage());

            $io->error(__(
                'Error during the customers fulltext search rebuild: {0}',
                $e->getMessage(),
            ));

            return static::CODE_ERROR;
        }
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public static function defaultName(): string
    {
        return 'fulltext_search_customers rebuild';
    }
}
