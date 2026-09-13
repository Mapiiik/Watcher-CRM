<?php
declare(strict_types=1);

namespace App\Test\TestCase\View;

use App\Test\Traits\ControllerTestTrait;
use App\View\AppView;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * A window says which page it is holding.
 *
 * Left to itself the framework names a window after the folder the template came from, so every
 * page of an agenda is called the same thing and a row of tabs cannot be told apart. The name of
 * the application stays in front of it, which is what keeps the windows of one application
 * together wherever they are listed.
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
        'app.ContractStates',
        'plugin.Files.Files',
        'plugin.Files.FileLinks',
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
    public function testTwoPagesOfOneAgendaAreNamedApart(): void
    {
        $this->login();
        $this->get('/labels');
        $this->assertResponseOk();
        $listing = $this->titleOfTheResponse();

        $this->get('/labels/add');
        $this->assertResponseOk();
        $form = $this->titleOfTheResponse();

        $this->assertStringEndsWith('Labels | Index', $listing);
        $this->assertStringEndsWith('Labels | Add', $form);
    }

    /**
     * @return void
     */
    public function testAnAgendaOfMoreThanOneWordIsSaidAsWords(): void
    {
        $this->login();
        $this->get('/contract-states');

        $this->assertResponseOk();
        $this->assertStringEndsWith('Contract States | Index', $this->titleOfTheResponse());
    }

    /**
     * @return void
     */
    public function testAPageOfAPluginSaysWhichPluginItIs(): void
    {
        $this->login();
        $this->get('/files/documents');

        $this->assertResponseOk();
        $this->assertStringEndsWith('Files | Documents | Index', $this->titleOfTheResponse());
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
