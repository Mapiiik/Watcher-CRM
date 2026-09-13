<?php
declare(strict_types=1);

namespace App\Test\TestCase\View;

use App\Test\Traits\ControllerTestTrait;
use App\View\AppView;
use Cake\Http\ServerRequest;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * A window says which page it is holding.
 *
 * A page names itself and its window in one call, so that the two cannot drift apart. A page
 * that has not been taught to yet is named after the address it was opened at, which is the
 * floor rather than the finish - left to the framework alone every page of an agenda carries
 * the same name and a row of tabs tells nothing apart. The name of the application stays in
 * front of both, which is what keeps the windows of one application together wherever they
 * are listed.
 *
 * The fallback is asked directly rather than through a page, so that these stay true as the
 * pages are taught one by one.
 */
#[UsesClass(AppView::class)]
class WindowsAreNamedTest extends TestCase
{
    use ControllerTestTrait;
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.AppUsers',
        'app.AccountingProfiles',
        'app.Customers',
        'app.Labels',
        'app.CustomerLabels',
    ];

    /**
     * @return void
     */
    public function testTheApplicationComesFirst(): void
    {
        $this->login();
        $this->get('/labels');

        $this->assertResponseOk();
        $this->assertMatchesRegularExpression(
            '~<title>Watcher CRM \| [^<]*</title>~',
            (string)$this->_getBodyAsString(),
        );
    }

    /**
     * @return void
     */
    public function testAPageIsCalledWhatItCallsItself(): void
    {
        $this->login();
        $this->get('/labels');
        $this->assertResponseOk();
        $listing = $this->titleOfTheResponse();

        $this->get('/labels/add');
        $this->assertResponseOk();
        $form = $this->titleOfTheResponse();

        $this->assertStringEndsWith('| Labels', $listing);
        $this->assertStringEndsWith('| Add Label', $form);
    }

    /**
     * @return void
     */
    public function testAPageThatHasNotNamedItselfIsCalledAfterItsAddress(): void
    {
        $this->assertSame('Contract States | View', $this->fallbackFor('ContractStates', 'view'));
        $this->assertSame('Customer Proposals | Index', $this->fallbackFor('CustomerProposals', 'index'));
    }

    /**
     * @return void
     */
    public function testTheFallbackSaysWhichPluginThePageBelongsTo(): void
    {
        $this->assertSame('Files | Documents | Index', $this->fallbackFor('Documents', 'index', 'Files'));
    }

    /**
     * A plugin named after the agenda it draws would otherwise say it twice.
     *
     * @return void
     */
    public function testTheFallbackSaysAPluginNamedAfterItsAgendaOnce(): void
    {
        $this->assertSame('Dashboard | Cards', $this->fallbackFor('Dashboard', 'cards', 'Dashboard'));
    }

    /**
     * The page has a line for the kind and another for the record. The window has one, so there
     * they are joined by the same bar that separates everything else in it.
     *
     * @return void
     */
    public function testAPageAboutOneRecordIsCalledAfterIt(): void
    {
        $view = $this->aPage();

        $view->record('Customer', '550001');
        $view->renderLayout('', 'ajax');

        $this->assertSame('Customer | 550001', $view->fetch('title'));
    }

    /**
     * What is being done with the record follows its kind, so that the papers of a customer and
     * the customer are not two pages carrying the same heading and the same window name.
     *
     * @return void
     */
    public function testWhatIsDoneWithARecordFollowsItsKind(): void
    {
        $view = $this->aPage();

        $drawn = $view->record('Customer', '550001', doing: 'Print');
        $view->renderLayout('', 'ajax');

        $this->assertSame('Customer - Print | 550001', $view->fetch('title'));
        // and the page says it too, which is where the window got it from
        $this->assertStringStartsWith('Customer - Print<h3>550001</h3>', $drawn);
    }

    /**
     * A page that is not about a record is called what it calls itself, whatever its fieldsets
     * are headed.
     *
     * @return void
     */
    public function testAFieldsetDoesNotNameAPageThatNamedItself(): void
    {
        $view = $this->aPage();

        $view->heading('Billings - Bulk Service Change');
        $view->legend('Original Service');
        $view->renderLayout('', 'ajax');

        $this->assertSame('Billings - Bulk Service Change', $view->fetch('title'));
    }

    /**
     * A view to ask these of, standing at some address or other.
     *
     * @return \App\View\AppView
     */
    private function aPage(): AppView
    {
        return new AppView(new ServerRequest([
            'params' => ['plugin' => null, 'controller' => 'Customers', 'action' => 'print'],
        ]));
    }

    /**
     * The heading that names the window has to be the page's own.
     *
     * A page carries headings for its sections as well, and one of those sitting after a form
     * would name the window after a panel somewhere down the page instead of after the page.
     * Read off the templates, because the mistake is a call in the wrong place.
     *
     * @return void
     */
    public function testASectionHeadingDoesNotNameTheWindow(): void
    {
        $looked = 0;

        foreach ($this->templates() as $path) {
            $source = (string)file_get_contents($path);

            foreach (['$this->heading(', '$this->record('] as $call) {
                $at = strpos($source, $call);
                if ($at === false) {
                    continue;
                }

                $looked++;

                $this->assertFalse(
                    str_contains(substr($source, 0, $at), '<legend>'),
                    substr($path, strlen(ROOT)) . ' names its window after a heading that sits below a form',
                );
            }
        }

        $this->assertNotEmpty($looked, 'there is at least one page naming itself to check');
    }

    /**
     * Every template of the application and of the plugins it carries.
     *
     * @return list<string>
     */
    private function templates(): array
    {
        $found = [];

        foreach ([ROOT . DS . 'templates', ROOT . DS . 'plugins'] as $where) {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($where));
            foreach ($files as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $found[] = $file->getPathname();
                }
            }
        }

        sort($found);

        return $found;
    }

    /**
     * What a page opened at this address would be called, had it not named itself.
     *
     * @param string $agenda The folder the template would come from.
     * @param string $action The action asked for.
     * @param string|null $plugin The plugin it belongs to, if any.
     * @return string
     */
    private function fallbackFor(string $agenda, string $action, ?string $plugin = null): string
    {
        $view = new AppView(new ServerRequest([
            'params' => ['plugin' => $plugin, 'controller' => $agenda, 'action' => $action],
        ]));
        $view->setTemplatePath($agenda);
        $view->renderLayout('', 'ajax');

        return $view->fetch('title');
    }

    /**
     * What the window the response asks for is called.
     *
     * @return string
     */
    private function titleOfTheResponse(): string
    {
        preg_match('~<title>(.*)</title>~', (string)$this->_getBodyAsString(), $found);

        return trim($found[1] ?? '');
    }
}
