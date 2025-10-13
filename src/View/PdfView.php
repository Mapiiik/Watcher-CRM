<?php
declare(strict_types=1);

namespace App\View;

use Override;

class PdfView extends AppView
{
    /**
     * PDF layouts are located in the 'pdf' subdirectory of `Layouts/`
     *
     * @var string
     */
    protected string $layoutPath = 'pdf';

    /**
     * PDF views are located in the 'pdf' subdirectory for controllers' views.
     *
     * @var string
     */
    protected string $subDir = 'pdf';

    /**
     * Mime-type this view class renders as.
     *
     * @return string The JSON content type.
     */
    #[Override]
    public static function contentType(): string
    {
        return 'application/pdf';
    }
}
