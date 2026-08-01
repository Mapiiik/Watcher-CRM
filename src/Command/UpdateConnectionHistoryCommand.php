<?php
declare(strict_types=1);

namespace App\Command;

use App\Command\Traits\MessageHandlerTrait;
use App\Service\ConnectionHistory\ConnectionHistoryUpdater;
use App\Service\ConnectionHistory\SourceInterface;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;
use Cake\Log\Log;
use Cake\Mailer\Mailer;
use Override;
use Throwable;

/**
 * Update connection history command.
 *
 * Meant to run daily. The sources it reads keep only a few months of accounting
 * data, so a run that never happens is a gap that cannot be filled in later,
 * whereas a run that happens twice changes nothing.
 */
class UpdateConnectionHistoryCommand extends Command
{
    use MessageHandlerTrait;

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
            'Records where accounts have been connected, from every configured source.',
        ));

        $parser->addOption('source', [
            'help' => __('Read only the named source instead of all of them.'),
            'required' => false,
        ]);

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
            $sources = $this->buildSources((string)$args->getOption('source'));

            if ($sources === []) {
                $io->warning(__('No connection history source is configured.'));

                return static::CODE_SUCCESS;
            }

            $updater = new ConnectionHistoryUpdater();
            $summary = $updater->update($sources);

            $this->handleMessages($updater->Messages->getMessages(), $io);

            $io->helper('Table')->output([
                [__('Accounts'), __('Opened'), __('By account change'), __('Extended'), __('Enriched')],
                [
                    (string)$summary->accounts,
                    (string)$summary->opened,
                    (string)$summary->openedByAccountChange,
                    (string)$summary->extended,
                    (string)$summary->enriched,
                ],
            ]);

            // a source being down is not a failure of the run, but leaving it
            // unnoticed until the accounting data ages out would be
            if ($summary->unavailableSources !== []) {
                $io->warning(__(
                    'These sources could not be reached and their history is now behind: {0}',
                    implode(', ', $summary->unavailableSources),
                ));

                return static::CODE_ERROR;
            }

            return static::CODE_SUCCESS;
        } catch (Throwable $e) {
            Log::error('Error during connection history update: ' . $e->getMessage());

            $io->error(__(
                'Error during connection history update: {0}',
                $e->getMessage(),
            ));

            // notify by email (if it fails, let it crash)
            $errorMailer = new Mailer('default');

            foreach (explode(' ', (string)env('REPORT_EMAILS')) as $email) {
                $errorMailer->addTo($email);
            }

            $errorMailer->setSubject(__('Connection history update failed'));

            $errorMailer->deliver(__(
                'Connection history update failed.' . PHP_EOL . PHP_EOL
                . 'Error: {0}',
                [$e->getMessage()],
            ));

            unset($errorMailer);

            return static::CODE_ERROR;
        }
    }

    /**
     * Build the configured sources.
     *
     * @param string $only Read only this source, empty for all of them.
     * @return array<\App\Service\ConnectionHistory\SourceInterface>
     */
    protected function buildSources(string $only): array
    {
        $sources = [];

        foreach ((array)Configure::read('ConnectionHistory.sources', []) as $className) {
            if (!is_string($className) || !class_exists($className)) {
                Log::warning('Configured connection history source does not exist: ' . (string)$className);

                continue;
            }

            $source = new $className();

            if (!$source instanceof SourceInterface) {
                Log::warning('Configured connection history source is not a source: ' . $className);

                continue;
            }

            if ($only !== '' && $source->getSource()->value !== $only) {
                continue;
            }

            $sources[] = $source;
        }

        return $sources;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public static function defaultName(): string
    {
        return 'connection_history update';
    }
}
