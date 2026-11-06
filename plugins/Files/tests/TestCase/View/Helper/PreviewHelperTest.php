<?php
declare(strict_types=1);

namespace Files\Test\TestCase\View\Helper;

use App\View\AppView;
use Cake\Http\ServerRequest;
use Cake\Routing\RouteBuilder;
use Cake\Routing\RouteCollection;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Cake\Utility\Text;
use Files\Model\Entity\File;
use Files\Model\Entity\FileLink;
use Files\View\Helper\PreviewHelper;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * Files\View\Helper\PreviewHelper Test Case
 */
#[UsesClass(PreviewHelper::class)]
class PreviewHelperTest extends TestCase
{
    /**
     * The routes in place before this test, which only needs those of the plugin.
     *
     * @var \Cake\Routing\RouteCollection
     */
    private RouteCollection $routes;

    /**
     * @inheritDoc
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->routes = Router::getRouteCollection();
        Router::setRouteCollection(new RouteCollection());
        Router::createRouteBuilder('/')->plugin('Files', function (RouteBuilder $routes): void {
            $routes->fallbacks();
        });
    }

    /**
     * @inheritDoc
     */
    #[Override]
    protected function tearDown(): void
    {
        Router::setRouteCollection($this->routes);

        parent::tearDown();
    }

    /**
     * The viewer puts the title of a page on the screen as markup, and the title carries what
     * somebody typed into a record, so it has to arrive there as text.
     *
     * @return void
     * @link \Files\View\Helper\PreviewHelper::flipThrough()
     */
    public function testTheTitleOfAPageReachesTheViewerAsText(): void
    {
        $helper = new PreviewHelper(new AppView(new ServerRequest()));
        $link = new FileLink([
            'id' => Text::uuid(),
            'name' => 'scan.png',
            'file' => new File(['mime_type' => 'image/png']),
        ]);

        $mark = $helper->flipThrough([$link], 'gallery', '<img src=x onerror=alert(1)>');

        $this->assertSame(1, preg_match('/data-files-pages="([^"]*)"/', $mark, $attribute));
        /** @var list<array<string, string>> $pages */
        $pages = json_decode(html_entity_decode($attribute[1] ?? '', ENT_QUOTES), true);
        $this->assertSame('&lt;img src=x onerror=alert(1)&gt;', $pages[0]['title']);
    }
}
