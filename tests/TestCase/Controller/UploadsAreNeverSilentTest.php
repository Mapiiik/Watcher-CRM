<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Every action that takes uploads also asks whether the server kept them all.
 *
 * PHP stops at `max_file_uploads` and says nothing: the files beyond it are gone before any of
 * this is running, so there is no error to catch and nothing to report on. The only thing that
 * can be noticed is the count, and an action that forgets to look goes back to filing part of a
 * batch and calling it a success - which is exactly the mistake this is here about.
 *
 * Read off the sources rather than by posting two hundred files at a request: what is wanted is
 * that no way in is left out, and a rebuilt image would be needed to make the limit small enough
 * to reach in a test.
 */
class UploadsAreNeverSilentTest extends TestCase
{
    /**
     * @return void
     */
    public function testEveryActionThatTakesUploadsAsksWhetherAnyWereLost(): void
    {
        $taking = [];

        foreach ($this->controllers() as $path) {
            $source = (string)file_get_contents($path);
            if (!str_contains($source, 'getUploadedFiles()')) {
                continue;
            }

            $taking[] = $path;

            $this->assertSame(
                substr_count($source, 'getUploadedFiles()['),
                substr_count($source, 'ProposalPapers::cutShort('),
                substr($path, strlen(ROOT)) . ' takes uploads without asking whether any were lost',
            );
        }

        $this->assertNotEmpty($taking, 'there is at least one way of uploading to check');
    }

    /**
     * Every controller of the application.
     *
     * @return list<string>
     */
    private function controllers(): array
    {
        $found = [];

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(ROOT . DS . 'src' . DS . 'Controller'),
        );

        foreach ($files as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $found[] = $file->getPathname();
            }
        }

        sort($found);

        return $found;
    }
}
