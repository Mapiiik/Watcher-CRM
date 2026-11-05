<?php
declare(strict_types=1);

namespace App\Service;

use RuntimeException;

/**
 * A billing change the records refused, with what they said about it.
 */
final class BillingChangeRefused extends RuntimeException
{
    /**
     * @param array<mixed> $errors The validation errors, by field.
     */
    public function __construct(public readonly array $errors)
    {
        parent::__construct('The billing could not be saved.');
    }
}
