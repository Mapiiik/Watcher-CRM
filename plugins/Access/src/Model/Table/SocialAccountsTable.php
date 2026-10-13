<?php
declare(strict_types=1);

namespace Access\Model\Table;

use Cake\Core\Configure;
use Cake\Datasource\EntityInterface;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Override;

/**
 * Identities at other providers, each linked to the account it signs in.
 *
 * Only what tells the identity apart is kept: the provider, its reference there and the name it
 * goes by. Neither the provider's tokens nor the profile it sends.
 *
 * @method \Access\Model\Entity\SocialAccount newEmptyEntity()
 * @method \Access\Model\Entity\SocialAccount|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 */
class SocialAccountsTable extends Table
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

        $this->setTable('social_accounts');
        $this->setDisplayField('username');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');
        $this->addBehavior('AuditStash.AuditLog');

        $this->belongsTo('Users', [
            'className' => (string)Configure::read('Users.table', 'Access.Users'),
            'foreignKey' => 'user_id',
            'joinType' => 'INNER',
        ]);
    }

    /**
     * An identity signs in one account only.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    #[Override]
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->isUnique(['provider', 'reference']), 'identityLinkedOnce', [
            'errorField' => 'reference',
            'message' => __d('access', 'This identity is already linked to another account.'),
        ]);
        $rules->add($rules->existsIn('user_id', 'Users'));

        return $rules;
    }

    /**
     * Links an identity to an account, or keeps the link it has.
     *
     * @param string $userId The account.
     * @param string $provider The provider, as in the addresses.
     * @param string $reference What the provider calls the identity.
     * @param string|null $username The name the identity goes by.
     * @return \Cake\Datasource\EntityInterface The link, with errors where it could not be made.
     */
    public function link(string $userId, string $provider, string $reference, ?string $username): EntityInterface
    {
        $link = $this->find()
            ->where(['user_id' => $userId, 'provider' => $provider, 'reference' => $reference])
            ->first() ?? $this->newEntity([
                'user_id' => $userId,
                'provider' => $provider,
                'reference' => $reference,
            ]);
        $link->patch(['username' => $username, 'active' => true]);
        $this->save($link);

        return $link;
    }
}
