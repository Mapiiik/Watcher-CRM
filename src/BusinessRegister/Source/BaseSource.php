<?php
declare(strict_types=1);

namespace App\BusinessRegister\Source;

use App\BusinessRegister\Dto\Subject;
use App\BusinessRegister\Provider\SubjectPayloadNormalizer;
use App\Http\Answer;
use Cake\Collection\CollectionInterface;
use Cake\Http\Client;
use Closure;
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
        return $this->url() . '/' . ltrim($path, '/');
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
     * Ask the register one thing.
     *
     * Not being configured is a state, not a failure - a register that was never given an address
     * was not asked, and saying it went wrong would put an outage where there is none.
     *
     * @param \Closure(): \Cake\Http\Client\Response $ask How to ask.
     * @param bool $missingIsAnAnswer Whether a 404 means the register holds no such entry.
     * @return \App\Http\Answer<array<int|string, mixed>|null>
     */
    private function ask(Closure $ask, bool $missingIsAnAnswer = false): Answer
    {
        if ($this->url() === '') {
            return Answer::notAsked();
        }

        try {
            $response = $ask();
        } catch (Throwable $e) {
            return Answer::failed(__(
                'The {0} register is unreachable: {1}',
                $this->label(),
                $e->getMessage(),
            ));
        }

        if ($missingIsAnAnswer && $response->getStatusCode() === 404) {
            return Answer::of(null);
        }

        if (!$response->isOk()) {
            return Answer::failed(__(
                'The {0} register returned HTTP {1}.',
                $this->label(),
                $response->getStatusCode(),
            ));
        }

        $data = $response->getJson();
        if (!is_array($data)) {
            return Answer::failed(__('The {0} register returned an invalid response.', $this->label()));
        }

        return Answer::of($data);
    }

    /**
     * Read one thing that the other side either holds or does not.
     *
     * A 404 is then an answer rather than a failure: it says there is no such thing, which is what
     * the caller asked to find out.
     *
     * @param \Closure(): \Cake\Http\Client\Response $ask How to ask.
     * @return \App\Http\Answer<array<int|string, mixed>|null>
     */
    protected function readOrMissing(Closure $ask): Answer
    {
        return $this->ask($ask, missingIsAnAnswer: true);
    }

    /**
     * Read one thing the other side is expected to hold.
     *
     * @param \Closure(): \Cake\Http\Client\Response $ask How to ask.
     * @return \App\Http\Answer<array<int|string, mixed>>
     */
    protected function read(Closure $ask): Answer
    {
        /** @var \App\Http\Answer<array<int|string, mixed>> $answer */
        $answer = $this->ask($ask, missingIsAnAnswer: false);

        return $answer;
    }

    /**
     * An entry read into the shape every register answers in.
     *
     * @param array<string, mixed>|null $mapped The entry as this register mapped it.
     * @return \App\BusinessRegister\Dto\Subject|null
     */
    protected static function toSubject(?array $mapped): ?Subject
    {
        return $mapped === null ? null : SubjectPayloadNormalizer::subject($mapped);
    }

    /**
     * Several entries read into that shape.
     *
     * @param list<array<string, mixed>> $mapped The entries as this register mapped them.
     * @return \Cake\Collection\CollectionInterface<int, \App\BusinessRegister\Dto\Subject>
     */
    protected static function toSubjects(array $mapped): CollectionInterface
    {
        return SubjectPayloadNormalizer::subjects($mapped);
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
