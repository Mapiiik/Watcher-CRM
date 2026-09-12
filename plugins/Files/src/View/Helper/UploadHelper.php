<?php
declare(strict_types=1);

namespace Files\View\Helper;

use Cake\View\Helper;

/**
 * What a form that takes files has to say about the server it is sending them to.
 *
 * The limit is not a thing HTML has an attribute for - `multiple` takes as many as are chosen,
 * and PHP then quietly keeps the first `max_file_uploads` of them. So the form carries the number
 * and a small script counts against it while somebody is still choosing.
 *
 * A helper because the wording belongs to whoever is asking, and the number belongs to the
 * server. Neither belongs in a template, and both are wanted at every form that takes files.
 *
 * @property \Cake\View\Helper\HtmlHelper $Html
 * @extends \Cake\View\Helper<\App\View\AppView>
 */
class UploadHelper extends Helper
{
    /**
     * Helpers used by this one.
     *
     * @var array<string>
     */
    protected array $helpers = ['Html'];

    /**
     * Whether this page has asked for the script already.
     */
    private bool $loaded = false;

    /**
     * Fetches the counting, for a page about to draw a form that takes files.
     *
     * A page that forgets loses the warning while choosing and nothing else: the server still
     * reports a batch it had to cut.
     *
     * @return void
     */
    public function load(): void
    {
        if ($this->loaded) {
            return;
        }

        $this->loaded = true;

        $this->Html->script('Files.uploads', ['block' => true]);
    }

    /**
     * What to put on the form so the counting has something to count against.
     *
     * @return array<string, string> Attributes for the form.
     */
    public function atMost(): array
    {
        $limit = self::limit();

        if ($limit < 1) {
            return [];
        }

        return [
            'data-files-at-most' => (string)$limit,
            // The count is put in where the script finds the mark, so that the sentence is
            // written here in words rather than glued together out of pieces over there.
            'data-files-too-many' => __d(
                'files',
                '%d files are chosen and this server takes at most {0} at once. Choose fewer, or'
                . ' send them in more than one batch.',
                $limit,
            ),
        ];
    }

    /**
     * How many files the server will take out of one request.
     *
     * @return int
     */
    public static function limit(): int
    {
        return (int)ini_get('max_file_uploads');
    }
}
