<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Enum;

use App\Model\Enum\LandingPage;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Model\Enum\LandingPage Test Case
 *
 * A page named here is one somebody will be sent to on every sign-in. That the URL of each one
 * really leads where it says is asked of the whole chain in
 * {@see \App\Test\TestCase\Controller\HomeControllerTest}, where the plugin routes are loaded;
 * what is left for here is what the settings form is filled from.
 */
#[UsesClass(LandingPage::class)]
class LandingPageTest extends TestCase
{
    /**
     * Every page is offered under a name, and the value stored for it is the one the form posts.
     *
     * @return void
     * @link \App\Model\Enum\LandingPage::label()
     * @link \App\Model\Enum\Trait\EnumOptionsTrait::options()
     */
    public function testEveryPageIsOfferedUnderAName(): void
    {
        $options = LandingPage::options();

        $this->assertSame(
            array_map(fn(LandingPage $page): string => $page->value, LandingPage::cases()),
            array_keys($options),
        );
        $this->assertNotContains('', $options);
    }

    /**
     * A page is somewhere to be sent, so it has to name a controller and an action to be sent to.
     *
     * @return void
     * @link \App\Model\Enum\LandingPage::url()
     */
    public function testEveryPageNamesWhereItIs(): void
    {
        foreach (LandingPage::cases() as $page) {
            $url = $page->url();

            $this->assertArrayHasKey('plugin', $url, $page->value);
            $this->assertNotEmpty($url['controller'], $page->value);
            $this->assertNotEmpty($url['action'], $page->value);
        }
    }
}
