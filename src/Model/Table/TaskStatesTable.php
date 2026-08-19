<?php
declare(strict_types=1);

namespace App\Model\Table;

use Tasks\Model\Table\TaskStatesTable as TasksTaskStatesTable;

/**
 * TaskStates Model
 *
 * A task state is the same thing in both applications, so the whole of it lives in the plugin.
 * This stands here because the table locator resolves an alias without a plugin prefix into this
 * namespace, which is what every association naming `TaskStates` goes through.
 *
 * @property \App\Model\Table\TasksTable&\Cake\ORM\Association\HasMany $Tasks
 * @method \App\Model\Entity\TaskState newEmptyEntity()
 * @method \App\Model\Entity\TaskState newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\TaskState[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\TaskState get(mixed $primaryKey, array|string $finder = 'all', null|\Psr\SimpleCache\CacheInterface|string $cache = null, null|\Closure|string $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\TaskState findOrCreate($search, callable|array|null $callback = null, $options = [])
 * @method \App\Model\Entity\TaskState patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\TaskState[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\TaskState|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\TaskState saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method iterable<\App\Model\Entity\TaskState>|false saveMany(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\TaskState> saveManyOrFail(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\TaskState>|false deleteMany(iterable $entities, $options = [])
 * @method iterable<\App\Model\Entity\TaskState> deleteManyOrFail(iterable $entities, $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class TaskStatesTable extends TasksTaskStatesTable
{
}
