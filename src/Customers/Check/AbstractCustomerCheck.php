<?php
declare(strict_types=1);

namespace App\Customers\Check;

use App\Check\AbstractCheck;
use App\Model\Table\CustomerLabelsTable;
use App\Model\Table\CustomersTable;
use Cake\ORM\Query\SelectQuery;
use Override;
use Settings\Utility\Settings;

/**
 * Shared ground for customer checks.
 *
 * These are all about something that is not on file, and for most of them there is a way of
 * saying that it never will be: a label put on by hand meaning the customer has no e-mail
 * address, or will not give a date of birth, or has refused consent. Somebody has already
 * looked at those and decided, so a listing that kept reporting them would be asking the same
 * question again every week.
 *
 * Which labels excuse which check is asked of the settings by name rather than written into
 * the code by identifier - the settings page is read by people, and a name can be read.
 *
 * A contract has no way of narrowing any of this, so none of them says which field holds one,
 * and a contract's page is not offered them.
 */
abstract class AbstractCustomerCheck extends AbstractCheck implements CustomerCheckInterface
{
    /**
     * Where the settings say which labels excuse a check.
     */
    private const SETTINGS_PATH = 'core.customers.checks';

    /**
     * @param \App\Model\Table\CustomersTable $customers Customers table.
     * @param bool $ignore_inactive Whether to pass over customers with nothing running.
     * @param string|null $customer_id The one customer being asked about, where there is one.
     */
    public function __construct(
        protected CustomersTable $customers,
        protected bool $ignore_inactive = true,
        ?string $customer_id = null,
    ) {
        parent::__construct(customer_id: $customer_id);
    }

    /**
     * @return string
     */
    #[Override]
    public function element(): string
    {
        return 'CustomerChecks/' . $this->template();
    }

    /**
     * @return string|null
     */
    #[Override]
    protected function customerField(): ?string
    {
        return 'Customers.id';
    }

    /**
     * The customers this check may report, before it says what it is looking for.
     *
     * What is on file about somebody we no longer serve is not worth chasing, so by default
     * only the ones with something running are asked at all.
     *
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    protected function candidates(): SelectQuery
    {
        $query = $this->customers->find()->orderBy(['Customers.nid' => 'DESC']);

        if ($this->ignore_inactive) {
            $query->where(['Customers.id IN' => $this->activeCustomerIds()]);
        }

        return $query;
    }

    /**
     * The customers somebody has already looked at and let off this check, as ids.
     *
     * @param string $setting The setting naming the labels, below the customer checks.
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    protected function excusedBy(string $setting): SelectQuery
    {
        $named = Settings::get(self::SETTINGS_PATH . '.' . $setting, []);
        $names = is_array($named) ? array_values(array_map(strval(...), $named)) : [];

        /** @var \App\Model\Table\CustomerLabelsTable $customer_labels */
        $customer_labels = $this->fetchTable(CustomerLabelsTable::class);

        $excused = $customer_labels
            ->find()
            ->select(['CustomerLabels.customer_id'], true)
            ->innerJoinWith('Labels');

        // A setting left empty excuses nobody. Left as an `IN` over no values it would say
        // the opposite of that, and quietly.
        return $names === []
            ? $excused->where(['1 = 0'])
            : $excused->where(['Labels.name IN' => $names]);
    }
}
