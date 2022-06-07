<?php
declare(strict_types=1);

namespace BookkeepingPohoda\Controller;

use App\Controller\AppController as BaseController;

class AppController extends BaseController
{
    /**
     * Global beforeFilter
     *
     * @param \Cake\Event\EventInterface $event An Event instance
     * @return \Cake\Http\Response|null|void
     */
    public function beforeFilter(\Cake\Event\EventInterface $event)
    {
        // add support for dBase extension
        $this->response->setTypeMap('dbf', ['application/dbase']);

        parent::beforeFilter($event);
    }
}
