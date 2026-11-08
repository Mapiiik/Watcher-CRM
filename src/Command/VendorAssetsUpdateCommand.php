<?php
declare(strict_types=1);

namespace App\Command;

use App\Service\VendorAssets;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Override;
use RuntimeException;

/**
 * Replaces the files of a library in `webroot/vendor` with those of another version of its source.
 */
class VendorAssetsUpdateCommand extends Command
{
    /**
     * @return string
     */
    #[Override]
    public static function defaultName(): string
    {
        return 'vendor_assets update';
    }

    /**
     * @return string
     */
    #[Override]
    public static function getDescription(): string
    {
        return 'Replace the files of a library in webroot/vendor with those of another version of its source.';
    }

    /**
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined.
     * @return \Cake\Console\ConsoleOptionParser
     */
    #[Override]
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        return parent::buildOptionParser($parser)
            ->addArgument('folder', [
                'help' => 'The folder, as `vendor_assets check` lists it.',
                'required' => true,
            ])
            ->addArgument('version', [
                'help' => 'The version to take, the newest one when left out.',
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
        $assets = new VendorAssets();
        $name = rtrim((string)$args->getArgument('folder'), '/');

        $folder = $assets->folders()[$name] ?? null;
        if ($folder === null) {
            $io->error(sprintf('%s does not say where it comes from, `vendor_assets check` lists what does', $name));

            return static::CODE_ERROR;
        }

        try {
            $manifest = $assets->manifest($folder);
            $version = $assets->update($folder, $args->getArgument('version'));
        } catch (RuntimeException $exception) {
            $io->error($exception->getMessage());

            return static::CODE_ERROR;
        }

        $io->out(sprintf('%s is now %s, see `git diff --stat %s`.', $name, $version ?? 'what its source has', $name));

        if (isset($manifest['pinnedBy'])) {
            $io->out(sprintf('Change the addresses in %s as well.', $manifest['pinnedBy']));
        }
        if (isset($manifest['googleFonts'])) {
            $io->out('Compare `unicode-range` of the @font-face rules, which `vendor_assets check` does.');
        }
        $io->out('Take the same update into the other applications that carry the folder, `diff -rq` tells.');

        return static::CODE_SUCCESS;
    }
}
