<?php
declare(strict_types=1);

namespace BookkeepingPohoda\View;

use App\View\AppView;

class XmlView extends AppView
{
    /**
     * XML layouts are located in the 'xml' subdirectory of `Layouts/`
     *
     * @var string
     */
    protected string $layoutPath = 'xml';

    /**
     * XML views are located in the 'xml' subdirectory for controllers' views.
     *
     * @var string
     */
    protected string $subDir = 'xml';

    /**
     * Mime-type this view class renders as.
     *
     * @return string The JSON content type.
     */
    public static function contentType(): string
    {
        return 'application/xml';
    }
}
