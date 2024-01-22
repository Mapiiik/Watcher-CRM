<?php
declare(strict_types=1);

namespace Radius\Command;

use App\Command\Traits\FlashMessageHandlerTrait;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Http\ServerRequest;
use Cake\Log\Log;
use Closure;
use Radius\Controller\AccountsController;

/**
 * Update related records for accounts command.
 */
class UpdateRelatedRecordsForAccountsCommand extends Command
{
    use FlashMessageHandlerTrait;

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
        // Data to be passed
        $postData = $args->getOptions();

        // Creating an instance of the ServerRequest with the provided data
        $serverRequest = new ServerRequest([
            'environment' => [
                'REQUEST_METHOD' => 'POST',
            ],
            'params' => [
                'plugin' => 'Radius',
            ],
            'post' => $postData,
        ]);

        // Creating an instance of the AccountsController
        $controller = new AccountsController($serverRequest);

        // Getting the Controller instance from the appropriate Shell
        $controller->setRequest($serverRequest);

        // Disable FormProtection Component
        $controller->FormProtection->setConfig('validate', false);

        // Disable automatic action rendering
        $controller->disableAutoRender();

        // Perform the startup process for Controller
        $controller->startupProcess();

        // Calling the action in the Controller
        $controller->invokeAction(Closure::fromCallable([$controller, 'updateRelatedRecordsForAllAccounts']), []);

        // Getting the response from the Controller after the action is invoked
        $response = $controller->getResponse();

        // Processing the response
        if ($response->getStatusCode() === 200) {
            $this->handleFlashMessages($controller->getRequest(), $io);
        } else {
            Log::error('Update of related records for accounts failed. Please try again.');
            $io->error(__d('radius', 'Update of related records for accounts failed. Please try again.'));
        }
    }

    /**
     * @inheritDoc
     */
    public static function defaultName(): string
    {
        return 'radius accounts update_related_records';
    }
}
