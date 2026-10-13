<?php
declare(strict_types=1);

namespace Access\Model\Entity;

use Access\Authentication\Argon2idPasswordHasher;
use Authentication\PasswordHasher\PasswordHasherInterface;
use Cake\Core\Configure;
use CakeDC\Users\Model\Entity\User as BaseUser;
use Override;

/**
 * An account people sign in with, shared by the applications.
 *
 * Each application extends this as its `AppUser` and says which roles it knows.
 *
 * @property string $id
 * @property string|null $first_name
 * @property string|null $last_name
 * @property bool $active
 * @property \Cake\I18n\Date|null $activation_date
 * @property \Cake\I18n\Date|null $tos_date
 * @property string|null $secret
 * @property bool $secret_verified
 * @property \Cake\I18n\Date|null $last_login
 * @property \Cake\I18n\Date $created
 * @property \Cake\I18n\Date $modified
 */
class User extends BaseUser
{
    /**
     * Fields left out wherever the account is written out whole.
     *
     * The first six are the plugin's own list, repeated because a property replaces the one it
     * inherits rather than adding to it. The last is the row of a many-to-many association, which
     * travels with a person named on a record and says something about the record, not about them.
     *
     * @var list<string>
     */
    protected array $_hidden = [
        'additional_data',
        'api_token',
        'password',
        'secret',
        'token',
        'token_expires',
        '_joinData',
    ];

    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        '*' => true,
        'id' => false,
        'is_superuser' => false,
        'role' => true,
        // set by signing in and by the actions that look after them, never by a form
        'secret' => false,
        'secret_verified' => false,
        'otp_last_timestep' => false,
        'token' => false,
        'token_expires' => false,
        'login_token' => false,
    ];

    /**
     * Keeps an API key as its SHA-256 only. A key is looked up by its hash, so nothing readable is
     * kept, and a key can be shown only when it is made.
     *
     * @param string|null $token The key.
     * @return string|null
     */
    protected function _setApiToken(?string $token): ?string
    {
        return $token === null || $token === '' ? $token : hash('sha256', $token);
    }

    /**
     * The hasher passwords are stored with: the one the configuration names, or Argon2id where it
     * names none, as before the application is started.
     *
     * @return \Authentication\PasswordHasher\PasswordHasherInterface
     */
    #[Override]
    public function getPasswordHasher(): PasswordHasherInterface
    {
        $class = Configure::read('Users.passwordHasher');
        if (is_string($class) && is_a($class, PasswordHasherInterface::class, true)) {
            return new $class();
        }

        return new Argon2idPasswordHasher();
    }

    /**
     * The roles an account may have, keyed by what is stored. Each application lists its own.
     *
     * @return array<string, string>
     */
    public function getRoleOptions(): array
    {
        return [];
    }

    /**
     * Get role name method
     *
     * @return string
     */
    public function getRoleName(): string
    {
        return $this->getRoleOptions()[$this->role] ?? $this->role;
    }
}
