<?php
declare(strict_types=1);

namespace Tasks\Test\TestCase\Model\Entity\Trait;

use App\Colors\ColorThemeSelector;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use Tasks\Model\Entity\Task;
use Tasks\Model\Entity\TaskState;
use Tasks\Model\Entity\Trait\ColoredStyleTrait;

/**
 * Tasks\Model\Entity\Trait\ColoredStyleTrait Test Case
 *
 * Asked through a task, which is the entity both applications share.
 */
#[CoversClass(ColoredStyleTrait::class)]
class ColoredStyleTraitTest extends TestCase
{
    /**
     * The theme as the test found it.
     */
    private mixed $theme = null;

    /**
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->theme = Configure::read('UI.theme');
        Configure::write('UI.theme', 'light');
    }

    /**
     * @return void
     */
    #[Override]
    protected function tearDown(): void
    {
        Configure::write('UI.theme', $this->theme);

        parent::tearDown();
    }

    /**
     * A task in a state of a colour is drawn in it.
     *
     * @return void
     */
    public function testAColorBecomesTheBackground(): void
    {
        $this->assertSame('background-color: #c8e6c9;', $this->aTaskIn('#c8e6c9')->get('style'));
    }

    /**
     * The dark theme has its own idea of the colour, and the style goes with it.
     *
     * @return void
     */
    public function testTheThemeHasItsSay(): void
    {
        Configure::write('UI.theme', 'dark');

        $this->assertSame(
            'background-color: ' . ColorThemeSelector::forTheme('#c8e6c9', 'dark', 0.75) . ';',
            $this->aTaskIn('#c8e6c9')->get('style'),
        );
    }

    /**
     * A label is drawn in its own colour, whatever the theme, with text that reads on it.
     *
     * @return void
     */
    public function testALabelGetsTextThatReadsOnIt(): void
    {
        $label = new class {
            use ColoredStyleTrait;

            /**
             * @param string|null $color The colour as it is stored.
             * @return string
             */
            public function style(?string $color): string
            {
                return $this->labelStyle($color);
            }
        };

        $this->assertSame('background-color: #000000; color: #ffffff;', $label->style('#000000'));
        $this->assertSame('background-color: #ffffff; color: #000000;', $label->style('#ffffff'));
        $this->assertSame('', $label->style('"><img'));
        $this->assertSame('', $label->style(null));

        Configure::write('UI.theme', 'dark');

        $this->assertStringStartsWith(
            'background-color: ' . ColorThemeSelector::forTheme('#c8e6c9', 'dark', 1.0) . ';',
            $label->style('#c8e6c9'),
        );
    }

    /**
     * A style goes into the attribute unescaped, so what is not a colour draws nothing rather than
     * ending the attribute or carrying a declaration of its own.
     *
     * @param string $color What is stored.
     * @return void
     */
    #[TestWith(['"><img']), TestWith(['red;x:y']), TestWith(['#fff']), TestWith(['#c8e6c9' . "\n"]), TestWith([''])]
    public function testWhatIsNotAColorDrawsNothing(string $color): void
    {
        $this->assertSame('', $this->aTaskIn($color)->get('style'));
    }

    /**
     * A task loaded without its state has no colour to go by.
     *
     * @return void
     */
    public function testNoStateDrawsNothing(): void
    {
        $this->assertSame('', (new Task())->get('style'));
    }

    /**
     * A task in a state of that colour.
     *
     * @param string $color The colour of the state.
     * @return \Tasks\Model\Entity\Task
     */
    private function aTaskIn(string $color): Task
    {
        return new Task(['task_state' => new TaskState(['color' => $color])]);
    }
}
