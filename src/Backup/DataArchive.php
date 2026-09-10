<?php
declare(strict_types=1);

namespace App\Backup;

use RuntimeException;

/**
 * Everything under the data root, packed and unpacked.
 *
 * Not compressed: what is kept here is PDFs, photographs and scans, all of them compressed
 * already, so packing them again would cost the time and save nothing.
 *
 * Unpacking writes over what it finds, which is what restoring means. For the stored documents it
 * is also a no-op either way - their paths are derived from what is in them, so the same path
 * always holds the same bytes.
 */
final class DataArchive
{
    /**
     * What the archive is called inside the backup, and what packs it.
     */
    public const FILENAME = 'data.tar';
    public const PROGRAM = 'tar';

    /**
     * @param string $root The data root, wherever the deployment has pointed it.
     */
    public function __construct(private string $root)
    {
    }

    /**
     * What `tar` is told to pack it, apart from the program itself.
     *
     * @param string $file Where the archive is written.
     * @return list<string>
     */
    public function packArguments(string $file): array
    {
        return ['--create', '--file=' . $file, '--directory=' . $this->root, '.'];
    }

    /**
     * What `tar` is told to unpack it, apart from the program itself.
     *
     * @param string $file The archive to read.
     * @return list<string>
     */
    public function unpackArguments(string $file): array
    {
        return ['--extract', '--file=' . $file, '--directory=' . $this->root];
    }

    /**
     * Packs the data root.
     *
     * @param string $file Where it goes.
     * @return string Whatever the program had to say.
     * @throws \RuntimeException When the root is not there or the program does not succeed.
     */
    public function pack(string $file): string
    {
        if (!is_dir($this->root)) {
            throw new RuntimeException(sprintf('There is no data root at %s.', $this->root));
        }

        return Process::run([$this->program(), ...$this->packArguments($file)]);
    }

    /**
     * Unpacks it again.
     *
     * @param string $file The archive to read.
     * @return string Whatever the program had to say.
     * @throws \RuntimeException When the root cannot be made or the program does not succeed.
     */
    public function unpack(string $file): string
    {
        if (!is_dir($this->root) && !mkdir($this->root, 0770, true) && !is_dir($this->root)) {
            throw new RuntimeException(sprintf('Could not make the data root at %s.', $this->root));
        }

        return Process::run([$this->program(), ...$this->unpackArguments($file)]);
    }

    /**
     * Where `tar` is.
     *
     * @return string
     * @throws \RuntimeException When it is not on the path.
     */
    private function program(): string
    {
        $located = Process::locate(self::PROGRAM);

        if ($located === null) {
            throw new RuntimeException(sprintf('%s is not installed here.', self::PROGRAM));
        }

        return $located;
    }
}
