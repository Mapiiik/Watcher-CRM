<?php
declare(strict_types=1);

namespace App\Controller\Traits;

use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\Routing\Router;
use Psr\Http\Message\UriInterface;

/**
 * @psalm-require-extends \Cake\Controller\Controller
 * @method \Cake\Http\ServerRequest getRequest()
 */
trait RedirectionTrait
{
    /**
     * Redirect to proper path after add
     *
     * @param \Psr\Http\Message\UriInterface|array<mixed>|string $url A string, array-based URL or UriInterface instance.
     * @param int $status HTTP status code. Defaults to `302`.
     * @return \Cake\Http\Response|null
     */
    public function afterAddRedirect(UriInterface|array|string $url, int $status = 302): ?Response
    {
        // other behavior if not in win-link
        if (!($this->getRequest()->getQuery('win-link') == 'true')) {
            # Redirect to contract card if contract ID is known
            if (isset($this->contract_id) && $this->getRequest()->getParam('controller') !== 'Contracts') {
                return $this->redirect([
                    'plugin' => null,
                    'controller' => 'Contracts',
                    'action' => 'view',
                    $this->contract_id,
                ]);
            }

            # Redirect to customer card if customer ID is known (not for contracts)
            if (
                isset($this->customer_id)
                && $this->getRequest()->getParam('controller') !== 'Customers'
                && $this->getRequest()->getParam('controller') !== 'Contracts'
            ) {
                return $this->redirect([
                    'plugin' => null,
                    'controller' => 'Customers',
                    'action' => 'view',
                    $this->customer_id,
                ]);
            }
        }

        # Redirect to URL from parameter
        return $this->redirect($url, $status);
    }

    /**
     * Redirect to proper path after edit
     *
     * @param \Psr\Http\Message\UriInterface|array<mixed>|string $url A string, array-based URL or UriInterface instance.
     * @param int $status HTTP status code. Defaults to `302`.
     * @return \Cake\Http\Response|null
     */
    public function afterEditRedirect(UriInterface|array|string $url, int $status = 302): ?Response
    {
        # Use afterAddRedirect
        return $this->afterAddRedirect($url, $status);
    }

    /**
     * Redirect to proper path after delete
     *
     * @param \Psr\Http\Message\UriInterface|array<mixed>|string $url A string, array-based URL or UriInterface instance.
     * @param int $status HTTP status code. Defaults to `302`.
     * @return \Cake\Http\Response|null
     */
    public function afterDeleteRedirect(UriInterface|array|string $url, int $status = 302): ?Response
    {
        $referer_url = $this->getRequest()->referer();

        # Redirect to referer if it is not the same object
        if ($referer_url) {
            $referer_param = Router::parseRequest(new ServerRequest(['url' => $referer_url]));
            $request_param = Router::parseRequest($this->getRequest());

            if (
                ($referer_param['controller'] ?? null) !== ($request_param['controller'] ?? null)
                || !isset($referer_param['pass'][0])
                || !isset($request_param['pass'][0])
                || $referer_param['pass'][0] !== $request_param['pass'][0]
            ) {
                return $this->redirect($referer_url, $status);
            }
        }

        // other behavior if not in win-link
        if (!($this->getRequest()->getQuery('win-link') == 'true')) {
            # Redirect to contract card if contract ID is known
            if (isset($this->contract_id) && $this->getRequest()->getParam('controller') !== 'Contracts') {
                return $this->redirect([
                    'plugin' => null,
                    'controller' => 'Contracts',
                    'action' => 'view',
                    $this->contract_id,
                ]);
            }

            # Redirect to customer card if customer ID is known
            if (isset($this->customer_id) && $this->getRequest()->getParam('controller') !== 'Customers') {
                return $this->redirect([
                    'plugin' => null,
                    'controller' => 'Customers',
                    'action' => 'view',
                    $this->customer_id,
                ]);
            }
        }

        # Redirect to URL from parameter
        return $this->redirect($url, $status);
    }
}
