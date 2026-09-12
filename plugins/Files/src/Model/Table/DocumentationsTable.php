<?php
declare(strict_types=1);

namespace Files\Model\Table;

use App\Model\Table\AppTable;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;
use Files\Model\Entity\Documentation;
use Files\Service\Documentations;
use Override;

/**
 * Documentations Model
 *
 * The column saying what a folder hangs on is not here: it is a contract in one application and a
 * mast in another, so each adds it with its own migration and its own subclass of this.
 *
 * @property \Files\Model\Table\DocumentationTypesTable&\Cake\ORM\Association\BelongsTo $DocumentationTypes
 * @method \Files\Model\Entity\Documentation newEmptyEntity()
 * @method \Files\Model\Entity\Documentation newEntity(array $data, array $options = [])
 * @method \Files\Model\Entity\Documentation[] newEntities(array $data, array $options = [])
 * @method \Files\Model\Entity\Documentation get(mixed $primaryKey, array|string $finder = 'all', null|\Psr\SimpleCache\CacheInterface|string $cache = null, null|\Closure|string $cacheKey = null, mixed ...$args)
 * @method \Files\Model\Entity\Documentation findOrCreate($search, callable|array|null $callback = null, $options = [])
 * @method \Files\Model\Entity\Documentation patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Files\Model\Entity\Documentation[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Files\Model\Entity\Documentation|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Files\Model\Entity\Documentation saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method iterable<\Files\Model\Entity\Documentation>|false saveMany(iterable $entities, $options = [])
 * @method iterable<\Files\Model\Entity\Documentation> saveManyOrFail(iterable $entities, $options = [])
 * @method iterable<\Files\Model\Entity\Documentation>|false deleteMany(iterable $entities, $options = [])
 * @method iterable<\Files\Model\Entity\Documentation> deleteManyOrFail(iterable $entities, $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class DocumentationsTable extends AppTable
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

        $this->setTable('documentations');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('Footprint');

        $this->belongsTo('DocumentationTypes', [
            'className' => 'Files.DocumentationTypes',
            'foreignKey' => 'documentation_type_id',
            'joinType' => 'INNER',
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
            ->uuid('documentation_type_id')
            ->requirePresence('documentation_type_id', 'create')
            ->notEmptyString('documentation_type_id');

        $validator
            ->date('happened_on')
            ->allowEmptyDate('happened_on');

        $validator
            ->scalar('name')
            ->allowEmptyString('name');

        $validator
            ->scalar('note')
            ->allowEmptyString('note');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * Both of these are asked of the whole record rather than of a field as it arrives, which is
     * why they are rules: one reads two columns together and the other reads the type. Neither is
     * gated on anything having changed - a folder that does not agree with itself is wrong every
     * time it is written, not only when somebody touches the part that made it so.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    #[Override]
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['documentation_type_id'], 'DocumentationTypes'), [
            'errorField' => 'documentation_type_id',
        ]);

        $rules->add(
            fn(Documentation $documentation): bool => $documentation->hasIdentity(),
            'told apart from the next one',
            [
                'errorField' => 'name',
                'message' => __d(
                    'files',
                    'A folder needs a name or a day. With neither there is nothing to tell it from'
                    . ' the next one of its kind.',
                ),
            ],
        );

        $rules->add(
            fn(Documentation $documentation): bool => $this->dayGivenWhereItIsAsked($documentation),
            'the day its kind asks for',
            [
                'errorField' => 'happened_on',
                'message' => __d('files', 'This kind of folder is filed under the day it is about.'),
            ],
        );

        return $rules;
    }

    /**
     * Lets go of what the folder holds before the folder itself goes.
     *
     * Here rather than in whoever pressed the button, so that a folder taken away with the record
     * it hangs on - a contract deleted, a mast deleted - takes its files with it just the same.
     *
     * @param \Cake\Event\EventInterface<\Files\Model\Table\DocumentationsTable> $event The event.
     * @param \Cake\Datasource\EntityInterface $entity The folder on its way out.
     * @return void
     */
    public function beforeDelete(EventInterface $event, EntityInterface $entity): void
    {
        if ($entity instanceof Documentation) {
            (new Documentations())->clear($entity);
        }
    }

    /**
     * The folders of a record, in the order they are meant to read.
     *
     * The standing ones first and the rest newest downwards, which on this database is what
     * ordering by the day descending already does - a folder with no day is one that is kept up
     * to date rather than one that happened long ago.
     *
     * @param \Cake\ORM\Query\SelectQuery<\Files\Model\Entity\Documentation> $query The query.
     * @return \Cake\ORM\Query\SelectQuery<\Files\Model\Entity\Documentation>
     */
    public function findInReadingOrder(SelectQuery $query): SelectQuery
    {
        return $query->orderBy([
            $this->aliasField('happened_on') => 'DESC',
            $this->aliasField('name') => 'ASC',
        ]);
    }

    /**
     * Whether this folder carries the day, where its kind says it has to.
     *
     * The type is read rather than taken off the entity, which need not have it loaded.
     *
     * @param \Files\Model\Entity\Documentation $documentation The folder.
     * @return bool
     */
    private function dayGivenWhereItIsAsked(Documentation $documentation): bool
    {
        if ($documentation->happened_on !== null) {
            return true;
        }

        $asked = $this->DocumentationTypes->find()
            ->select(['DocumentationTypes.date_required'])
            ->where(['DocumentationTypes.id' => $documentation->documentation_type_id])
            ->first();

        return !($asked->date_required ?? false);
    }
}
