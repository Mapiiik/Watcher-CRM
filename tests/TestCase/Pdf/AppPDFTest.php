<?php
declare(strict_types=1);

namespace App\Test\TestCase\Pdf;

use App\Pdf\AppPDF;
use Cake\Cache\Cache;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use Settings\Service\SettingsService;
use Settings\Utility\Settings;

/**
 * App\Pdf\AppPDF Test Case
 *
 * Every page of a paper says at its foot which paper it is and which page of how many, so that a
 * page that has come loose from a stack still says where it belongs.
 */
#[CoversClass(AppPDF::class)]
class AppPDFTest extends TestCase
{
    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'plugin.Settings.Settings',
    ];

    /**
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Settings::set('core.documents.common.labels.page_of_pages', 'page {page} of {pages}');
    }

    /**
     * Forgets the texts a test set: the database is rolled back, but the settings' caches are not.
     *
     * @return void
     */
    #[Override]
    protected function tearDown(): void
    {
        Settings::setInstance(new SettingsService());
        Cache::clear('default');

        parent::tearDown();
    }

    /**
     * Each page gets its own footer, on that page, with the paper's title and number on the left
     * and which page of how many on the right.
     *
     * @return void
     */
    public function testEveryPageSaysWhichPaperItIsAndWhichPageOfHowMany(): void
    {
        $this->assertSame([
            [1, 'PAPER of a kind · number: 117554-6658'],
            [1, 'page 1 of 3'],
            [2, 'PAPER of a kind · number: 117554-6658'],
            [2, 'page 2 of 3'],
            [3, 'PAPER of a kind · number: 117554-6658'],
            [3, 'page 3 of 3'],
        ], $this->footers(3));
    }

    /**
     * Asking for the same document twice does not draw its footers a second time.
     *
     * @return void
     */
    public function testAskingTwiceDrawsTheFootersOnce(): void
    {
        $this->assertCount(4, $this->footers(2, asked: 2));
    }

    /**
     * What a paper of so many pages draws at the foot of each, on which page and what.
     *
     * The page is the one the engine draws on, not the one the cursor thinks it is on, so that a
     * footer written onto the wrong page is caught.
     *
     * @param int $pages How many pages.
     * @param int $asked How many times the finished document is asked for.
     * @return array<int, array{0:int, 1:string}>
     */
    private function footers(int $pages, int $asked = 1): array
    {
        $pdf = new class extends AppPDF {
            /**
             * What was drawn at the foot of the pages: on which page, and what.
             *
             * @var array<int, array{0:int, 1:string}>
             */
            private array $drawn = [];

            /**
             * @param int $pages How many pages.
             * @return void
             */
            public function draw(int $pages): void
            {
                $this->printDocumentHeader('PAPER', 'of a kind');
                $this->setFooterReference([['number:', '117554-6658']]);

                for ($page = 2; $page <= $pages; $page++) {
                    $this->AddPage();
                }
            }

            /**
             * @inheritDoc
             */
            #[Override]
            public function cell(mixed ...$arguments): void
            {
                $text = (string)($arguments['txt'] ?? $arguments[2] ?? '');
                if ($text !== '' && $this->GetY() === $this->getPageHeight() - static::FOOTER_RESERVE) {
                    $this->drawn[] = [$this->pdf()->page->getPageId() + 1, $text];
                }

                parent::cell(...$arguments);
            }

            /**
             * @return array<int, array{0:int, 1:string}>
             */
            public function footers(): array
            {
                return $this->drawn;
            }
        };

        $pdf->draw($pages);
        for ($time = 1; $time <= $asked; $time++) {
            $pdf->Output('paper.pdf', 'S');
        }

        return $pdf->footers();
    }
}
