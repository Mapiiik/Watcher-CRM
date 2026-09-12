<?php
declare(strict_types=1);

namespace Files\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;
use Override;

/**
 * DocumentationTypes Model
 *
 * @property \Files\Model\Table\DocumentationsTable&\Cake\ORM\Association\HasMany $Documentations
 * @method \Files\Model\Entity\DocumentationType newEmptyEntity()
 * @method \Files\Model\Entity\DocumentationType newEntity(array $data, array $options = [])
 * @method \Files\Model\Entity\DocumentationType[] newEntities(array $data, array $options = [])
 * @method \Files\Model\Entity\DocumentationType get(mixed $primaryKey, array|string $finder = 'all', null|\Psr\SimpleCache\CacheInterface|string $cache = null, null|\Closure|string $cacheKey = null, mixed ...$args)
 * @method \Files\Model\Entity\DocumentationType findOrCreate($search, callable|array|null $callback = null, $options = [])
 * @method \Files\Model\Entity\DocumentationType patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Files\Model\Entity\DocumentationType[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Files\Model\Entity\DocumentationType|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Files\Model\Entity\DocumentationType saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method iterable<\Files\Model\Entity\DocumentationType>|false saveMany(iterable $entities, $options = [])
 * @method iterable<\Files\Model\Entity\DocumentationType> saveManyOrFail(iterable $entities, $options = [])
 * @method iterable<\Files\Model\Entity\DocumentationType>|false deleteMany(iterable $entities, $options = [])
 * @method iterable<\Files\Model\Entity\DocumentationType> deleteManyOrFail(iterable $entities, $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class DocumentationTypesTable extends AppTable
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

        $this->setTable('documentation_types');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('Footprint');

        $this->hasMany('Documentations', [
            'className' => 'Files.Documentations',
            'foreignKey' => 'documentation_type_id',
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
        $validator
            ->uuid('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->scalar('name')
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->nonNegativeInteger('position')
            ->notEmptyString('position');

        $validator
            ->boolean('currently_offered')
            ->notEmptyString('currently_offered');

        $validator
            ->boolean('date_required')
            ->notEmptyString('date_required');

        $validator
            ->scalar('note')
            ->allowEmptyString('note');

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
        // A type with folders under it is not deleted, it is taken off the list. Deleting it would
        // leave those folders saying nothing about what they are.
        $rules->addDelete($rules->isNotLinkedTo('Documentations'));

        return $rules;
    }

    /**
     * The types somebody may choose from, in the order they are meant to read.
     *
     * One that is no longer offered is still among them where a folder already carries it, so
     * that editing that folder does not quietly move it to some other kind.
     *
     * @param \Cake\ORM\Query\SelectQuery<\Files\Model\Entity\DocumentationType> $query The query.
     * @param string|null $including A type to keep whatever it says about itself.
     * @return \Cake\ORM\Query\SelectQuery<\Files\Model\Entity\DocumentationType>
     */
    public function findOffered(SelectQuery $query, ?string $including = null): SelectQuery
    {
        $offered = [$this->aliasField('currently_offered') => true];

        return $query
            ->where($including === null ? $offered : [
                'OR' => [$offered, [$this->aliasField('id') => $including]],
            ])
            ->orderBy([
                $this->aliasField('position') => 'ASC',
                $this->aliasField('name') => 'ASC',
            ]);
    }
}
