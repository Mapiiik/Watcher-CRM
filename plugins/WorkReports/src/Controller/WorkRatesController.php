<?php
declare(strict_types=1);

namespace WorkReports\Controller;

use Cake\ORM\Table;
use WorkReports\Controller\Trait\LookupControllerTrait;

/**
 * WorkRates Controller
 *
 * @property \WorkReports\Model\Table\WorkRatesTable $WorkRates
 */
class WorkRatesController extends AppController
{
    use LookupControllerTrait;

    /**
     * @inheritDoc
     */
    protected function lookupTable(): Table
    {
        return $this->WorkRates;
    }
}
