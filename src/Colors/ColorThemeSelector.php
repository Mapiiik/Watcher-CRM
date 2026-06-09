<?php
declare(strict_types=1);

namespace App\Colors;

final class ColorThemeSelector
{
    /**
     * @var array<string, string>
     */
    private static array $cache = [];

    /**
     * Returns a theme-adjusted color.
     *
     * @param string      $hex   Original HEX color (#rrggbb)
     * @param string|null $theme Current UI theme (may be null before initialization)
     * @return string HEX color adjusted for the theme
     */
    public static function forTheme(string $hex, ?string $theme, float $factor = 0.75): string
    {
        $key = $hex . '|' . ($theme ?? 'default') . '|' . toString($factor);

        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        $result = match ($theme) {
            'dark' => ColorTransformer::invertLightness($hex, $factor),
            default => $hex,
        };

        return self::$cache[$key] = $result;
    }
}
