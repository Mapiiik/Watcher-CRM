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

use Cake\Utility\Inflector;
use Cake\View\View;
use Override;

/**
 * Application View
 *
 * Your application's default view class
 *
 * @link https://book.cakephp.org/5/en/views.html#the-app-view
 * @property \CakeDC\Users\View\Helper\AuthLinkHelper $AuthLink
 * @property \Files\View\Helper\PreviewHelper $Preview
 * @property \Files\View\Helper\RecordHelper $Record
 * @property \Files\View\Helper\UploadHelper $Upload
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
     * What the page said about itself, as far as it has said anything.
     *
     * @var string|null
     */
    private ?string $pageIsCalled = null;
    private ?string $formIsCalled = null;
    private ?string $recordIsCalled = null;

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
        // A page asks for the viewer before it draws documents. The table that draws them is a
        // cell, and what a cell puts in a block the layout never reads.
        $this->addHelper('Files.Preview');
        // And a form that takes files says how many the server will take of them.
        $this->addHelper('Files.Upload');
    }

    /**
     * The heading of a page, which is also what its window is called.
     *
     * Saying it once is the point: a heading and a window name that are asked for separately
     * drift apart, and then a row of tabs stops telling anybody where they are.
     *
     * @param string $what What this page is.
     * @return string
     */
    public function heading(string $what): string
    {
        $this->pageIsCalled ??= $what;

        return '<h3>' . h($what) . '</h3>';
    }

    /**
     * The heading of a form, which is also what its window is called.
     *
     * @param string $what What this form does.
     * @return string
     */
    public function legend(string $what): string
    {
        $this->formIsCalled ??= $what;

        return '<legend>' . h($what) . '</legend>';
    }

    /**
     * The heading of a page about one record, which is also what its window is called.
     *
     * What sort of record it is and then which one, because a heading reading only `42` leaves
     * the reader to work out what they are even looking at.
     *
     * Several pages are about the same record - it is looked at, its papers are gone through, it
     * is printed - and without saying which of those this is they all carry the same heading and
     * the same window name. So a page says that too, after the kind: `Customer - Print | 550001`.
     * It holds what is being done with the record, or which part of it is being looked at,
     * whichever the page is for.
     *
     * @param string $kind What sort of record this is.
     * @param string $identity Which one, as the record is known by.
     * @param string|null $about A line worth reading under it, if there is one.
     * @param string|null $doing What this page is about that record, where it is not the record
     *     itself that is being looked at.
     * @return string
     */
    public function record(
        string $kind,
        string $identity,
        ?string $about = null,
        ?string $doing = null,
    ): string {
        $said = $doing === null || $doing === '' ? $kind : $kind . ' - ' . $doing;

        $this->recordIsCalled ??= $said . ' | ' . $identity;

        return h($said) . '<h3>' . h($identity) . '</h3>'
            . ($about === null || $about === '' ? '' : '<h5>' . h($about) . '</h5>');
    }

    /**
     * What the page said it was, as the window is to be called.
     *
     * A page about a record is named after it, a page that names itself after that, and a form
     * standing on its own after what it does. The record wins over the rest because a page about
     * a record has the most to say about where somebody is.
     *
     * Of each of the three, whoever asks first wins - so a page carrying more than one fieldset,
     * or one record shown inside another, keeps the name of what it is about rather than the name
     * of the last thing drawn on it.
     *
     * @return string
     */
    private function whatThePageSaidItIs(): string
    {
        return $this->recordIsCalled ?? $this->pageIsCalled ?? $this->formIsCalled ?? '';
    }

    /**
     * Names the window after the page, when the page has not named itself.
     *
     * @param string $content Content to render in a template, wrapped by the surrounding layout.
     * @param string|null $layout Layout name
     * @return string Rendered output.
     */
    #[Override]
    public function renderLayout(string $content, ?string $layout = null): string
    {
        if ($this->fetch('title') === '') {
            $said = $this->whatThePageSaidItIs();
            $this->assign('title', $said === '' ? $this->nameOfThePage() : $said);
        }

        return parent::renderLayout($content, $layout);
    }

    /**
     * What to call a window over a page that has not named itself.
     *
     * The plugin, the agenda and the action, which together are the address the page was opened
     * at. What Cake falls back to on its own is only the middle one, so every page of an agenda
     * carries the same name and a row of tabs tells nothing apart. It stays English on purpose:
     * too few of these names are in the catalogues for a translated one to come out whole.
     *
     * @return string
     */
    private function nameOfThePage(): string
    {
        $request = $this->getRequest();
        $plugin = $request->getParam('plugin');
        $action = $request->getParam('action');
        $agenda = str_replace(DIRECTORY_SEPARATOR, '/', $this->getTemplatePath());

        // The plugin keeps the name it is addressed by, vendor prefix and all - said as words,
        // `CakeDC/Users` comes out as something nobody would recognise. A plugin whose main
        // agenda is called the same thing as itself says it once.
        $address = [
            is_string($plugin) && $plugin !== $agenda ? $plugin : '',
            $this->inWords($agenda),
            is_string($action) ? $this->inWords($action) : '',
        ];

        return implode(' | ', array_filter($address));
    }

    /**
     * A name the way it is written in code, said as words.
     *
     * @param string $name An agenda or an action, in either CamelCase or under_scores.
     * @return string
     */
    private function inWords(string $name): string
    {
        return Inflector::humanize(Inflector::underscore($name));
    }
}
