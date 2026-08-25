<?php
declare(strict_types=1);

namespace App\Controller\Api;

use Cake\Http\Exception\BadRequestException;
use Cake\Validation\Validation;

/**
 * AccessPoints Controller
 *
 * This application keeps no places of the network of its own - what it keeps is records that name
 * one, without a key to hold them by. So the only thing asked here is the one thing the other
 * application cannot work out for itself: it is about to let a place go, and something over here
 * may still be standing on it.
 *
 * The historical connections are left out of the answer on purpose. What they say is where a
 * customer was connected at the time, and they keep the name of the place beside its number for
 * exactly that reason - a mast that is gone does not make the history of it wrong.
 */
class AccessPointsController extends AppController
{
    /**
     * How many records here name one place of the network, by what they are.
     *
     * Counted rather than listed: the caller is deciding whether the place may go, and what it
     * needs for that is whether the answer is nought. Whoever then wants to see them opens this
     * application, where they are anyway.
     *
     * @param string|null $id The identifier Watcher NMS keeps the place under.
     * @return void Renders view
     * @throws \Cake\Http\Exception\BadRequestException Where that is not an identifier at all.
     */
    public function references(?string $id = null): void
    {
        if (!Validation::uuid($id)) {
            throw new BadRequestException(__('That is not the identifier of an access point.'));
        }

        $references = [
            'contracts' => $this->fetchTable('Contracts')
                ->find()
                ->where(['Contracts.access_point_id' => $id])
                ->count(),
            'tasks' => $this->fetchTable('Tasks')
                ->find()
                ->where(['Tasks.access_point_id' => $id])
                ->count(),
        ];

        $this->set('references', $references);
        $this->viewBuilder()->setOption('serialize', ['references']);
    }
}
