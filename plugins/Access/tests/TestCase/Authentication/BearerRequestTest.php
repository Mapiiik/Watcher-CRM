<?php
declare(strict_types=1);

namespace Access\Test\TestCase\Authentication;

use Access\Authentication\BearerRequest;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * Access\Authentication\BearerRequest Test Case
 */
#[UsesClass(BearerRequest::class)]
class BearerRequestTest extends TestCase
{
    /**
     * Only an API request with a Bearer token counts as one from an API client, the only kind let
     * past the CSRF check.
     *
     * @return void
     */
    public function testOnlyAnApiRequestWithABearerTokenIsFromAnApiClient(): void
    {
        $request = fn(string $url, string $authorization): ServerRequest => new ServerRequest([
            'url' => $url,
            'environment' => $authorization === '' ? [] : ['HTTP_AUTHORIZATION' => $authorization],
        ]);

        $this->assertTrue(BearerRequest::isApi($request('/api/customers.json', 'Bearer key')));
        $this->assertTrue(BearerRequest::isApi($request('/api/customers.json', 'bearer key')));
        $this->assertFalse(BearerRequest::isApi($request('/api/customers.json', '')));
        $this->assertFalse(BearerRequest::isApi($request('/api/customers.json', 'Basic a2V5')));
        $this->assertFalse(BearerRequest::isApi($request('/customers', 'Bearer key')));
    }
}
