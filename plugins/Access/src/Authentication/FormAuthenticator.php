<?php
declare(strict_types=1);

namespace Access\Authentication;

use Authentication\Identifier\IdentifierInterface;
use CakeDC\Auth\Authenticator\FormAuthenticator as BaseFormAuthenticator;

/**
 * Signing in with a username and a password.
 */
class FormAuthenticator extends BaseFormAuthenticator
{
    /**
     * The identifiers that check the password.
     *
     * Signing in asks for them to store a password hashed the old way again, and finds nothing to
     * ask without this.
     *
     * @return \Authentication\Identifier\IdentifierInterface
     */
    public function getIdentifier(): IdentifierInterface
    {
        return $this->identifier;
    }
}
