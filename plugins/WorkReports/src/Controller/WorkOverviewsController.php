<?php
declare(strict_types=1);

namespace WorkReports\Controller;

use App\NMS\ApiClient;
use Cake\Http\Response;
use Cake\I18n\Date;
use Override;
use PhpCollective\DecimalObject\Decimal;
use WorkReports\Model\Entity\WorkReportItem;
use WorkReports\Model\Table\WorkReportItemsTable;

/**
 * Overviews of the work reported, for those who carry on with it.
 */
class WorkOverviewsController extends AppController
{
    /**
     * The items the overviews are made of.
     */
    protected WorkReportItemsTable $WorkReportItems;

    /**
     * Initialize method
     *
     * @return void
     */
    #[Override]
    public function initialize(): void
    {
        parent::initialize();

        /** @var \WorkReports\Model\Table\WorkReportItemsTable $items */
        $items = $this->fetchTable('WorkReports.WorkReportItems');
        $this->WorkReportItems = $items;
    }

    /**
     * The work to be invoiced, by customer, with what it comes to.
     *
     * @return void Renders view
     */
    public function toInvoice(): void
    {
        $query = $this->WorkReportItems->find(
            'all',
            contain: [
                'WorkReports' => ['Users'],
                'WorkReportItemTypes',
                'Customers',
                'Contracts',
                'WorkRates',
            ],
            conditions: ['WorkReportItems.to_invoice' => true],
            order: ['WorkReportItems.customer_id', 'WorkReportItems.date', 'WorkReportItems.work_from'],
        );

        $invoiced = $this->getRequest()->getQuery('invoiced', '0');
        if ($invoiced === '0' || $invoiced === '1') {
            $query->where(['WorkReportItems.invoiced' => $invoiced === '1']);
        }
        $customerId = $this->getRequest()->getQuery('customer_id');
        if (is_string($customerId) && $customerId !== '') {
            $query->where(['WorkReportItems.customer_id' => $customerId]);
        }
        $from = $this->queryDate('from');
        if ($from !== null) {
            $query->where(['WorkReportItems.date >=' => $from]);
        }
        $to = $this->queryDate('to');
        if ($to !== null) {
            $query->where(['WorkReportItems.date <=' => $to]);
        }

        $groups = [];
        $total = Decimal::create(0);
        /** @var \WorkReports\Model\Entity\WorkReportItem $item */
        foreach ($query->all() as $item) {
            $key = $item->customer_id ?? '';
            $groups[$key] ??= ['customer' => $item->customer, 'items' => [], 'amount' => Decimal::create(0)];
            $groups[$key]['items'][] = $item;
            $amount = self::amountOf($item);
            if ($amount !== null) {
                $groups[$key]['amount'] = $groups[$key]['amount']->add($amount);
                $total = $total->add($amount);
            }
        }

        $customers = $this->WorkReportItems->Customers->find('list', order: ['company', 'last_name', 'first_name'])
            ->where(['Customers.id IN' => $this->WorkReportItems->find()
                ->select(['customer_id'])
                ->where(['to_invoice' => true, 'customer_id IS NOT' => null])]);

        $this->set(compact('groups', 'total', 'customers', 'invoiced', 'from', 'to'));
    }

    /**
     * The work done at the access points, by access point, with the time it took.
     *
     * @return void Renders view
     */
    public function byAccessPoint(): void
    {
        $query = $this->WorkReportItems->find(
            'all',
            contain: [
                'WorkReports' => ['Users'],
                'WorkReportItemTypes',
                'Customers',
            ],
            conditions: ['WorkReportItems.access_point_id IS NOT' => null],
            order: ['WorkReportItems.date' => 'DESC', 'WorkReportItems.work_from' => 'DESC'],
        );

        $accessPointId = $this->getRequest()->getQuery('access_point_id');
        if (is_string($accessPointId) && $accessPointId !== '') {
            $query->where(['WorkReportItems.access_point_id' => $accessPointId]);
        }
        $from = $this->queryDate('from');
        if ($from !== null) {
            $query->where(['WorkReportItems.date >=' => $from]);
        }
        $to = $this->queryDate('to');
        if ($to !== null) {
            $query->where(['WorkReportItems.date <=' => $to]);
        }

        $accessPoints = ApiClient::getAccessPointsList();
        /** @var array<string, string> $names */
        $names = $accessPoints->or([]);

        $groups = [];
        /** @var \WorkReports\Model\Entity\WorkReportItem $item */
        foreach ($query->all() as $item) {
            $key = (string)$item->access_point_id;
            $groups[$key] ??= ['name' => $names[$key] ?? null, 'items' => [], 'minutes' => 0];
            $groups[$key]['items'][] = $item;
            $groups[$key]['minutes'] += $item->minutes;
        }
        uasort($groups, fn(array $a, array $b): int => strnatcasecmp((string)$a['name'], (string)$b['name']));

        $this->set(compact('groups', 'accessPoints', 'names', 'from', 'to'));
    }

    /**
     * Mark the chosen items as invoiced, or take the mark back.
     *
     * @return \Cake\Http\Response|null Redirects back to the overview.
     */
    public function markInvoiced(): ?Response
    {
        $this->getRequest()->allowMethod(['post']);

        $ids = (array)$this->getRequest()->getData('ids', []);
        $invoiced = (bool)$this->getRequest()->getData('invoiced');

        $saved = 0;
        $failed = 0;
        if ($ids !== []) {
            foreach ($this->WorkReportItems->find()->where(['id IN' => $ids, 'to_invoice' => true])->all() as $item) {
                $this->WorkReportItems->patchEntity($item, ['invoiced' => $invoiced], ['fields' => ['invoiced']]);
                if ($this->WorkReportItems->save($item)) {
                    $saved++;
                } else {
                    $failed++;
                }
            }
        }

        if ($saved > 0) {
            $this->Flash->success(__dn(
                'work_reports',
                '{0} item has been marked.',
                '{0} items have been marked.',
                $saved,
                $saved,
            ));
        }
        if ($failed > 0 || $saved === 0) {
            $this->Flash->error(__d('work_reports', 'Some items could not be marked. Please, try again.'));
        }

        return $this->redirect($this->referer(['action' => 'toInvoice']));
    }

    /**
     * What the item comes to: the hours by the price of the rate and the multiplier. Nothing when
     * the rate has no price.
     *
     * @param \WorkReports\Model\Entity\WorkReportItem $item Item with its rate.
     * @return \PhpCollective\DecimalObject\Decimal|null
     */
    public static function amountOf(WorkReportItem $item): ?Decimal
    {
        if ($item->invoice_hours === null || $item->work_rate?->price === null) {
            return null;
        }

        return $item->invoice_hours
            ->multiply($item->work_rate->price)
            ->multiply($item->rate_multiplier)
            ->round(2);
    }

    /**
     * A date from the query, when it holds one.
     *
     * @param string $name Query parameter.
     * @return \Cake\I18n\Date|null
     */
    protected function queryDate(string $name): ?Date
    {
        $value = $this->getRequest()->getQuery($name);

        return is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1 ? new Date($value) : null;
    }
}
