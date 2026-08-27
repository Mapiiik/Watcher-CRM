<?php
declare(strict_types=1);

namespace App\Check;

use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * Shared ground for the registries that hold a family of checks.
 *
 * A registry is the single extension point of its family: register a check in the
 * constructor, give it a template beside the others, and both the dashboard card and the
 * overview pick it up. Checks are built lazily, so registering one costs nothing until it is
 * asked something - which matters on the overview, where a check nobody ticked must not run
 * its query.
 *
 * @template TCheck of \App\Check\CheckInterface
 */
abstract class AbstractCheckRegistry
{
    use LocatorAwareTrait;

    /**
     * Registered in the order they are listed, by the constructor of the family.
     *
     * @var array<string, callable(): TCheck>
     */
    protected array $factories = [];

    /**
     * @var array<string, TCheck>
     */
    private array $built = [];

    /**
     * The check registered under the given id, or null where there is none.
     *
     * @param string $id Registry key.
     * @return TCheck|null
     */
    public function get(string $id): ?CheckInterface
    {
        if (!isset($this->factories[$id])) {
            return null;
        }

        // A check is asked how many records it found and then asked for them, so it is kept
        // rather than built twice.
        return $this->built[$id] ??= ($this->factories[$id])();
    }

    /**
     * Every check, in the order they are registered.
     *
     * @return list<TCheck>
     */
    public function all(): array
    {
        $checks = [];
        foreach (array_keys($this->factories) as $id) {
            $check = $this->get($id);
            if ($check !== null) {
                $checks[] = $check;
            }
        }

        return $checks;
    }

    /**
     * The checks the dashboard card counts.
     *
     * @return list<TCheck>
     */
    public function forDashboard(): array
    {
        return array_values(array_filter(
            $this->all(),
            fn(CheckInterface $check): bool => $check->onDashboard(),
        ));
    }
}
