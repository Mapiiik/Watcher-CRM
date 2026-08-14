<?php
declare(strict_types=1);

namespace App\BusinessRegister\Source;

use Cake\Http\Client;
use Cake\Http\Client\Response;
use RuntimeException;
use Settings\Utility\Settings;
use Throwable;

/**
 * What every register needs whichever one it is: where its settings live, a client to ask
 * with, and one way of turning a refused request into an exception.
 */
abstract class BaseSource implements SourceInterface
{
    /**
     * @inheritDoc
     */
    public function isConfigured(): bool
    {
        return $this->isEnabled() && $this->url() !== '';
    }

    /**
     * Whether the register has been turned on in the settings.
     *
     * @return bool
     */
    protected function isEnabled(): bool
    {
        return (bool)Settings::get($this->settingsPath('enabled'), false);
    }

    /**
     * The address the register answers at, without its trailing slash.
     *
     * @return string
     */
    protected function url(): string
    {
        return rtrim($this->setting('url'), '/');
    }

    /**
     * A single setting of this register, trimmed - a value padded in the settings form is a
     * value, not a difference.
     *
     * @param string $name The name below the register's own block.
     * @return string
     */
    protected function setting(string $name): string
    {
        return trim(Settings::getString($this->settingsPath($name)));
    }

    /**
     * Where a setting of this register is read from.
     *
     * @param string $name The name below the register's own block.
     * @return string
     */
    private function settingsPath(string $name): string
    {
        return 'core.business_register.' . $this->key() . '.' . $name;
    }

    /**
     * Resolve a relative path against the address the register answers at.
     *
     * @param string $path The path below the register's address.
     * @return string
     */
    protected function endpoint(string $path): string
    {
        $url = $this->url();
        if ($url === '') {
            throw new RuntimeException(__('The {0} register is not configured.', $this->label()));
        }

        return $url . '/' . ltrim($path, '/');
    }

    /**
     * Build the client the register is asked with.
     *
     * @param array<string, string> $headers Anything the register wants beyond an answer in JSON.
     * @param int $timeout How long to wait, in seconds.
     * @return \Cake\Http\Client
     */
    protected function http(array $headers = [], int $timeout = 15): Client
    {
        return new Client([
            'headers' => ['Accept' => 'application/json'] + $headers,
            'timeout' => $timeout,
        ]);
    }

    /**
     * The decoded body, or an exception naming the register that refused.
     *
     * @param \Cake\Http\Client\Response $response The response to read.
     * @return array<int|string, mixed>
     */
    protected function decodeOrThrow(Response $response): array
    {
        if (!$response->isOk()) {
            throw new RuntimeException(__(
                'The {0} register returned HTTP {1}.',
                $this->label(),
                $response->getStatusCode(),
            ));
        }

        $data = $response->getJson();
        if (!is_array($data)) {
            throw new RuntimeException(__('The {0} register returned an invalid response.', $this->label()));
        }

        return $data;
    }

    /**
     * Turn whatever went wrong on the way into one exception the caller can show.
     *
     * @param \Throwable $e What the transport threw.
     * @return \RuntimeException
     */
    protected function unreachable(Throwable $e): RuntimeException
    {
        return new RuntimeException(
            __('The {0} register is unreachable: {1}', $this->label(), $e->getMessage()),
            $e->getCode(),
            previous: $e,
        );
    }

    /**
     * The value without the whitespace it may have been typed with.
     *
     * @param string $value The value as it was entered.
     * @return string
     */
    protected static function withoutWhitespace(string $value): string
    {
        return (string)preg_replace('/\s+/', '', $value);
    }
}
