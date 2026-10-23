<?php
declare(strict_types=1);

namespace Access\Social\Mapper;

use CakeDC\Auth\Social\Mapper\AbstractMapper;

/**
 * An identity at an OpenID Connect provider, from the claims of its ID token.
 *
 * Its reference is `sub`, which the provider keeps for the person. The methods it signed in with,
 * what the provider said of the second factor, and the ID token for ending the session there go
 * along with it.
 */
class Oidc extends AbstractMapper
{
    /**
     * @var array<string, string>
     */
    protected array $_mapFields = [
        'id' => 'sub',
        'username' => 'preferred_username',
        'full_name' => 'name',
        'first_name' => 'given_name',
        'last_name' => 'family_name',
        'email' => 'email',
        'methods' => 'amr',
        'second_factor_at_provider' => 'second_factor_at_provider',
        'id_token' => 'id_token',
    ];
}
