<?php
declare(strict_types=1);

namespace App\Model\Table;

use Tasks\Model\Table\TaskCollaboratorsTable as TasksTaskCollaboratorsTable;

/**
 * TaskCollaborators Model
 *
 * Who works on a task beside whoever holds it is the same thing in both applications, so the
 * whole of it lives in the plugin. This stands here because the table locator resolves an alias
 * without a plugin prefix into this namespace, which is what every association naming
 * `TaskCollaborators` goes through.
 *
 * @property \App\Model\Table\TasksTable&\Cake\ORM\Association\BelongsTo $Tasks
 * @property \App\Model\Table\AppUsersTable&\Cake\ORM\Association\BelongsTo $Users
 * @method \App\Model\Entity\TaskCollaborator newEmptyEntity()
 * @method \App\Model\Entity\TaskCollaborator newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\TaskCollaborator[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\TaskCollaborator get(mixed $primaryKey, array|string $finder = 'all', null|\Psr\SimpleCache\CacheInterface|string $cache = null, null|\Closure|string $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\TaskCollaborator findOrCreate($search, callable|array|null $callback = null, $options = [])
 * @method \App\Model\Entity\TaskCollaborator patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\TaskCollaborator[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\TaskCollaborator|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\TaskCollaborator saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method iterable<\App\Model\Entity\TaskCollaborator>|false saveMany(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\TaskCollaborator> saveManyOrFail(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\TaskCollaborator>|false deleteMany(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\TaskCollaborator> deleteManyOrFail(iterable $entities, $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class TaskCollaboratorsTable extends TasksTaskCollaboratorsTable
{
}
