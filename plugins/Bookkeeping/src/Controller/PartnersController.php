<?php
declare(strict_types=1);

namespace Bookkeeping\Controller;

use App\Model\Table\CustomersTable;
use Bookkeeping\Service\BookkeepingService;
use Cake\Http\Response;
use Cake\Log\Log;
use RuntimeException;

/**
 * Partners Controller
 *
 * The customer's partner card in the accounting system, written at once rather than on the next
 * scheduled run.
 */
class PartnersController extends AppController
{
    /**
     * How long to wait for the accounting system, in seconds. Somebody is waiting at the screen,
     * and the scheduled run sends the customer later anyway.
     */
    private const TIMEOUT = 30;

    /**
     * Send method
     *
     * @param string|null $id Customer id.
     * @return \Cake\Http\Response|null Redirects to referer or customer view.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function send(?string $id = null): ?Response
    {
        $this->getRequest()->allowMethod(['post']);

        $customer = $this->fetchTable(CustomersTable::class)->get(
            $id,
            contain: BookkeepingService::PARTNER_ASSOCIATIONS,
        );

        $bookkeeping = new BookkeepingService();

        if (!$bookkeeping->writesPartnerCardOf($customer)) {
            $this->Flash->error(__d('bookkeeping', 'This customer is not sent to the accounting system.'));
        } else {
            try {
                $bookkeeping->sendPartners([$customer], self::TIMEOUT);

                $this->Flash->success(__d('bookkeeping', 'The customer has been sent to the accounting system.'));
            } catch (RuntimeException $e) {
                Log::error('Error when sending partner ' . $customer->number . ': ' . $e->getMessage());

                $this->Flash->error(__d(
                    'bookkeeping',
                    'The customer could not be sent to the accounting system: {0}',
                    $e->getMessage(),
                ));
            }
        }

        return $this->redirect($this->referer([
            'plugin' => null,
            'controller' => 'Customers',
            'action' => 'view',
            $customer->id,
        ]));
    }
}
