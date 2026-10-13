<?php
declare(strict_types=1);

namespace Access\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;
use Cake\Utility\Security;
use Override;

/**
 * Adds an admin account, such as the first one of a new installation.
 *
 * The password is made up here and shown once, so no installation starts with one somebody knows.
 */
class AddAdminCommand extends Command
{
    /**
     * @inheritDoc
     */
    #[Override]
    public static function defaultName(): string
    {
        return 'access add_admin';
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public static function getDescription(): string
    {
        return 'Add an admin account, with a password made up for it.';
    }

    /**
     * @inheritDoc
     */
    #[Override]
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        return $parser
            ->setDescription(static::getDescription())
            ->addArgument('username', ['help' => 'What the admin signs in with.', 'required' => true])
            ->addArgument('email', ['help' => 'Where a forgotten password is sent.', 'required' => true]);
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function execute(Arguments $args, ConsoleIo $io): int
    {
        $users = $this->fetchTable((string)Configure::read('Users.table', 'Users'));
        $password = Security::randomString(20);
        $user = $users->newEntity([
            'username' => $args->getArgument('username'),
            'email' => $args->getArgument('email'),
            'password' => $password,
            'role' => 'admin',
            'active' => true,
        ], ['accessibleFields' => ['password' => true]]);

        if (!$users->save($user)) {
            foreach ($user->getErrors() as $field => $errors) {
                $io->err(sprintf('%s: %s', $field, implode(' ', (array)$errors)));
            }

            return static::CODE_ERROR;
        }

        $io->out(sprintf('The admin %s can sign in with the password %s', $user->get('username'), $password));
        $io->out('It is shown only now. Change it after signing in.');

        return static::CODE_SUCCESS;
    }
}
