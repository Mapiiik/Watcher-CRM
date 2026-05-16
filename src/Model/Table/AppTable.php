<?php
declare(strict_types=1);

namespace App\Model\Table;

use AuditStash\Persister\TablePersister;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Table;
use Override;

/**
 * Single database table
 *
 * @extends \Cake\ORM\Table<array<string, \Cake\ORM\Behavior>>
 * @property \App\Model\Table\AppUsersTable&\Cake\ORM\Association\BelongsTo $Creators
 * @property \App\Model\Table\AppUsersTable&\Cake\ORM\Association\BelongsTo $Modifiers
 * @property \App\Model\Table\AppUsersTable&\Cake\ORM\Association\BelongsTo $Removers
 * @property \App\Model\Table\AppUsersTable&\Cake\ORM\Association\BelongsTo $Revokers
 * @property \App\Model\Table\AppUsersTable&\Cake\ORM\Association\BelongsTo $Archivers
 */
class AppTable extends Table
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

        // Persisting audit log
        $this->addBehavior('AuditStash.AuditLog');
        /** @var \AuditStash\Model\Behavior\AuditLogBehavior $auditLog */
        $auditLog = $this->getBehavior('AuditLog');
        /** @var \AuditStash\Persister\TablePersister $auditLogPersister */
        $auditLogPersister = $auditLog->persister();
        $auditLogPersister->setConfig([
            'serializeFields' => false,
            'primaryKeyExtractionStrategy' => TablePersister::STRATEGY_RAW,
        ]);

        if ($this->hasField('created_by')) {
            $this->belongsTo('Creators', [
                'className' => 'AppUsers',
                'foreignKey' => 'created_by',
            ]);
        }
        if ($this->hasField('modified_by')) {
            $this->belongsTo('Modifiers', [
                'className' => 'AppUsers',
                'foreignKey' => 'modified_by',
            ]);
        }
        if ($this->hasField('removed_by')) {
            $this->belongsTo('Removers', [
                'className' => 'AppUsers',
                'foreignKey' => 'removed_by',
            ]);
        }
        if ($this->hasField('revoked_by')) {
            $this->belongsTo('Revokers', [
                'className' => 'AppUsers',
                'foreignKey' => 'revoked_by',
            ]);
        }
        if ($this->hasField('archived_by')) {
            $this->belongsTo('Archivers', [
                'className' => 'AppUsers',
                'foreignKey' => 'archived_by',
            ]);
        }
    }

    /**
     * Finds an existing record or prepare a new entity.
     *
     * @param array<string, mixed> $search Data to be searched in existing records or added to new entity
     * @return \Cake\Datasource\EntityInterface An entity.
     */
    public function findOrNewEntity(array $search): EntityInterface
    {
        $row = $this->find()->where($search)->first();
        if ($row instanceof EntityInterface) {
            return $row;
        }

        return $this->newEntity($search);
    }
}
