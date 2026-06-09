<?php
declare(strict_types=1);

namespace App\Colors;

/**
 * Utility class for color manipulation.
 *
 * Provides conversions between HEX and HSL color formats and methods
 * for generating darker or lighter variants of a given color.
 * Useful for theme adjustments (e.g., light/dark mode).
 */
final class ColorTransformer
{
    /**
     * Converts a HEX color value to HSL.
     *
     * @param string $hex Color in #rrggbb or #rgb format.
     * @return array{h: float, s: float, l: float} HSL components:
     *   - h: Hue in range 0–360
     *   - s: Saturation in range 0–1
     *   - l: Lightness in range 0–1
     */
    public static function hexToHsl(string $hex): array
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $l = ($max + $min) / 2;

        if ($max === $min) {
            $h = 0.0;
            $s = 0.0;
        } else {
            $d = $max - $min;
            $s = $l > 0.5
                ? $d / (2 - $max - $min)
                : $d / ($max + $min);

            if ($max === $r) {
                $h = ($g - $b) / $d + ($g < $b ? 6 : 0);
            } elseif ($max === $g) {
                $h = ($b - $r) / $d + 2;
            } else {
                $h = ($r - $g) / $d + 4;
            }

            $h /= 6;
        }

        return [
            'h' => $h * 360,
            's' => $s,
            'l' => $l,
        ];
    }

    /**
     * Converts an HSL color value to HEX.
     *
     * @param float $h Hue in range 0–360.
     * @param float $s Saturation in range 0–1.
     * @param float $l Lightness in range 0–1.
     * @return string HEX color in #rrggbb format.
     */
    public static function hslToHex(float $h, float $s, float $l): string
    {
        $h /= 360;

        if ($s === 0.0) {
            $r = $g = $b = $l;
        } else {
            $q = $l < 0.5
                ? $l * (1 + $s)
                : $l + $s - $l * $s;

            $p = 2 * $l - $q;

            $r = self::hueToRgb($p, $q, $h + 1 / 3);
            $g = self::hueToRgb($p, $q, $h);
            $b = self::hueToRgb($p, $q, $h - 1 / 3);
        }

        return sprintf(
            '#%02x%02x%02x',
            (int)round($r * 255),
            (int)round($g * 255),
            (int)round($b * 255),
        );
    }

    /**
     * Helper function for HSL → RGB conversion.
     *
     * @return float RGB component in range 0–1.
     */
    private static function hueToRgb(float $p, float $q, float $t): float
    {
        if ($t < 0) {
            $t += 1;
        }
        if ($t > 1) {
            $t -= 1;
        }
        if ($t < 1 / 6) {
            return $p + ($q - $p) * 6 * $t;
        }
        if ($t < 1 / 2) {
            return $q;
        }
        if ($t < 2 / 3) {
            return $p + ($q - $p) * (2 / 3 - $t) * 6;
        }

        return $p;
    }

    /**
     * Generates a darker variant of a given color.
     *
     * @param string $hex Original HEX color.
     * @param float $factor Darkening factor (0.0–1.0).
     *                      Lower values produce darker colors.
     *                      Recommended:
     *                      - 0.25 for strong darkening
     *                      - 0.35 for moderate darkening
     * @return string HEX color adjusted for dark theme.
     */
    public static function darken(string $hex, float $factor = 0.25): string
    {
        $hsl = self::hexToHsl($hex);

        $h = $hsl['h'];
        $s = $hsl['s'];
        $l = max(0.0, min(1.0, $hsl['l'] * $factor));

        return self::hslToHex($h, $s, $l);
    }

    /**
     * Generates a lighter variant of a given color.
     *
     * @param string $hex Original HEX color.
     * @param float $factor Lightening factor (>1.0).
     * @return string HEX color.
     */
    public static function lighten(string $hex, float $factor = 1.25): string
    {
        $hsl = self::hexToHsl($hex);

        $h = $hsl['h'];
        $s = $hsl['s'];
        $l = max(0.0, min(1.0, $hsl['l'] * $factor));

        return self::hslToHex($h, $s, $l);
    }

    /**
     * Inverts the lightness component of a HEX color using the HSL model,
     * with optional adjustment factor for fine‑tuning the result.
     *
     * Base transformation:
     *   L' = 1 - L
     *
     * This preserves hue and saturation while producing a perceptually
     * consistent dark‑mode variant:
     *   - Light colors become dark
     *   - Dark colors become light
     *   - Mid‑tones remain balanced
     *
     * The optional $factor allows controlled brightening or darkening
     * of the inverted result:
     *
     *   L'' = clamp(L' * $factor, 0.0, 1.0)
     *
     * Examples:
     *   - factor 1.00 → pure inversion (recommended default)
     *   - factor 0.85 → slightly darker dark‑mode
     *   - factor 1.15 → slightly brighter dark‑mode
     *
     * @param string $hex Original HEX color (#rrggbb or #rgb).
     * @param float  $factor Multiplier applied after inversion (default 1.0).
     * @return string HEX color with inverted and adjusted lightness.
     */
    public static function invertLightness(string $hex, float $factor = 1.0): string
    {
        $hsl = self::hexToHsl($hex);

        $h = $hsl['h'];
        $s = $hsl['s'];

        // Base inversion
        $l = 1.0 - $hsl['l'];

        // Optional adjustment
        $l = max(0.0, min(1.0, $l * $factor));

        return self::hslToHex($h, $s, $l);
    }

    /**
     * Returns a readable text color (black or white) based on background brightness.
     *
     * @param string $hex Background color.
     * @return string '#000000' or '#ffffff'
     */
    public static function getContrastColor(string $hex): string
    {
        $hex = ltrim($hex, '#');

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        // Perceived luminance
        $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;

        return $luminance > 0.5 ? '#000000' : '#ffffff';
    }
}
