<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

/**
 * Single database table
 *
 * @property \CakeDC\Users\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Creators
 * @property \CakeDC\Users\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Modifiers
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
    }
}
