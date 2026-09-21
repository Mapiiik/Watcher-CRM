<?php
declare(strict_types=1);

namespace WorkReports\Controller;

use Cake\ORM\Table;
use WorkReports\Controller\Trait\LookupControllerTrait;

/**
 * WorkLabels Controller
 *
 * @property \WorkReports\Model\Table\WorkLabelsTable $WorkLabels
 */
class WorkLabelsController extends AppController
{
    use LookupControllerTrait;

    /**
     * @inheritDoc
     */
    protected function lookupTable(): Table
    {
        return $this->WorkLabels;
    }
}
