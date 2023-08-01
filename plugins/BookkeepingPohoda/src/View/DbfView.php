<?php
declare(strict_types=1);

namespace BookkeepingPohoda\View;

use App\View\AppView;

class DbfView extends AppView
{
    /**
     * DBF layouts are located in the 'dbf' subdirectory of `Layouts/`
     *
     * @var string
     */
    protected string $layoutPath = 'dbf';

    /**
     * DBF views are located in the 'dbf' subdirectory for controllers' views.
     *
     * @var string
     */
    protected string $subDir = 'dbf';

    /**
     * Mime-type this view class renders as.
     *
     * @return string The JSON content type.
     */
    public static function contentType(): string
    {
        return 'application/dbase';
    }
}
