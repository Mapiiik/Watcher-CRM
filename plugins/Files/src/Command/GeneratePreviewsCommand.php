<?php
declare(strict_types=1);

namespace Files\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Files\Service\Previews;
use Override;

/**
 * GeneratePreviews command.
 *
 * The pictures of what is filed, made ahead of anybody asking for them.
 *
 * They are made on demand as well, so this changes nothing about what can be seen - only when the
 * waiting happens. Making them as the files arrive would put it on whoever uploaded them, and a
 * round of thirty photographs is thirty conversions to sit through before the form comes back.
 * From cron it falls on nobody.
 *
 * Everything that has none yet, oldest content first, up to a limit - so that a first run over a
 * store that predates any of this takes several passes instead of one long one. What has no
 * picture to be made of is passed over without a word: a spreadsheet has none and never will, and
 * saying so every night would teach the reader to stop looking.
 */
class GeneratePreviewsCommand extends Command
{
    /**
     * How many to make in one run, unless told otherwise. Enough that an hourly run keeps up with
     * anything a person can upload, and few enough that a first run over an old store does not
     * hold the machine all night.
     *
     * @var int
     */
    private const AT_A_TIME = 200;

    /**
     * The name of this command.
     *
     * @var string
     */
    protected string $name = 'generate_previews';

    /**
     * Get the default command name.
     *
     * @return string
     */
    #[Override]
    public static function defaultName(): string
    {
        return 'generate_previews';
    }

    /**
     * Get the command description.
     *
     * @return string
     */
    #[Override]
    public static function getDescription(): string
    {
        return 'Makes the pictures of what is filed, before anybody asks for them.';
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

        $parser->setDescription(__d(
            'files',
            'Makes the pictures of what is filed, so that nobody has to wait for them. They are'
            . ' made on demand too, so this only decides who does the waiting.',
        ));

        $parser->addOption('limit', [
            'help' => __d('files', 'How many to make before stopping. Defaults to {0}.', self::AT_A_TIME),
            'default' => (string)self::AT_A_TIME,
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
        $limit = max(1, (int)$args->getOption('limit'));
        $previews = new Previews();

        /** @var iterable<\Files\Model\Entity\File> $rows */
        $rows = $this->fetchTable('Files.Files')
            ->find()
            ->orderByAsc('Files.created')
            ->all();

        $made = 0;
        $refused = 0;

        foreach ($rows as $file) {
            if ($made >= $limit) {
                break;
            }

            if (!Previews::generates($file->mime_type)) {
                continue;
            }

            // Both sizes together, because they are asked for from the same places and making
            // one of them means the content has already been read and decoded.
            $wanted = false;
            foreach ([Previews::THUMBNAIL, Previews::PREVIEW] as $size) {
                if (is_file(Previews::pathFor((string)$file->id, $size))) {
                    continue;
                }

                $wanted = true;

                if ($previews->generate($file, $size) === null) {
                    $refused++;
                }
            }

            if ($wanted) {
                $made++;
            }
        }

        $io->out(__d('files', '{0} made.', $made));

        if ($refused > 0) {
            $io->warning(__d('files', '{0} could not be made. The log says why.', $refused));
        }

        return static::CODE_SUCCESS;
    }
}
