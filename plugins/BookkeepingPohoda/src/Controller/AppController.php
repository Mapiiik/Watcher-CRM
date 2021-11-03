<?php
declare(strict_types=1);

namespace BookkeepingPohoda\Controller;

use App\Controller\AppController as BaseController;

class AppController extends BaseController
{
    public function beforeFilter($event)
    {
        // add support for dBase extension
        $this->response->setTypeMap('dbf', ['application/dbase']);

        parent::beforeFilter($event);
    }
}
