<?php
declare(strict_types=1);

namespace App\Test\TestCase\View\Cell;

use Cake\TestSuite\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Every page that draws the documents table also fetches the viewer that opens them.
 *
 * The table is a cell, and a cell renders in a view of its own - what it puts in a block, the
 * layout never reads. So the page has to ask, and a page that forgets loses the overlay without
 * anything breaking, which is exactly the kind of mistake nobody notices.
 *
 * Read off the templates rather than by rendering them: the pages are many and the mistake is in
 * the markup, so the markup is what is asked.
 */
class DocumentsViewerTest extends TestCase
{
    /**
     * @return void
     */
    public function testEveryPageThatDrawsDocumentsFetchesTheViewer(): void
    {
        $drawn = [];

        foreach ($this->templates() as $path) {
            $source = (string)file_get_contents($path);
            if (!str_contains($source, "'Documents',")) {
                continue;
            }

            $drawn[] = $path;

            $this->assertStringContainsString(
                'Preview->load()',
                $source,
                substr($path, strlen(ROOT)) . ' draws documents without asking for the viewer',
            );
        }

        // A guard on the guard: a change of shape that stopped finding the pages would otherwise
        // leave this passing over nothing at all.
        $this->assertGreaterThanOrEqual(9, count($drawn), 'the pages that draw documents were found');
    }

    /**
     * Every page template, leaving out the one the table itself lives in.
     *
     * @return iterable<string>
     */
    private function templates(): iterable
    {
        $root = ROOT . DS . 'templates';
        $found = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));

        foreach ($found as $file) {
            /** @var SplFileInfo $file */
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $path = $file->getPathname();
            if (str_contains($path, 'cell' . DS . 'Documents') || str_contains($path, 'layout')) {
                continue;
            }

            yield $path;
        }
    }
}
