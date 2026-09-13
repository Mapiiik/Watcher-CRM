<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\Validation\Validator;
use Files\Model\Table\DocumentationTypesTable as FilesDocumentationTypesTable;
use Override;

/**
 * DocumentationTypes Model
 *
 * On top of the shared kind: what this application lets a kind require of the folders under it.
 *
 * @property \App\Model\Table\DocumentationsTable&\Cake\ORM\Association\HasMany $Documentations
 * @method \App\Model\Entity\DocumentationType newEmptyEntity()
 * @method \App\Model\Entity\DocumentationType newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\DocumentationType[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\DocumentationType get(mixed $primaryKey, array|string $finder = 'all', null|\Psr\SimpleCache\CacheInterface|string $cache = null, null|\Closure|string $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\DocumentationType findOrCreate($search, callable|array|null $callback = null, $options = [])
 * @method \App\Model\Entity\DocumentationType patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\DocumentationType[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\DocumentationType|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\DocumentationType saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method iterable<\App\Model\Entity\DocumentationType>|false saveMany(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\DocumentationType> saveManyOrFail(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\DocumentationType>|false deleteMany(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\DocumentationType> deleteManyOrFail(iterable $entities, $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class DocumentationTypesTable extends FilesDocumentationTypesTable
{
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
            ->boolean('customer_required')
            ->notEmptyString('customer_required');

        $validator
            ->boolean('contract_required')
            ->notEmptyString('contract_required');

        return $validator;
    }
}
