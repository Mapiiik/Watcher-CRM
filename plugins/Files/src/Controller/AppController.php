<?php
declare(strict_types=1);

namespace Files\Controller;

use App\Controller\AppController as BaseController;
use Cake\Event\EventInterface;
use Override;

class AppController extends BaseController
{
    /**
     * Every page here lists what is filed against something, and every one of them offers the way
     * to it, so the helper that knows the way is loaded once here.
     *
     * @param \Cake\Event\EventInterface<\Cake\Controller\Controller> $event The event.
     * @return void
     */
    #[Override]
    public function beforeRender(EventInterface $event): void
    {
        parent::beforeRender($event);

        $this->viewBuilder()->addHelper('Files.Record');
    }
}
