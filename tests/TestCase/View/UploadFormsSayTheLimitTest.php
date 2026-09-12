<?php
declare(strict_types=1);

namespace App\Test\TestCase\View;

use Cake\TestSuite\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Every form that takes files says how many of them the server will take.
 *
 * HTML has no attribute for it - `multiple` takes as many as are chosen, and PHP then keeps the
 * first `max_file_uploads` of them without a word. So the form carries the number and a script
 * counts against it while somebody is still choosing, and a form that carries neither goes back
 * to letting a batch be cut in silence.
 *
 * Read off the templates rather than by rendering them: the mistake is a missing call, and the
 * markup is where a missing call shows.
 */
class UploadFormsSayTheLimitTest extends TestCase
{
    /**
     * @return void
     */
    public function testEveryFormThatTakesFilesCarriesTheLimit(): void
    {
        $forms = [];

        foreach ($this->templates() as $path) {
            $source = (string)file_get_contents($path);
            if (!$this->takesFiles($source)) {
                continue;
            }

            $forms[] = $path;
            $named = substr($path, strlen(ROOT));

            $this->assertStringContainsString(
                'Upload->load()',
                $source,
                $named . ' takes files without fetching the counting',
            );
            $this->assertStringContainsString(
                'Upload->atMost()',
                $source,
                $named . ' takes files without saying what the server takes',
            );
        }

        $this->assertNotEmpty($forms, 'there is at least one form taking files to check');
    }

    /**
     * Whether this template opens a form that carries files.
     *
     * @param string $source The template.
     * @return bool
     */
    private function takesFiles(string $source): bool
    {
        $at = strpos($source, 'Form->create(');
        while ($at !== false) {
            // The options of that one call, as far as the end of the statement.
            $end = strpos($source, '?>', $at);
            $options = substr($source, $at, $end === false ? 400 : $end - $at);

            if (str_contains($options, "'type' => 'file'")) {
                return true;
            }

            $at = strpos($source, 'Form->create(', $at + 1);
        }

        return false;
    }

    /**
     * Every template of the application.
     *
     * @return list<string>
     */
    private function templates(): array
    {
        $found = [];

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(ROOT . DS . 'templates'),
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
