<?php
declare(strict_types=1);

namespace App\Controller\Traits;

use Cake\Core\Configure;

/**
 * Covers the user settings
 *
 * @property \Cake\Http\ServerRequest $request
 */
trait UserSettingsTrait
{
    /**
     * Edit user settings method
     *
     * @param string|null $id User id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function userSettings($id = null)
    {
        $identity = $this->getRequest()->getAttribute('identity');
        $identity = $identity ?? [];
        $userId = $identity['id'] ?? null;

        if ($id && $identity['is_superuser'] && Configure::read('Users.Superuser.allowedToChangeSettings')) {
            // superuser can edit settings of all users
            $redirect = ['action' => 'index'];
        } elseif ((!$id && $userId) || $id === $userId) {
            // normal user can edit own settings
            $id = $userId;
            $redirect = Configure::read('Users.Profile.route');
        } else {
            $this->Flash->error(
                __d('app_users', "You are not allowed to edit another user's settings.")
            );
            $this->redirect(Configure::read('Users.Profile.route'));

            return;
        }

        /** @var \App\Model\Table\AppUsersTable $usersTable */
        $usersTable = $this->fetchTable(Configure::read('Users.table'));

        $user = $usersTable->get($id, [
            'fields' => [
                'id',
                'username',
                'user_settings',
            ],
            'contain' => [],
        ]);

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $user = $usersTable->patchEntity($user, [
                'user_settings' => $this->getRequest()->getData('user_settings'),
            ]);
            if ($usersTable->save($user)) {
                // update current identity data if it is a logged-in user
                if ($user->id === $userId) {
                    $this->getRequest()->getAttribute('identity')
                        ->getOriginalData()
                        ->set('user_settings', $user->user_settings);
                }

                $this->Flash->success(__d('app_users', 'The user settings have been saved.'));

                return $this->redirect($redirect);
            }
            $this->Flash->error(__d('app_users', 'The user settings could not be saved. Please, try again.'));
        }

        $this->set(compact('user'));
    }
}
