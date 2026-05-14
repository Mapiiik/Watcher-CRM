<?php
declare(strict_types=1);

namespace App\Colors;

final class ColorThemeSelector
{
    /**
     * Returns a theme-adjusted color.
     *
     * @param string      $hex   Original HEX color (#rrggbb)
     * @param string|null $theme Current UI theme (may be null before initialization)
     * @return string HEX color adjusted for the theme
     */
    public static function forTheme(string $hex, ?string $theme): string
    {
        switch ($theme) {
            case 'dark':
            case 'tailwind':
                return ColorTransformer::darken($hex, 0.15);

            default:
                return $hex;
        }
    }
}
