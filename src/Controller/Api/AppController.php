<?php
declare(strict_types=1);

namespace App\Controller\Api;

use Access\Authentication\BearerRequest;
use App\Controller\AppController as BaseController;
use Cake\View\JsonView;
use Override;

/**
 * Shared ground for what this application answers over its API.
 *
 * Everything here speaks JSON.
 *
 * Writing is open only to a client that sends its key as a Bearer token in the `Authorization`
 * header. Such a request has no cookie to forge, so CSRF and FormProtection are skipped for it;
 * a request with `api_key` in the address, or from this application's own JavaScript, keeps both
 * guards. What a client may write is still decided by its role in `config/permissions.php`.
 */
class AppController extends BaseController
{
    /**
     * Initialization hook method.
     *
     * @return void
     */
    #[Override]
    public function initialize(): void
    {
        parent::initialize();

        if (BearerRequest::isApi($this->getRequest()) && $this->components()->has('FormProtection')) {
            $this->getEventManager()->off($this->components()->get('FormProtection'));
        }
    }

    /**
     * Returns supported output types
     */
    #[Override]
    public function viewClasses(): array
    {
        return [JsonView::class];
    }
}
