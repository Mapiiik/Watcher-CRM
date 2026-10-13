<?php
declare(strict_types=1);

namespace Access\Social;

use Cake\Core\Configure;

/**
 * The identity providers people may sign in with: those the configuration gives a client for,
 * while signing in with them is turned on.
 */
class Providers
{
    /**
     * The names of the providers, as they appear in the addresses.
     *
     * @return list<string>
     */
    public static function names(): array
    {
        if (!Configure::read('Users.Social.login')) {
            return [];
        }

        $names = [];
        foreach ((array)Configure::read('OAuth.providers') as $name => $provider) {
            $options = (array)($provider['options'] ?? []);
            if (!empty($options['clientId']) && !empty($options['clientSecret'])) {
                $names[] = (string)$name;
            }
        }

        return $names;
    }

    /**
     * The name of a provider as people know it.
     *
     * @param string $name The name in the addresses.
     * @return string
     */
    public static function label(string $name): string
    {
        return (string)Configure::read('OAuth.providers.' . $name . '.label', ucfirst($name));
    }
}
