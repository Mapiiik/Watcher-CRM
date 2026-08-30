<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\Billing;
use Bookkeeping\Model\Enum\InvoicingSchedule;
use Cake\I18n\Date;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;
use Override;
use Settings\Utility\Settings;

/**
 * Billings Model
 *
 * @property \App\Model\Table\CustomersTable&\Cake\ORM\Association\BelongsTo $Customers
 * @property \App\Model\Table\ServicesTable&\Cake\ORM\Association\BelongsTo $Services
 * @property \App\Model\Table\ContractsTable&\Cake\ORM\Association\BelongsTo $Contracts
 * @method \App\Model\Entity\Billing newEmptyEntity()
 * @method \App\Model\Entity\Billing newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Billing[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Billing get(mixed $primaryKey, array|string $finder = 'all', null|\Psr\SimpleCache\CacheInterface|string $cache = null, null|\Closure|string $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Billing findOrCreate($search, callable|array|null $callback = null, $options = [])
 * @method \App\Model\Entity\Billing patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Billing[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Billing|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Billing saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method iterable<\App\Model\Entity\Billing>|false saveMany(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\Billing> saveManyOrFail(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\Billing>|false deleteMany(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\Billing> deleteManyOrFail(iterable $entities, $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class BillingsTable extends AppTable
{
    /**
     * Where the installation says whether the day invoicing runs on is still open.
     *
     * @var string
     */
    public const DAY_STAYS_OPEN = 'bookkeeping.invoices.issuing.day_stays_open';

    /**
     * The save option that lets a write reach into a period that has already been invoiced.
     *
     * @var string
     */
    public const ALLOW_CLOSED_PERIODS = 'allow_closed_periods';

    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    #[Override]
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('billings');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('Footprint');
        $this->addBehavior('StringModifications');

        $this->belongsTo('Customers', [
            'foreignKey' => 'customer_id',
        ]);
        $this->belongsTo('Services', [
            'foreignKey' => 'service_id',
        ]);
        $this->belongsTo('Contracts', [
            'foreignKey' => 'contract_id',
            'joinType' => 'INNER',
        ]);
    }

    /**
     * Restrict a billings query to the non-historical ones: still open
     * (`billing_until IS NULL`) or ending in the current month or later.
     *
     * This is the "active or future" scope the contract and customer views show
     * by default, shared so the bulk message wizard reads services the same way.
     *
     * @param \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Billing> $query Query to restrict.
     * @return \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Billing>
     */
    public function findActiveOrFuture(SelectQuery $query): SelectQuery
    {
        return $query->where([
            'OR' => [
                'Billings.billing_until IS' => null,
                'Billings.billing_until >=' => Date::now()->firstOfMonth(),
            ],
        ]);
    }

    /**
     * The last day invoicing has settled.
     *
     * Nothing in the data says which months were invoiced - invoicing reads the billings for a
     * month and writes nothing back - so it is derived from when the run happens, which is the
     * one thing the installation does say.
     *
     * @return \Cake\I18n\Date
     */
    public function lastInvoicedPeriodEnd(): Date
    {
        $today = Date::now();
        $endOfLastMonth = $today->firstOfMonth()->subDays(1);

        return match ($this->invoicingSchedule()) {
            // invoiced on its own last day, so from that day the month itself is behind us
            InvoicingSchedule::CURRENT_MONTH_ON_LAST =>
                $today->equals($today->lastOfMonth()) ? $today : $endOfLastMonth,

            // invoiced on the first day of the month that follows, so the current one never is
            InvoicingSchedule::PREV_MONTH_ON_FIRST => $endOfLastMonth,
        };
    }

    /**
     * The last day a billing may no longer be moved behind.
     *
     * What invoicing settled, less one concession: on the day of the run itself the period being
     * invoiced is usually still being put right - the check against the control file fails and the
     * corrections are made that same day - so an installation may keep it open.
     *
     * @return \Cake\I18n\Date
     */
    public function lastClosedPeriodEnd(): Date
    {
        $invoiced = $this->lastInvoicedPeriodEnd();

        if ($this->invoicingRunsToday() && (bool)Settings::get(self::DAY_STAYS_OPEN, true)) {
            // the period being invoiced is a whole month, so stepping over it steps a month
            return $invoiced->firstOfMonth()->subDays(1);
        }

        return $invoiced;
    }

    /**
     * The earliest day a billing may still be given.
     *
     * @return \Cake\I18n\Date
     */
    public function firstOpenPeriodStart(): Date
    {
        return $this->lastClosedPeriodEnd()->addDays(1);
    }

    /**
     * What the installation says about when it invoices.
     *
     * A value outside the list cannot be stored - the setting is declared as a choice and refuses
     * one - so the only thing left to catch here is a row written before it was declared.
     *
     * @return \Bookkeeping\Model\Enum\InvoicingSchedule
     */
    private function invoicingSchedule(): InvoicingSchedule
    {
        return InvoicingSchedule::tryFrom(Settings::getString(
            InvoicingSchedule::SETTINGS_PATH,
            InvoicingSchedule::CURRENT_MONTH_ON_LAST->value,
        )) ?? InvoicingSchedule::CURRENT_MONTH_ON_LAST;
    }

    /**
     * Whether invoicing is running today.
     *
     * @return bool
     */
    private function invoicingRunsToday(): bool
    {
        $today = Date::now();

        return match ($this->invoicingSchedule()) {
            InvoicingSchedule::CURRENT_MONTH_ON_LAST => $today->equals($today->lastOfMonth()),
            InvoicingSchedule::PREV_MONTH_ON_FIRST => $today->equals($today->firstOfMonth()),
        };
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    #[Override]
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->uuid('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->uuid('customer_id')
            ->requirePresence('customer_id', 'create')
            ->notEmptyString('customer_id');

        $validator
            ->uuid('contract_id')
            ->requirePresence('contract_id', 'create')
            ->notEmptyString('contract_id');

        $validator
            ->scalar('text')
            ->allowEmptyString('text');

        $validator
            ->decimal('price')
            ->allowEmptyString('price');

        $validator
            ->decimal('fixed_discount')
            ->allowEmptyString('fixed_discount');

        $validator
            ->integer('percentage_discount')
            ->allowEmptyString('percentage_discount');

        $validator
            ->date('billing_from')
            ->requirePresence('billing_from', 'create')
            ->notEmptyDate('billing_from');

        $validator
            ->scalar('note')
            ->allowEmptyString('note');

        $validator
            ->date('billing_until')
            ->allowEmptyDate('billing_until');

        $validator
            ->boolean('separate_invoice')
            ->notEmptyString('separate_invoice');

        $validator
            ->integer('quantity')
            ->notEmptyString('quantity');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    #[Override]
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['customer_id'], 'Customers'), ['errorField' => 'customer_id']);
        $rules->add($rules->existsIn(['service_id'], 'Services'), ['errorField' => 'service_id']);
        $rules->add($rules->existsIn(['contract_id'], 'Contracts'), ['errorField' => 'contract_id']);

        // Held here rather than in the validator, because a billing is also written by callers that
        // send one field and nothing else - a contract being wound up, a service being changed -
        // and because what the date used to be is a question only the entity can answer.
        $rules->add(
            function (Billing $entity): bool {
                if ($entity->billing_from === null || $entity->billing_until === null) {
                    return true;
                }

                // a billing of a single day is a real one, and is what the finding asks for too
                return $entity->billing_until >= $entity->billing_from;
            },
            'billingPeriodIsPossible',
            [
                'errorField' => 'billing_until',
                'message' => __('The billing cannot end before it starts.'),
            ],
        );

        $rules->add(
            function (Billing $entity, array $options): bool {
                if (!empty($options[self::ALLOW_CLOSED_PERIODS]) || !$entity->isDirty('billing_from')) {
                    return true;
                }

                $open = $this->firstOpenPeriodStart();
                $was = $entity->isNew() ? null : $entity->getOriginal('billing_from');

                // both ways round: a start already invoiced for cannot be taken away, and none
                // may be put back into months whose invoices have gone out
                return $entity->billing_from >= $open && ($was === null || $was >= $open);
            },
            'billingStartsInAnOpenPeriod',
            [
                'errorField' => 'billing_from',
                'message' => __('The billing may not start in a period that has already been invoiced.'),
            ],
        );

        $rules->add(
            function (Billing $entity, array $options): bool {
                if (!empty($options[self::ALLOW_CLOSED_PERIODS]) || !$entity->isDirty('billing_until')) {
                    return true;
                }

                $closed = $this->lastClosedPeriodEnd();
                $was = $entity->isNew() ? null : $entity->getOriginal('billing_until');

                // an end lying in a period already invoiced is not to be moved at all - lifting it
                // charges for months whose invoices went out without them
                if ($was !== null && $was < $closed) {
                    return false;
                }

                return $entity->billing_until === null || $entity->billing_until >= $closed;
            },
            'billingEndsAfterWhatWasInvoiced',
            [
                'errorField' => 'billing_until',
                'message' => __(
                    'The billing may not end before {0}, the last day that has been invoiced.',
                    $this->lastClosedPeriodEnd()->i18nFormat(),
                ),
            ],
        );

        return $rules;
    }
}
