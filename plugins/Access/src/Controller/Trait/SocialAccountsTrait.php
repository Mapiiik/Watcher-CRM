<?php
declare(strict_types=1);

namespace Access\Controller\Trait;

use Access\Authentication\AuthLog;
use Access\Model\Table\SocialAccountsTable;
use Access\Social\Providers;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use CakeDC\Auth\Social\MapUser;
use CakeDC\Auth\Social\Service\ServiceFactory;
use Throwable;

/**
 * Identities at other providers, linked to the account signed in by its owner.
 *
 * Linking never changes the account: not its role, nor its e-mail. An identity linked to another
 * account is refused.
 */
trait SocialAccountsTrait
{
    /**
     * Sends the owner of the account to the provider, to say which identity there is theirs.
     *
     * @param string|null $provider The provider, as in the addresses.
     * @return \Cake\Http\Response|null
     */
    public function linkSocial(?string $provider = null): ?Response
    {
        return $this->redirect(
            (new ServiceFactory())
                ->setRedirectUriField('callbackLinkSocialUri')
                ->createFromProvider($this->offeredProvider($provider))
                ->getAuthorizationUrl($this->getRequest()),
        );
    }

    /**
     * Links the identity the provider sends the owner back with.
     *
     * @param string|null $provider The provider, as in the addresses.
     * @return \Cake\Http\Response|null
     */
    public function callbackLinkSocial(?string $provider = null): ?Response
    {
        $provider = $this->offeredProvider($provider);
        $user = $this->signedInAccount();
        $refused = __d('access', 'The identity could not be linked. Please try again.');

        try {
            $service = (new ServiceFactory())
                ->setRedirectUriField('callbackLinkSocialUri')
                ->createFromProvider($provider);
            $identity = $service->isGetUserStep($this->getRequest())
                ? (new MapUser())($service, $service->getUser($this->getRequest()))
                : null;
        } catch (Throwable $exception) {
            $this->log('An identity could not be linked: ' . $exception->getMessage());
            $identity = null;
        }
        $reference = (string)($identity['id'] ?? '');
        if ($reference === '') {
            $this->Flash->error($refused);

            return $this->redirect(['action' => 'profile']);
        }

        $link = $this->socialAccountsTable()->link(
            (string)$user->get('id'),
            $provider,
            $reference,
            $identity['email'] ?? $identity['username'] ?? null,
        );
        if ($link->hasErrors()) {
            $errors = $link->getError('reference');
            $this->Flash->error($errors === [] ? $refused : (string)current($errors));
        } else {
            AuthLog::notice('social account linked', [
                'user' => (string)$user->get('username'),
                'provider' => $provider,
                'reference' => $reference,
                'ip' => AuthLog::address($this->getRequest()),
            ]);
            $this->Flash->success(__d('access', 'The identity at {0} is linked.', Providers::label($provider)));
        }

        return $this->redirect(['action' => 'profile']);
    }

    /**
     * Unlinks an identity from the account signed in.
     *
     * @param string|null $id The link.
     * @return \Cake\Http\Response|null
     */
    public function unlinkSocial(?string $id = null): ?Response
    {
        $this->getRequest()->allowMethod(['post', 'delete']);
        $user = $this->signedInAccount();
        $links = $this->socialAccountsTable();
        $link = $links->find()->where(['id' => $id, 'user_id' => $user->get('id')])->firstOrFail();

        if ($links->delete($link)) {
            AuthLog::notice('social account unlinked', [
                'user' => (string)$user->get('username'),
                'provider' => (string)$link->get('provider'),
                'reference' => (string)$link->get('reference'),
                'ip' => AuthLog::address($this->getRequest()),
            ]);
            $this->Flash->success(__d('access', 'The identity is unlinked.'));
        } else {
            $this->Flash->error(__d('access', 'The identity could not be unlinked.'));
        }

        return $this->redirect(['action' => 'profile']);
    }

    /**
     * Unlinks an identity from whichever account it is linked to, for an admin: where somebody
     * linked the wrong one, or has left.
     *
     * @param string|null $id The link.
     * @return \Cake\Http\Response|null
     */
    public function unlinkSocialAccount(?string $id = null): ?Response
    {
        $this->getRequest()->allowMethod(['post', 'delete']);
        $links = $this->socialAccountsTable();
        $link = $links->get($id);
        $owner = $this->getUsersTable()->get($link->get('user_id'));

        if ($links->delete($link)) {
            AuthLog::notice('social account unlinked', [
                'user' => (string)$owner->get('username'),
                'by' => (string)($this->getRequest()->getAttribute('identity')['username'] ?? ''),
                'provider' => (string)$link->get('provider'),
                'reference' => (string)$link->get('reference'),
                'ip' => AuthLog::address($this->getRequest()),
            ]);
            $this->Flash->success(__d('access', 'The identity is unlinked.'));
        } else {
            $this->Flash->error(__d('access', 'The identity could not be unlinked.'));
        }

        return $this->redirect(['action' => 'view', $owner->get('id')]);
    }

    /**
     * The identities linked to accounts.
     *
     * @return \Access\Model\Table\SocialAccountsTable
     */
    private function socialAccountsTable(): SocialAccountsTable
    {
        /** @var \Access\Model\Table\SocialAccountsTable */
        return $this->fetchTable('Access.SocialAccounts');
    }

    /**
     * A provider people may sign in with, or none at all.
     *
     * @param string|null $provider The provider, as in the addresses.
     * @return string
     * @throws \Cake\Http\Exception\NotFoundException For a provider not offered.
     */
    private function offeredProvider(?string $provider): string
    {
        if ($provider === null || !in_array($provider, Providers::names(), true)) {
            throw new NotFoundException();
        }

        return $provider;
    }
}
