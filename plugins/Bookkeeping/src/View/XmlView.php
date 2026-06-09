<?php
declare(strict_types=1);

namespace Bookkeeping\View;

use App\View\AppView;
use Override;

class XmlView extends AppView
{
    /**
     * XML layouts are located in the 'xml' subdirectory of `Layouts/`
     */
    protected string $layoutPath = 'xml';

    /**
     * XML views are located in the 'xml' subdirectory for controllers' views.
     */
    protected string $subDir = 'xml';

    /**
     * Mime-type this view class renders as.
     *
     * @return string The JSON content type.
     */
    #[Override]
    public static function contentType(): string
    {
        return 'application/xml';
    }
}
