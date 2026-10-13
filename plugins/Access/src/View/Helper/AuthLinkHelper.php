<?php
declare(strict_types=1);

namespace Access\View\Helper;

use Cake\Http\ServerRequest;
use Cake\View\Helper\HtmlHelper;
use CakeDC\Auth\Traits\IsAuthorizedTrait;
use Override;

/**
 * Links shown only to whoever may open what they lead to.
 *
 * @property \Cake\View\Helper\UrlHelper $Url
 * @property \Cake\View\Helper\FormHelper $Form
 */
class AuthLinkHelper extends HtmlHelper
{
    use IsAuthorizedTrait;

    /**
     * Helpers used by this helper.
     *
     * @var array<int|string, array<string, mixed>|string>
     */
    protected array $helpers = ['Url', 'Form'];

    /**
     * A link, or nothing for somebody who may not open the page.
     *
     * Besides the options of `HtmlHelper::link()`: `before` and `after` are put around the link
     * where it is shown, and `allowed` decides instead of the permissions.
     *
     * @param array|string $title The content to be wrapped by `<a>` tags.
     * @param array|string|null $url Cake-relative URL or array of URL parameters.
     * @param array<string, mixed> $options Array of options and HTML attributes.
     * @return string
     */
    #[Override]
    public function link(array|string $title, array|string|null $url = null, array $options = []): string
    {
        $allowed = $options['allowed'] ?? null;
        $before = (string)($options['before'] ?? '');
        $after = (string)($options['after'] ?? '');
        unset($options['allowed'], $options['before'], $options['after']);

        if ($allowed === false || ($allowed !== true && !$this->isAuthorized($url))) {
            return '';
        }

        return $before . parent::link($title, $url, $options) . $after;
    }

    /**
     * A form posted by a link, or nothing for somebody who may not do what it does.
     *
     * @param string $title The content to be wrapped by `<a>` tags.
     * @param array|string|null $url Cake-relative URL or array of URL parameters.
     * @param array<string, mixed> $options Array of options and HTML attributes.
     * @return string
     */
    public function postLink(string $title, array|string|null $url = null, array $options = []): string
    {
        return $this->isAuthorized($url) ? $this->Form->postLink($title, $url, $options) : '';
    }

    /**
     * The request the page answers.
     *
     * @return \Cake\Http\ServerRequest
     */
    public function getRequest(): ServerRequest
    {
        return $this->getView()->getRequest();
    }
}
