<?php
declare(strict_types=1);

namespace WorkReports\Controller;

use Cake\ORM\Table;
use WorkReports\Controller\Trait\LookupControllerTrait;

/**
 * WorkReportItemTypes Controller
 *
 * @property \WorkReports\Model\Table\WorkReportItemTypesTable $WorkReportItemTypes
 */
class WorkReportItemTypesController extends AppController
{
    use LookupControllerTrait;

    /**
     * @inheritDoc
     */
    protected function lookupTable(): Table
    {
        return $this->WorkReportItemTypes;
    }
}
