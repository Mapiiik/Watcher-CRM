<?php
declare(strict_types=1);

namespace Files\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Query\SelectQuery;
use Cake\Validation\Validator;
use Override;

/**
 * FileLinks Model
 *
 * @property \Files\Model\Table\FilesTable&\Cake\ORM\Association\BelongsTo $Files
 * @property \App\Model\Table\AppUsersTable&\Cake\ORM\Association\BelongsTo $Creators
 * @property \App\Model\Table\AppUsersTable&\Cake\ORM\Association\BelongsTo $Modifiers
 * @method \Files\Model\Entity\FileLink newEmptyEntity()
 * @method \Files\Model\Entity\FileLink newEntity(array $data, array $options = [])
 * @method \Files\Model\Entity\FileLink[] newEntities(array $data, array $options = [])
 * @method \Files\Model\Entity\FileLink get(mixed $primaryKey, array|string $finder = 'all', null|\Psr\SimpleCache\CacheInterface|string $cache = null, null|\Closure|string $cacheKey = null, mixed ...$args)
 * @method \Files\Model\Entity\FileLink findOrCreate($search, callable|array|null $callback = null, $options = [])
 * @method \Files\Model\Entity\FileLink patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Files\Model\Entity\FileLink[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Files\Model\Entity\FileLink|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Files\Model\Entity\FileLink saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method iterable<\Files\Model\Entity\FileLink>|false saveMany(iterable $entities, $options = [])
 * @method iterable<\Files\Model\Entity\FileLink> saveManyOrFail(iterable $entities, $options = [])
 * @method iterable<\Files\Model\Entity\FileLink>|false deleteMany(iterable $entities, $options = [])
 * @method iterable<\Files\Model\Entity\FileLink> deleteManyOrFail(iterable $entities, $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class FileLinksTable extends AppTable
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

        $this->setTable('file_links');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->getSchema()->setColumnType('meta', 'json');

        $this->addBehavior('Timestamp');
        $this->addBehavior('Footprint');

        $this->belongsTo('Files', [
            'className' => 'Files.Files',
            'foreignKey' => 'file_id',
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
            ->scalar('model')
            ->requirePresence('model', 'create')
            ->notEmptyString('model');

        $validator
            ->uuid('foreign_key')
            ->requirePresence('foreign_key', 'create')
            ->notEmptyString('foreign_key');

        $validator
            ->scalar('document_type')
            ->requirePresence('document_type', 'create')
            ->notEmptyString('document_type');

        $validator
            ->scalar('variant')
            ->requirePresence('variant', 'create')
            ->notEmptyString('variant');

        $validator
            ->nonNegativeInteger('position')
            ->notEmptyString('position');

        // Kept exactly as it arrived, so nothing at all is asked of it.
        $validator
            ->scalar('name')
            ->allowEmptyString('name');

        $validator
            ->array('meta')
            ->allowEmptyArray('meta');

        return $validator;
    }

    /**
     * Everything one record has.
     *
     * @param \Cake\ORM\Query\SelectQuery<\Files\Model\Entity\FileLink> $query The query.
     * @param string $model What kind of record.
     * @param string $foreign_key Which one.
     * @return \Cake\ORM\Query\SelectQuery<\Files\Model\Entity\FileLink>
     */
    public function findFor(SelectQuery $query, string $model, string $foreign_key): SelectQuery
    {
        return $query
            ->where([
                $this->aliasField('model') => $model,
                $this->aliasField('foreign_key') => $foreign_key,
            ])
            ->orderBy([
                $this->aliasField('document_type') => 'ASC',
                $this->aliasField('variant') => 'ASC',
                $this->aliasField('position') => 'ASC',
            ]);
    }

    /**
     * Everything a handful of records have, in one asking.
     *
     * A page that lists what several records hold - every proposal on a contract, say - would
     * otherwise ask once per record, and the answer is the same query with a wider WHERE.
     *
     * @param \Cake\ORM\Query\SelectQuery<\Files\Model\Entity\FileLink> $query The query.
     * @param string $model What kind of record.
     * @param list<string> $foreign_keys Which ones.
     * @return \Cake\ORM\Query\SelectQuery<\Files\Model\Entity\FileLink>
     */
    public function findForAny(SelectQuery $query, string $model, array $foreign_keys): SelectQuery
    {
        if ($foreign_keys === []) {
            return $query->where('1 = 0');
        }

        return $query
            ->where([
                $this->aliasField('model') => $model,
                $this->aliasField('foreign_key') . ' IN' => $foreign_keys,
            ])
            ->orderBy([
                $this->aliasField('foreign_key') => 'ASC',
                $this->aliasField('document_type') => 'ASC',
                $this->aliasField('variant') => 'ASC',
                $this->aliasField('position') => 'ASC',
            ]);
    }

    /**
     * One group: a record's pages of one document in one hand.
     *
     * @param \Cake\ORM\Query\SelectQuery<\Files\Model\Entity\FileLink> $query The query.
     * @param string $model What kind of record.
     * @param string $foreign_key Which one.
     * @param string $document_type Which document.
     * @param string $variant Which variant of that document this is.
     * @return \Cake\ORM\Query\SelectQuery<\Files\Model\Entity\FileLink>
     */
    public function findGroup(
        SelectQuery $query,
        string $model,
        string $foreign_key,
        string $document_type,
        string $variant,
    ): SelectQuery {
        return $query
            ->where([
                $this->aliasField('model') => $model,
                $this->aliasField('foreign_key') => $foreign_key,
                $this->aliasField('document_type') => $document_type,
                $this->aliasField('variant') => $variant,
            ])
            ->orderBy([$this->aliasField('position') => 'ASC']);
    }
}
