<?php
declare(strict_types=1);

namespace Files\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Query\SelectQuery;
use Cake\Validation\Validator;
use Override;

/**
 * Files Model
 *
 * @property \Files\Model\Table\FileLinksTable&\Cake\ORM\Association\HasMany $FileLinks
 * @property \App\Model\Table\AppUsersTable&\Cake\ORM\Association\BelongsTo $Creators
 * @property \App\Model\Table\AppUsersTable&\Cake\ORM\Association\BelongsTo $Modifiers
 * @method \Files\Model\Entity\File newEmptyEntity()
 * @method \Files\Model\Entity\File newEntity(array $data, array $options = [])
 * @method \Files\Model\Entity\File[] newEntities(array $data, array $options = [])
 * @method \Files\Model\Entity\File get(mixed $primaryKey, array|string $finder = 'all', null|\Psr\SimpleCache\CacheInterface|string $cache = null, null|\Closure|string $cacheKey = null, mixed ...$args)
 * @method \Files\Model\Entity\File findOrCreate($search, callable|array|null $callback = null, $options = [])
 * @method \Files\Model\Entity\File patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Files\Model\Entity\File[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Files\Model\Entity\File|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Files\Model\Entity\File saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method iterable<\Files\Model\Entity\File>|false saveMany(iterable $entities, $options = [])
 * @method iterable<\Files\Model\Entity\File> saveManyOrFail(iterable $entities, $options = [])
 * @method iterable<\Files\Model\Entity\File>|false deleteMany(iterable $entities, $options = [])
 * @method iterable<\Files\Model\Entity\File> deleteManyOrFail(iterable $entities, $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class FilesTable extends AppTable
{
    /**
     * What content is hashed with today.
     *
     * A row says which algorithm answered for it rather than the column being named after one,
     * so changing this is a migration of the rows and not of the schema - and both can stand side
     * by side while that happens, which is what they are unique over together.
     */
    public const HASH_ALGORITHM = 'sha256';

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

        $this->setTable('files');
        $this->setDisplayField('hash');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('Footprint');

        // Raised rather than cascaded, so that content is never taken away from something that
        // still wants it. Letting go happens the other way round: the last link goes, then this.
        $this->hasMany('FileLinks', [
            'className' => 'Files.FileLinks',
            'foreignKey' => 'file_id',
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
            ->scalar('hash')
            ->add('hash', 'hexadecimal', [
                'rule' => fn(string $value): bool => ctype_xdigit($value),
                'message' => __d('files', 'The hash of the content is written in hexadecimal.'),
            ])
            ->requirePresence('hash', 'create')
            ->notEmptyString('hash');

        $validator
            ->scalar('hash_type')
            ->requirePresence('hash_type', 'create')
            ->notEmptyString('hash_type');

        $validator
            ->nonNegativeInteger('byte_size')
            ->requirePresence('byte_size', 'create')
            ->notEmptyString('byte_size');

        $validator
            ->scalar('mime_type')
            ->requirePresence('mime_type', 'create')
            ->notEmptyString('mime_type');

        $validator
            ->scalar('path')
            ->requirePresence('path', 'create')
            ->notEmptyString('path');

        return $validator;
    }

    /**
     * The content this hash stands for, where it is already on file.
     *
     * Both halves, because the algorithm is part of the answer: the same string worked out two
     * ways is two different pieces of content as far as anything here is concerned.
     *
     * @param \Cake\ORM\Query\SelectQuery<\Files\Model\Entity\File> $query The query.
     * @param string $hash The hash.
     * @param string $hash_type What worked it out.
     * @return \Cake\ORM\Query\SelectQuery<\Files\Model\Entity\File>
     */
    public function findByHash(SelectQuery $query, string $hash, string $hash_type): SelectQuery
    {
        return $query->where([
            $this->aliasField('hash') => $hash,
            $this->aliasField('hash_type') => $hash_type,
        ]);
    }

    /**
     * Content nothing points at any more.
     *
     * Bytes without a row are the harmless way round for a backup to be torn - they are found
     * here and thrown away, whereas a row without bytes cannot be put right at all.
     *
     * @param \Cake\ORM\Query\SelectQuery<\Files\Model\Entity\File> $query The query.
     * @return \Cake\ORM\Query\SelectQuery<\Files\Model\Entity\File>
     */
    public function findUnused(SelectQuery $query): SelectQuery
    {
        return $query->where(function ($exp, SelectQuery $q) {
            return $exp->notExists(
                $q->getConnection()->selectQuery()
                    ->select(['1'])
                    ->from(['FileLinksCheck' => 'file_links'])
                    ->where(['FileLinksCheck.file_id' => $q->identifier($this->aliasField('id'))]),
            );
        });
    }
}
