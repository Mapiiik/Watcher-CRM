<?php
declare(strict_types=1);

namespace Files\Service;

/**
 * What may be shown rather than handed over.
 *
 * The content came from outside, so anything the browser would run instead of draw would run in
 * our own origin. A list of what is allowed rather than of what is not, and one list rather than
 * two: the controller asks whether it may offer something inline and the viewer asks how to show
 * it, which are the same question answered at two ends.
 *
 * Kept apart from the application's own list of what may be uploaded. That one says what is worth
 * filing, this one says what is safe to open, and they answer to different things.
 */
class Viewable
{
    /**
     * How each kind is shown, by the name the viewer knows it under.
     *
     * A PDF goes in a frame so that the browser's own reader pages through it: the outer arrows
     * then move between documents and the inner bar between the pages of one.
     *
     * @var array<string, string>
     */
    private const AS_WHAT = [
        'application/pdf' => 'iframe',
        'image/jpeg' => 'image',
        'image/png' => 'image',
        'image/gif' => 'image',
        'image/webp' => 'image',
    ];

    /**
     * The kinds that may be opened in place of being kept.
     *
     * @return list<string>
     */
    public static function safely(): array
    {
        return array_keys(self::AS_WHAT);
    }

    /**
     * Whether this is something to show.
     *
     * @param string|null $mime_type What kind of content it is.
     * @return bool
     */
    public static function opens(?string $mime_type): bool
    {
        return $mime_type !== null && isset(self::AS_WHAT[$mime_type]);
    }

    /**
     * How to show it, or null where it is not something to show at all.
     *
     * @param string|null $mime_type What kind of content it is.
     * @return string|null
     */
    public static function typeOf(?string $mime_type): ?string
    {
        return $mime_type === null ? null : self::AS_WHAT[$mime_type] ?? null;
    }
}
