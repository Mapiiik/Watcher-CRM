<?php
declare(strict_types=1);

namespace Files\Model\Table;

use App\Model\Table\AppTable;
use Override;

/**
 * RemovedFileLinks Model
 *
 * The trash. A row keeps what the link said, so neither `Timestamp` nor `Footprint` is added here:
 * who filed it and when stay what they were, and `removed` says the rest.
 *
 * @property \Files\Model\Table\FilesTable&\Cake\ORM\Association\BelongsTo $Files
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Creators
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Modifiers
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Removers
 * @method \Files\Model\Entity\RemovedFileLink newEmptyEntity()
 * @method \Files\Model\Entity\RemovedFileLink newEntity(array $data, array $options = [])
 * @method \Files\Model\Entity\RemovedFileLink[] newEntities(array $data, array $options = [])
 * @method \Files\Model\Entity\RemovedFileLink get(mixed $primaryKey, array|string $finder = 'all', null|\Psr\SimpleCache\CacheInterface|string $cache = null, null|\Closure|string $cacheKey = null, mixed ...$args)
 * @method \Files\Model\Entity\RemovedFileLink findOrCreate($search, callable|array|null $callback = null, $options = [])
 * @method \Files\Model\Entity\RemovedFileLink patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Files\Model\Entity\RemovedFileLink[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Files\Model\Entity\RemovedFileLink|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Files\Model\Entity\RemovedFileLink saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method iterable<\Files\Model\Entity\RemovedFileLink>|false saveMany(iterable $entities, $options = [])
 * @method iterable<\Files\Model\Entity\RemovedFileLink> saveManyOrFail(iterable $entities, $options = [])
 * @method iterable<\Files\Model\Entity\RemovedFileLink>|false deleteMany(iterable $entities, $options = [])
 * @method iterable<\Files\Model\Entity\RemovedFileLink> deleteManyOrFail(iterable $entities, $options = [])
 */
class RemovedFileLinksTable extends AppTable
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

        $this->setTable('removed_file_links');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->getSchema()->setColumnType('meta', 'json');
        $this->getSchema()->setColumnType('documentation', 'json');

        $this->belongsTo('Files', [
            'className' => 'Files.Files',
            'foreignKey' => 'file_id',
            'joinType' => 'INNER',
        ]);
    }
}
