<?php
declare(strict_types=1);

namespace Radius\Command;

use App\Command\Traits\MessageHandlerTrait;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Radius\Updater\AccountsUpdater;

/**
 * Update related records for accounts command.
 */
class UpdateRelatedRecordsForAccountsCommand extends Command
{
    use MessageHandlerTrait;

    /**
     * Hook method for defining this command's option parser.
     *
     * @see https://book.cakephp.org/5/en/console-commands/commands.html#defining-arguments-and-options
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
     * @return \Cake\Console\ConsoleOptionParser The built parser.
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser = parent::buildOptionParser($parser);

        $parser->addOption('state', [
            'help' => __d('radius', 'Required state of accounts.'),
            'default' => 'active',
            'choices' => [
                'all',
                'active',
                'inactive',
            ],
            'required' => false,
        ]);

        $parser->addOption('radcheck', [
            'help' => __d('radius', 'Update RADIUS Checks.'),
            'boolean' => true,
        ]);
        $parser->addOption('radreply', [
            'help' => __d('radius', 'Update RADIUS Replies.'),
            'boolean' => true,
        ]);
        $parser->addOption('radusergroup', [
            'help' => __d('radius', 'Update RADIUS User Groups.'),
            'boolean' => true,
        ]);

        $parser->addOption('reconnect_modified_accounts', [
            'help' => __d('radius', 'Reconnect Modified RADIUS Accounts.'),
            'boolean' => true,
        ]);

        return $parser;
    }

    /**
     * Implement this method with your command's logic.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int|null|void The exit code or null for success
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingNativeTypeHint
     */
    public function execute(Arguments $args, ConsoleIo $io)
    {
        // load accounts updater
        $accountsUpdater = new AccountsUpdater();

        // update related records for all accounts
        $accountsUpdater->updateRelatedRecordsForAllAccounts($args->getOptions());

        // load messages from accounts updater and generate flash messages
        $this->handleMessages($accountsUpdater->Messages->getMessages(), $io);
    }

    /**
     * @inheritDoc
     */
    public static function defaultName(): string
    {
        return 'radius accounts update_related_records';
    }
}
