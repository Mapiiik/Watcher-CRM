<?php
declare(strict_types=1);

namespace App\View\Helper;

use App\Model\Entity\Billing;
use App\Model\Table\BillingsTable;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\View\Helper;

/**
 * What the pages listing billings say about one of them.
 *
 * @extends \Cake\View\Helper<\App\View\AppView>
 */
class BillingHelper extends Helper
{
    use LocatorAwareTrait;

    /**
     * The question asked before a billing is deleted.
     *
     * Only whoever may override the invoiced periods is shown the link on a billing invoiced for,
     * so the question warns them rather than everybody else.
     *
     * @param \App\Model\Entity\Billing $billing The billing about to be deleted.
     * @return string
     */
    public function deleteConfirmation(Billing $billing): string
    {
        $question = __('Are you sure you want to delete # {0}?', $billing->id);

        if ($this->fetchTable(BillingsTable::class)->mayBeDeleted($billing)) {
            return $question;
        }

        return __('This billing has been invoiced for, so deleting it changes a period whose invoices have gone out.')
            . ' ' . $question;
    }
}
