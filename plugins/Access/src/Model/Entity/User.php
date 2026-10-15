<?php
declare(strict_types=1);

namespace Access\Model\Entity;

use Access\Authentication\Argon2idPasswordHasher;
use Access\Model\Table\UsersTable;
use Cake\ORM\Entity;

/**
 * An account people sign in with, shared by the applications.
 *
 * Each application extends this as its own `User` and says which roles it knows.
 *
 * @property string $id
 * @property int $nid
 * @property string $username
 * @property string|null $email
 * @property string|null $password
 * @property string|null $first_name
 * @property string|null $last_name
 * @property string|null $role
 * @property bool $active
 * @property bool $is_superuser
 * @property string|null $api_token
 * @property string|null $token
 * @property \Cake\I18n\DateTime|null $token_expires
 * @property string|null $secret
 * @property bool $secret_verified
 * @property bool|null $password_sign_in
 * @property int|null $otp_last_timestep
 * @property \Cake\I18n\DateTime|null $last_granted
 * @property string|null $last_granted_ip
 * @property \Cake\I18n\DateTime|null $last_denied
 * @property string|null $last_denied_ip
 * @property array|null $user_settings
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 * @property array<\Access\Model\Entity\SocialAccount> $social_accounts
 * @property string $name
 * @property string $name_for_lists
 */
class User extends Entity
{
    /**
     * Fields left out wherever the account is written out whole.
     *
     * The last is the row of a many-to-many association, which travels with a person named on a
     * record and says something about the record, not about them.
     *
     * @var list<string>
     */
    protected array $_hidden = [
        'api_token',
        'password',
        'secret',
        'token',
        'token_expires',
        '_joinData',
    ];

    /**
     * What the forms of an account set. Anything else - the password, the keys, the settings,
     * what signing in keeps - is set by the action that looks after it, naming it.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'username' => true,
        'email' => true,
        'first_name' => true,
        'last_name' => true,
        'role' => true,
        'active' => true,
    ];

    /**
     * Keeps a password as its Argon2id hash.
     *
     * @param string $password The password.
     * @return string
     */
    protected function _setPassword(string $password): string
    {
        return (new Argon2idPasswordHasher())->hash($password);
    }

    /**
     * Whether a password is the one the account has, as read with the finder `withSecrets`.
     *
     * @param string $password The password.
     * @return bool
     */
    public function checkPassword(string $password): bool
    {
        return (new Argon2idPasswordHasher())->check($password, (string)$this->get('password'));
    }

    /**
     * Whether the account signs in with two-step verification, by any of its methods. The one
     * method so far is the authenticator application (TOTP).
     *
     * @return bool
     */
    public function hasTwoStepVerification(): bool
    {
        return (bool)$this->get('secret_verified');
    }

    /**
     * Whether the account signs in with a password at all. An account without one signs in only
     * with a key or through an identity provider.
     *
     * An account is read with whether it has a password rather than the password, see
     * UsersTable::beforeFind(); one read with its secrets has the password itself.
     *
     * @return bool
     */
    public function hasPasswordSignIn(): bool
    {
        $known = $this->get(UsersTable::PASSWORD_SIGN_IN);

        return $known !== null ? (bool)$known : $this->get('password') !== null;
    }

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
     * What the person is called.
     *
     * The account name belongs to {@see _getNameForLists()} rather than here. A list has to tell
     * two people of the same name apart, and says which account each one is; a cell standing in a
     * row that is already about one task has nothing to tell apart, and the brackets only get in
     * the way of reading it.
     *
     * @return string
     */
    protected function _getName(): string
    {
        $name = implode(' ', array_filter([
            $this->first_name,
            $this->last_name,
        ]));

        // An account with no name filled in still has to read as somebody rather than as an empty
        // cell, and the only thing left to call it by is the account itself.
        return $name !== '' ? $name : (string)$this->username;
    }

    /**
     * What the person is called, surname first and with the account named, for picking one out
     * of a list. An account with no name filled in is the account alone.
     *
     * @return string
     */
    protected function _getNameForLists(): string
    {
        $name = implode(' ', array_filter([
            $this->last_name,
            $this->first_name,
        ]));

        return $name !== '' ? $name . ' (' . $this->username . ')' : (string)$this->username;
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
        $role = (string)$this->role;

        return $this->getRoleOptions()[$role] ?? $role;
    }
}
