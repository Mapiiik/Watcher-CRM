<?php
declare(strict_types=1);

namespace App\Test\TestCase\Colors;

use App\Colors\ColorTransformer;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Colors\ColorTransformer Test Case
 *
 * The conversions are what the theme colors are built out of, so what is held on to here is that a
 * color survives the trip through HSL and back, and that the adjustments move lightness the way
 * their names say while staying inside the range a color has.
 */
#[UsesClass(ColorTransformer::class)]
class ColorTransformerTest extends TestCase
{
    /**
     * Colors covering the corners the conversion is written around: each of the three components in
     * turn being the largest, the wrap the hue takes when green is below blue, a lightness on either
     * side of the half where saturation is worked out differently, and the greys that have no hue at
     * all.
     *
     * @return array<string, array{string}>
     */
    public static function colors(): array
    {
        return [
            'red' => ['#ff0000'],
            'green' => ['#00ff00'],
            'blue' => ['#0000ff'],
            'magenta, whose green is below its blue' => ['#ff00ff'],
            'dark, saturated' => ['#336699'],
            'light, saturated' => ['#99ccff'],
            'grey' => ['#808080'],
            'white' => ['#ffffff'],
            'black' => ['#000000'],
        ];
    }

    /**
     * A color comes back as it went in.
     *
     * @param string $hex Color to send through the conversion.
     * @return void
     * @link \App\Colors\ColorTransformer::hexToHsl()
     * @link \App\Colors\ColorTransformer::hslToHex()
     */
    #[DataProvider('colors')]
    public function testAColorSurvivesTheTripThroughHsl(string $hex): void
    {
        $hsl = ColorTransformer::hexToHsl($hex);

        $this->assertSame($hex, ColorTransformer::hslToHex($hsl['h'], $hsl['s'], $hsl['l']));
    }

    /**
     * The components are read off where they are known: red sits at the start of the circle, fully
     * saturated, halfway up the lightness.
     *
     * @return void
     * @link \App\Colors\ColorTransformer::hexToHsl()
     */
    public function testHexToHslReadsTheComponentsOff(): void
    {
        $hsl = ColorTransformer::hexToHsl('#ff0000');

        $this->assertEqualsWithDelta(0.0, $hsl['h'], 0.001);
        $this->assertEqualsWithDelta(1.0, $hsl['s'], 0.001);
        $this->assertEqualsWithDelta(0.5, $hsl['l'], 0.001);
    }

    /**
     * The hue is placed around the whole circle rather than only over the part red occupies.
     *
     * @return void
     * @link \App\Colors\ColorTransformer::hexToHsl()
     */
    public function testHexToHslPlacesTheHueAroundTheCircle(): void
    {
        $this->assertEqualsWithDelta(120.0, ColorTransformer::hexToHsl('#00ff00')['h'], 0.001);
        $this->assertEqualsWithDelta(240.0, ColorTransformer::hexToHsl('#0000ff')['h'], 0.001);
        $this->assertEqualsWithDelta(300.0, ColorTransformer::hexToHsl('#ff00ff')['h'], 0.001);
    }

    /**
     * A grey has nothing to say about hue, so it is given none rather than whatever the arithmetic
     * would land on.
     *
     * @return void
     * @link \App\Colors\ColorTransformer::hexToHsl()
     */
    public function testHexToHslGivesAGreyNoHueOrSaturation(): void
    {
        $hsl = ColorTransformer::hexToHsl('#808080');

        $this->assertSame(0.0, $hsl['h']);
        $this->assertSame(0.0, $hsl['s']);
    }

    /**
     * The short way of writing a color means the same as the long one.
     *
     * @return void
     * @link \App\Colors\ColorTransformer::hexToHsl()
     */
    public function testHexToHslReadsTheThreeDigitFormAsTheSixDigitOne(): void
    {
        $this->assertSame(ColorTransformer::hexToHsl('#ff0000'), ColorTransformer::hexToHsl('#f00'));
    }

    /**
     * The hash is a convenience of the notation rather than part of the color.
     *
     * @return void
     * @link \App\Colors\ColorTransformer::hexToHsl()
     */
    public function testHexToHslTakesAColorWithoutItsHash(): void
    {
        $this->assertSame(ColorTransformer::hexToHsl('#336699'), ColorTransformer::hexToHsl('336699'));
    }

    /**
     * Darkening lowers the lightness and leaves the hue where it was.
     *
     * The hue is only held to within a degree or two: a darkened color is written back into the
     * eight bits a channel has, and the darker it gets the coarser those steps are about it.
     *
     * @return void
     * @link \App\Colors\ColorTransformer::darken()
     */
    public function testDarkenLowersTheLightnessAndKeepsTheHue(): void
    {
        $darkened = ColorTransformer::hexToHsl(ColorTransformer::darken('#336699'));
        $original = ColorTransformer::hexToHsl('#336699');

        $this->assertLessThan($original['l'], $darkened['l']);
        $this->assertEqualsWithDelta($original['h'], $darkened['h'], 2.0);
    }

    /**
     * Lightening raises the lightness and leaves the hue where it was.
     *
     * @return void
     * @link \App\Colors\ColorTransformer::lighten()
     */
    public function testLightenRaisesTheLightnessAndKeepsTheHue(): void
    {
        $lightened = ColorTransformer::hexToHsl(ColorTransformer::lighten('#336699'));
        $original = ColorTransformer::hexToHsl('#336699');

        $this->assertGreaterThan($original['l'], $lightened['l']);
        $this->assertEqualsWithDelta($original['h'], $lightened['h'], 0.5);
    }

    /**
     * Lightening white has nowhere left to go, and is answered with white rather than with something
     * outside the range a color has.
     *
     * @return void
     * @link \App\Colors\ColorTransformer::lighten()
     */
    public function testLightenStopsAtWhite(): void
    {
        $this->assertSame('#ffffff', ColorTransformer::lighten('#ffffff'));
    }

    /**
     * Inverting swaps the ends of the lightness: what was light comes back dark and the other way
     * round.
     *
     * @return void
     * @link \App\Colors\ColorTransformer::invertLightness()
     */
    public function testInvertLightnessSwapsTheEnds(): void
    {
        $this->assertSame('#000000', ColorTransformer::invertLightness('#ffffff'));
        $this->assertSame('#ffffff', ColorTransformer::invertLightness('#000000'));
    }

    /**
     * Inverting keeps the hue, which is what makes the dark variant recognisable as the same color.
     *
     * @return void
     * @link \App\Colors\ColorTransformer::invertLightness()
     */
    public function testInvertLightnessKeepsTheHue(): void
    {
        $inverted = ColorTransformer::hexToHsl(ColorTransformer::invertLightness('#336699'));

        $this->assertEqualsWithDelta(210.0, $inverted['h'], 0.5);
    }

    /**
     * The factor is applied after the inversion, so it dims or brightens what the inversion arrived
     * at rather than what went in.
     *
     * @return void
     * @link \App\Colors\ColorTransformer::invertLightness()
     */
    public function testInvertLightnessDimsTheResultByTheFactor(): void
    {
        $plain = ColorTransformer::hexToHsl(ColorTransformer::invertLightness('#336699'));
        $dimmed = ColorTransformer::hexToHsl(ColorTransformer::invertLightness('#336699', 0.5));

        $this->assertEqualsWithDelta($plain['l'] * 0.5, $dimmed['l'], 0.01);
    }

    /**
     * Text is put in whichever of black and white can be read against the background.
     *
     * @return void
     * @link \App\Colors\ColorTransformer::getContrastColor()
     */
    public function testGetContrastColorAnswersWithWhatCanBeRead(): void
    {
        $this->assertSame('#000000', ColorTransformer::getContrastColor('#ffffff'));
        $this->assertSame('#ffffff', ColorTransformer::getContrastColor('#000000'));
    }

    /**
     * Brightness is weighted the way an eye sees it rather than by the components alone: green at
     * full strength reads as light, blue at full strength as dark, though neither is any larger a
     * number than the other.
     *
     * @return void
     * @link \App\Colors\ColorTransformer::getContrastColor()
     */
    public function testGetContrastColorWeighsBrightnessTheWayAnEyeSeesIt(): void
    {
        $this->assertSame('#000000', ColorTransformer::getContrastColor('#00ff00'));
        $this->assertSame('#ffffff', ColorTransformer::getContrastColor('#0000ff'));
    }
}
