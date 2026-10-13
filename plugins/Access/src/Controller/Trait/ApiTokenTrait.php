<?php
declare(strict_types=1);

namespace Access\Controller\Trait;

use Access\Authentication\AuthLog;
use Cake\Http\Response;

/**
 * API keys of the accounts API clients sign in as.
 *
 * Only a hash of a key is kept, so a key is shown once, when it is made, and can only be replaced
 * afterwards.
 */
trait ApiTokenTrait
{
    /**
     * Makes a new API key for an account, in place of the one it had.
     *
     * @param string|null $id The account.
     * @return \Cake\Http\Response|null
     */
    public function generateApiToken(?string $id = null): ?Response
    {
        $this->getRequest()->allowMethod('post');
        $user = $this->getUsersTable()->get($id);

        $token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $user->set('api_token', $token);
        $this->getUsersTable()->saveOrFail($user);
        AuthLog::notice('api key made', [
            'user' => (string)$user->get('username'),
            'by' => (string)($this->getRequest()->getAttribute('identity')['username'] ?? ''),
            'ip' => AuthLog::address($this->getRequest()),
        ]);

        $this->getRequest()->getSession()->write('Access.apiToken', [
            'id' => $id,
            'username' => (string)$user->get('username'),
            'token' => $token,
        ]);

        return $this->redirect(['action' => 'apiToken']);
    }

    /**
     * Takes the API key of an account away, so that no client signs in as it any more.
     *
     * @param string|null $id The account.
     * @return \Cake\Http\Response|null
     */
    public function removeApiToken(?string $id = null): ?Response
    {
        $this->getRequest()->allowMethod('post');
        $user = $this->getUsersTable()->get($id);

        $user->set('api_token', null);
        $this->getUsersTable()->saveOrFail($user);
        AuthLog::notice('api key removed', [
            'user' => (string)$user->get('username'),
            'by' => (string)($this->getRequest()->getAttribute('identity')['username'] ?? ''),
            'ip' => AuthLog::address($this->getRequest()),
        ]);
        $this->Flash->success(__d('access', 'The API key of {0} is removed.', $user->get('username')));

        return $this->redirect(['action' => 'view', $id]);
    }

    /**
     * Shows a new API key, once.
     *
     * @return \Cake\Http\Response|null
     */
    public function apiToken(): ?Response
    {
        $made = $this->getRequest()->getSession()->consume('Access.apiToken');
        if (!is_array($made)) {
            return $this->redirect(['action' => 'index']);
        }

        $this->set('made', $made);

        return null;
    }
}
