<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Enum\LandingPage;
use Cake\Http\Response;
use Cake\Utility\Hash;
use CakeDC\Auth\Traits\IsAuthorizedTrait;

/**
 * Home Controller
 *
 * The root is not a page but a question - which page this user starts on - and this is where it
 * is answered. Everybody gets the dashboard unless they have settled on something else for
 * themselves.
 */
class HomeController extends AppController
{
    use IsAuthorizedTrait;

    /**
     * Send the user on to the page they start on.
     *
     * The choice is checked rather than trusted: it was made at a time when the role, and what
     * the installation offers at all, may both have been other than they now are - and a
     * redirect into a page the permissions then refuse would turn signing in into a bounce back
     * to the login. What is on offer here and what the navigation draws is the same question
     * asked the same way, so the two cannot come apart.
     *
     * @return \Cake\Http\Response|null
     */
    public function index(): ?Response
    {
        $chosen = LandingPage::tryFrom((string)Hash::get($this->user_settings, 'landing_page', ''));

        if ($chosen !== null && $chosen !== LandingPage::Dashboard && !$this->isStillOpen($chosen)) {
            $this->Flash->error(__(
                'The page you chose to start on is not open to you any more. Please pick another one in your settings.',
            ));

            $chosen = null;
        }

        return $this->redirect(($chosen ?? LandingPage::Dashboard)->url());
    }

    /**
     * Whether the page is one this user could ask for on their own.
     *
     * @param \App\Model\Enum\LandingPage $page The page they chose.
     * @return bool
     */
    private function isStillOpen(LandingPage $page): bool
    {
        return array_key_exists($page->value, LandingPage::options())
            && $this->isAuthorized($page->url());
    }
}
