<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Controller\AppController as BaseController;
use Cake\View\JsonView;
use Override;

/**
 * Shared ground for what this application answers over its API.
 *
 * Everything here speaks JSON, which is the whole of what this class settles today.
 *
 * Writing is another matter. The actions are here, but only this application's own JavaScript
 * gets to use them: a caller from outside is turned away by the CSRF middleware for having no
 * cookie, and by FormProtection for carrying no `_Token`. Both are deliberately left on. Opening
 * them would leave `api_key` as the only thing between the outside and every record this
 * application keeps - and it travels as a query parameter, so it is written into access logs and
 * handed on in Referer headers. Before another application is let in here to write, that wants
 * Bearer authentication; turning the two guards off over this prefix is a step to take with it,
 * not before it. Watcher NMS already does exactly that for the endpoint its agent posts to -
 * a token in an `Authorization` header, `FormProtection` taken off the controller's event
 * manager, and the prefix named in the CSRF middleware's skip callback.
 *
 * Reading is not affected either way - neither guard looks at a GET.
 */
class AppController extends BaseController
{
    /**
     * Returns supported output types
     */
    #[Override]
    public function viewClasses(): array
    {
        return [JsonView::class];
    }
}
