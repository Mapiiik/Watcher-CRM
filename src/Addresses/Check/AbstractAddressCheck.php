<?php
declare(strict_types=1);

namespace App\Addresses\Check;

/**
 * Shared ground for address checks - the defaults a check only overrides where it differs.
 */
abstract class AbstractAddressCheck implements AddressCheckInterface
{
    /**
     * The template is named after the check unless it says otherwise.
     *
     * @return string
     */
    public function template(): string
    {
        return $this->id();
    }

    /**
     * Checks are counted on the dashboard unless they say otherwise.
     *
     * @return bool
     */
    public function onDashboard(): bool
    {
        return true;
    }

    /**
     * Checks are listed without being asked for unless they say otherwise.
     *
     * @return bool
     */
    public function optional(): bool
    {
        return false;
    }

    /**
     * Counting is asking the same query how many rows it has.
     *
     * @return int
     */
    public function count(): int
    {
        return $this->find()->count();
    }
}
