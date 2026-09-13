<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\Validation\Validator;
use Files\Model\Table\DocumentationsTable as FilesDocumentationsTable;
use Override;

/**
 * Documentations Model
 *
 * On top of the shared folder: what it hangs on here, and what a kind of folder may require of it.
 *
 * @property \App\Model\Table\DocumentationTypesTable&\Cake\ORM\Association\BelongsTo $DocumentationTypes
 * @property \App\Model\Table\CustomersTable&\Cake\ORM\Association\BelongsTo $Customers
 * @property \App\Model\Table\ContractsTable&\Cake\ORM\Association\BelongsTo $Contracts
 * @method \App\Model\Entity\Documentation newEmptyEntity()
 * @method \App\Model\Entity\Documentation newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Documentation[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Documentation get(mixed $primaryKey, array|string $finder = 'all', null|\Psr\SimpleCache\CacheInterface|string $cache = null, null|\Closure|string $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Documentation findOrCreate($search, callable|array|null $callback = null, $options = [])
 * @method \App\Model\Entity\Documentation patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Documentation[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Documentation|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Documentation saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method iterable<\App\Model\Entity\Documentation>|false saveMany(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\Documentation> saveManyOrFail(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\Documentation>|false deleteMany(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\Documentation> deleteManyOrFail(iterable $entities, $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class DocumentationsTable extends FilesDocumentationsTable
{
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

        $this->belongsTo('Customers', [
            'foreignKey' => 'customer_id',
        ]);

        $this->belongsTo('Contracts', [
            'foreignKey' => 'contract_id',
        ]);
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
        $validator = parent::validationDefault($validator);

        $validator
            ->uuid('customer_id')
            ->allowEmptyString('customer_id');

        $validator
            ->uuid('contract_id')
            ->allowEmptyString('contract_id');

        return $validator;
    }

    /**
     * What a kind of folder may require of the folders under it.
     *
     * @return array<string, array<string, string>>
     */
    #[Override]
    protected function askedFor(): array
    {
        return parent::askedFor() + [
            'customer_required' => [
                'column' => 'customer_id',
                'message' => __('This documentation type is filed under a customer.'),
            ],
            'contract_required' => [
                'column' => 'contract_id',
                'message' => __('This documentation type is filed under a contract.'),
            ],
        ];
    }
}
