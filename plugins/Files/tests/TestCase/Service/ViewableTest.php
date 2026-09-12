<?php
declare(strict_types=1);

namespace Files\Test\TestCase\Service;

use Cake\TestSuite\TestCase;
use Files\Service\Viewable;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Files\Service\Viewable Test Case
 *
 * One list answers two questions - whether the content may be offered inline, and how the viewer
 * should show it. They have to stay the same list, because a kind the controller will hand over
 * inline but the viewer does not know would open as a blank slide.
 */
#[CoversClass(Viewable::class)]
class ViewableTest extends TestCase
{
    /**
     * What may be opened is what the viewer knows how to show. Neither is a longer list.
     *
     * @return void
     */
    public function testTheTwoAnswersComeFromTheSameList(): void
    {
        foreach (Viewable::safely() as $mime_type) {
            $this->assertNotNull(
                Viewable::typeOf($mime_type),
                $mime_type . ' may be opened, so the viewer has to know what to do with it',
            );
        }
    }

    /**
     * A paper goes in a frame so the browser's own reader pages through it, a scan is drawn.
     *
     * @return void
     */
    public function testEachKindIsShownTheWayItWants(): void
    {
        // `external` is the viewer's word for a framed page. `iframe` is not a kind it knows, and
        // an unknown kind is drawn as a picture - which for a PDF is an empty slide.
        $this->assertSame('external', Viewable::typeOf('application/pdf'));
        $this->assertSame('image', Viewable::typeOf('image/jpeg'));

        // HEIC is filed but not shown: no browser draws it, and until a preview is made of it
        // there is nothing to put in a slide.
        $this->assertNull(Viewable::typeOf('image/heic'));
    }

    /**
     * Every kind is named in words the viewer answers to.
     *
     * The list is GLightbox's own: anything else falls through to being drawn as a picture, and
     * a PDF drawn as a picture is a slide that stays blank.
     *
     * @return void
     */
    public function testEveryKindIsNamedInTheViewersOwnWords(): void
    {
        $known = ['image', 'video', 'external', 'inline', 'audio', 'ajax'];

        foreach (Viewable::safely() as $mime_type) {
            $this->assertContains(Viewable::typeOf($mime_type), $known, $mime_type);
        }
    }

    /**
     * Anything the browser would run rather than draw is not opened at all.
     *
     * @return void
     */
    public function testWhatWouldRunInOurOwnOriginIsNotOpened(): void
    {
        foreach (['text/html', 'image/svg+xml', 'application/javascript', 'text/plain'] as $mime_type) {
            $this->assertFalse(Viewable::opens($mime_type), $mime_type . ' is not ours to open');
            $this->assertNull(Viewable::typeOf($mime_type));
        }
    }

    /**
     * Content nobody said anything about is not guessed at.
     *
     * @return void
     */
    public function testNothingIsGuessedAt(): void
    {
        $this->assertFalse(Viewable::opens(null));
        $this->assertNull(Viewable::typeOf(null));
    }
}
