<?php
declare(strict_types=1);

namespace App\Command;

use App\CustomerPortal\Passwords;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Log\Log;
use Override;

/**
 * Wraps the customer portal passwords still stored as bare SHA-1 in Argon2id.
 *
 * A bare SHA-1 without salt is cracked offline in no time, a wrapped one is not, and the customer
 * signs in with the same password as before (see `Passwords`). Safe to run again: what is
 * wrapped already is left alone, and a password changed meanwhile is not overwritten.
 */
class WrapLegacyLoginHashesCommand extends Command
{
    private const BATCH = 500;

    /**
     * Hook method for defining this command's option parser.
     *
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
     * @return \Cake\Console\ConsoleOptionParser The built parser.
     */
    #[Override]
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser = parent::buildOptionParser($parser);

        $parser->setDescription(__(
            'Wraps the customer portal passwords still stored as bare SHA-1 in Argon2id.',
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
        $logins = $this->fetchTable('Logins');
        $wrapped = 0;
        $lastId = null;

        do {
            $query = $logins->find()
                ->select(['id', 'password'])
                ->orderByAsc('id')
                ->limit(self::BATCH)
                ->disableHydration();
            if ($lastId !== null) {
                $query->where(['id >' => $lastId]);
            }
            $rows = $query->all()->toList();

            foreach ($rows as $row) {
                $lastId = $row['id'];
                if (!is_string($row['password']) || !Passwords::isLegacy($row['password'])) {
                    continue;
                }
                // past the entity, whose setter would hash the hash, and with the old value as the
                // condition, so that a password set meanwhile stays
                $wrapped += $logins->updateAll(
                    ['password' => Passwords::wrap($row['password'])],
                    ['id' => $row['id'], 'password' => $row['password']],
                );
            }
            $io->verbose(sprintf('%d wrapped so far', $wrapped));
            $more = count($rows) === self::BATCH;
        } while ($more);

        Log::info(sprintf('Customer portal: %d passwords stored as bare SHA-1 wrapped in Argon2id', $wrapped));
        $io->out(__('Wrapped: {0}', $wrapped));

        return static::CODE_SUCCESS;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public static function defaultName(): string
    {
        return 'logins wrap_legacy_hashes';
    }
}
