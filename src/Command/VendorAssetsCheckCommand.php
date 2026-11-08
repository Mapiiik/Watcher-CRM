<?php
declare(strict_types=1);

namespace App\Command;

use App\Service\VendorAssets;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Override;
use RuntimeException;

/**
 * Compares the libraries in `webroot/vendor` with where they come from, and tells what is newer.
 */
class VendorAssetsCheckCommand extends Command
{
    /**
     * @return string
     */
    #[Override]
    public static function defaultName(): string
    {
        return 'vendor_assets check';
    }

    /**
     * @return string
     */
    #[Override]
    public static function getDescription(): string
    {
        return 'Compare the libraries in webroot/vendor with where they come from, and tell what is newer.';
    }

    /**
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io.
     * @return int Success when every folder is what its source says.
     */
    #[Override]
    public function execute(Arguments $args, ConsoleIo $io): int
    {
        $assets = new VendorAssets();
        $result = static::CODE_SUCCESS;

        foreach ($assets->folders() as $name => $folder) {
            $line = sprintf('%-46s ', $name);

            try {
                $state = $assets->compare($folder);
            } catch (RuntimeException $exception) {
                $io->out($line . 'NOT CHECKED: ' . $exception->getMessage());
                $result = static::CODE_ERROR;

                continue;
            }

            $line .= sprintf('%-14s', $state['version'] ?? '-');
            $line .= $state['differ'] === [] ? ' as the source' : ' DIFFERS: ' . implode(', ', $state['differ']);

            if ($state['newest'] !== null && $state['newest'] !== $state['version']) {
                $line .= sprintf(', %s is out', $state['newest']);
                $line .= $state['pinnedBy'] === null ? '' : sprintf(' (kept for %s)', $state['pinnedBy']);
            }

            $io->out($line);
            $result = $state['differ'] === [] ? $result : static::CODE_ERROR;
        }

        return $result;
    }
}
