<?php
declare(strict_types=1);

namespace Tasks\Model\Rule;

use Cake\Datasource\EntityInterface;

/**
 * A task type may insist that a task filed under it says what it is about.
 *
 * Which link that is differs between the applications - a customer, a contract, an access point -
 * but the shape never does: a flag on the type names a field on the task that then cannot be left
 * empty. Each application says which pair it means and this holds the rest.
 */
final class RequiredLinkRule
{
    /**
     * @param string $flag Column on the task type that says the link is required.
     * @param string $field Column on the task that then has to be filled in.
     */
    public function __construct(
        private readonly string $flag,
        private readonly string $field,
    ) {
    }

    /**
     * @param \Cake\Datasource\EntityInterface $entity The task being saved.
     * @param array<string, mixed> $options Options the rules checker was given.
     * @return bool
     */
    public function __invoke(EntityInterface $entity, array $options): bool
    {
        $taskType = $this->taskType($entity, $options);

        // a type that is not there is for `existsIn` to report, not for this rule
        if ($taskType === null || !$taskType->get($this->flag)) {
            return true;
        }

        return !empty($entity->get($this->field));
    }

    /**
     * The task type a task names, or null where it names none or one that is not there.
     *
     * A checker runs every rule it holds rather than stopping at the first one to fail, so this
     * runs on whatever the `existsIn` beside it made of the same field. Reading the type with
     * `get()` would therefore throw out of the rules rather than fail them, and a caller waiting
     * for a `false` would get an exception instead.
     *
     * @param \Cake\Datasource\EntityInterface $entity The task being saved.
     * @param array<string, mixed> $options Options the rules checker was given.
     * @return \Cake\Datasource\EntityInterface|null
     */
    private function taskType(EntityInterface $entity, array $options): ?EntityInterface
    {
        $taskTypeId = $entity->get('task_type_id');
        if (empty($taskTypeId)) {
            return null;
        }

        /** @var \Cake\ORM\Table $tasks */
        $tasks = $options['repository'];
        $table = $tasks->getAssociation('TaskTypes')->getTarget();

        return $table->find()->where([$table->aliasField('id') => $taskTypeId])->first();
    }
}
