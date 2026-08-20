<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link      https://cakephp.org CakePHP(tm) Project
 * @since     3.0.0
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 */
namespace App\View;

use Cake\View\View;
use Override;

/**
 * Application View
 *
 * Your application's default view class
 *
 * @link https://book.cakephp.org/5/en/views.html#the-app-view
 * @property \CakeDC\Users\View\Helper\AuthLinkHelper $AuthLink
 */
class AppView extends View
{
    /**
     * What a control puts on its `onchange` when choosing is the whole of the asking.
     *
     * A filter above a listing works this way: there is nothing to press, the choice is the
     * request. Kept here beside the longer one below so that a change of mind about how forms
     * answer to a choice has one place to happen.
     *
     * @var string
     */
    public const SUBMIT_ON_CHANGE = 'this.form.submit();';

    /**
     * What a control puts on its `onchange` when choosing narrows the controls below it.
     *
     * The form has to say that it is only refreshing rather than being submitted in earnest, and
     * the controller reads that as a field - so the field is added on the way out instead of
     * sitting in the markup, where the browser would send it on a real submit as well. Form
     * protection knows nothing of a field that appears this late, so whoever asks for this has to
     * follow it with `$this->Form->unlockField('refresh')`.
     *
     * Templates reach it as `$this::REFRESH_ON_CHANGE`, `$this` being this class.
     *
     * @var string
     */
    public const REFRESH_ON_CHANGE = <<<JS
        var refresh = document.createElement("input");
        refresh.type = "hidden";
        refresh.name = "refresh";
        refresh.value = "refresh";
        this.form.appendChild(refresh);
        this.form.submit();
        JS;

    /**
     * Initialization hook method.
     *
     * Use this method to add common initialization code like adding helpers.
     *
     * e.g. `$this->addHelper('Html');`
     *
     * @return void
     */
    #[Override]
    public function initialize(): void
    {
        parent::initialize();
        $this->addHelper('CakeDC/Users.User');
        $this->addHelper('CakeDC/Users.AuthLink');
    }
}
