<?php
declare(strict_types=1);

namespace Tasks\Model\Entity\Trait;

use App\Colors\ColorThemeSelector;
use App\Colors\ColorTransformer;
use Cake\Core\Configure;

/**
 * Turns a stored colour into what goes into a `style` attribute.
 *
 * Templates print a style as it is, so this is the one place it is made safe: what is not a colour
 * draws nothing, whatever got into the column. It lives here so that the application and its
 * sister application can both reach it from the entities they share.
 */
trait ColoredStyleTrait
{
    /**
     * What a colour is stored as, which is what the colour field of a form sends.
     */
    public const COLOR_PATTERN = '/^#[0-9a-fA-F]{6}$/D';

    /**
     * A colour as the background of a row, softened for the dark theme.
     *
     * @param string|null $color The colour as it is stored.
     * @return string
     */
    protected function backgroundStyle(?string $color): string
    {
        $background = $this->themedColor($color, 0.75);

        return $background === null ? '' : 'background-color: ' . $background . ';';
    }

    /**
     * A colour as the background of a label, with the text in whichever colour reads on it.
     *
     * @param string|null $color The colour as it is stored.
     * @return string
     */
    protected function labelStyle(?string $color): string
    {
        $background = $this->themedColor($color, 1.0);

        return $background === null
            ? ''
            : 'background-color: ' . $background . '; color: ' . ColorTransformer::getContrastColor($background) . ';';
    }

    /**
     * The colour the current theme makes of a stored one, null where it is not a colour.
     *
     * @param string|null $color The colour as it is stored.
     * @param float $factor How far the dark theme turns it.
     * @return string|null
     */
    private function themedColor(?string $color, float $factor): ?string
    {
        if ($color === null || preg_match(self::COLOR_PATTERN, $color) !== 1) {
            return null;
        }

        $theme = Configure::read('UI.theme');

        return ColorThemeSelector::forTheme($color, is_string($theme) ? $theme : null, $factor);
    }
}
