<?php
declare(strict_types=1);

namespace Access\Middleware\UnauthorizedHandler;

use Authorization\Exception\Exception;
use Authorization\Exception\ForbiddenException;
use Authorization\Exception\MissingIdentityException;
use Authorization\Middleware\UnauthorizedHandler\CakeRedirectHandler;
use Cake\Http\ServerRequest;
use Cake\Http\Session;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Where a page somebody may not open sends them: to sign in when nobody is signed in, and back
 * where they came from, with a word why, when they are.
 */
class RedirectHandler extends CakeRedirectHandler
{
    /**
     * @inheritDoc
     */
    protected array $defaultOptions = [
        'exceptions' => [
            MissingIdentityException::class,
            ForbiddenException::class,
        ],
        'url' => [
            'plugin' => false,
            'prefix' => false,
            'controller' => 'AppUsers',
            'action' => 'login',
        ],
        'queryParam' => 'redirect',
        'statusCode' => 302,
        'allowedRedirectExtensions' => true,
    ];

    /**
     * @inheritDoc
     */
    #[Override]
    public function handle(
        Exception $exception,
        ServerRequestInterface $request,
        array $options = [],
    ): ResponseInterface {
        $response = parent::handle($exception, $request, $options + $this->defaultOptions);

        $session = $request->getAttribute('session');
        if ($request->getAttribute('identity') !== null && $session instanceof Session) {
            $messages = (array)$session->read('Flash.flash');
            $messages[] = [
                'message' => __d('access', 'You are not allowed to open that page.'),
                'key' => 'flash',
                'element' => 'flash/error',
                'params' => [],
            ];
            $session->write('Flash.flash', $messages);
        }

        return $response;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    protected function getUrl(ServerRequestInterface $request, array $options): string
    {
        if ($request->getAttribute('identity') !== null && $request instanceof ServerRequest) {
            return $request->referer() ?? '/';
        }

        return parent::getUrl($request, $options);
    }
}
