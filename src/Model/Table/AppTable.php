<?php
declare(strict_types=1);

namespace App\Model\Table;

use AuditLog\Persister\TablePersister;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Table;

/**
 * Single database table
 *
 * @property \CakeDC\Users\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Creators
 * @property \CakeDC\Users\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Modifiers
 * @property \CakeDC\Users\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Removers
 */
class AppTable extends Table
{
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        // Persisting audit log
        $this->addBehavior('AuditLog.AuditLog');
        /** @var \AuditLog\Model\Behavior\AuditLogBehavior $auditLog */
        $auditLog = $this->getBehavior('AuditLog');
        /** @var \AuditLog\Persister\TablePersister $auditLogPersister */
        $auditLogPersister = $auditLog->persister();
        $auditLogPersister->setConfig([
            'serializeFields' => false,
            'primaryKeyExtractionStrategy' => TablePersister::STRATEGY_RAW,
        ]);

        if ($this->hasField('created_by')) {
            $this->belongsTo('Creators', [
                'className' => 'CakeDC/Users.Users',
                'foreignKey' => 'created_by',
            ]);
        }
        if ($this->hasField('modified_by')) {
            $this->belongsTo('Modifiers', [
                'className' => 'CakeDC/Users.Users',
                'foreignKey' => 'modified_by',
            ]);
        }
        if ($this->hasField('removed_by')) {
            $this->belongsTo('Removers', [
                'className' => 'CakeDC/Users.Users',
                'foreignKey' => 'removed_by',
            ]);
        }
    }

    /**
     * Finds an existing record or prepare a new entity.
     *
     * @param array $search Data to be searched in existing records or added to new entity
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
