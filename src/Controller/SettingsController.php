<?php
declare(strict_types=1);

namespace App\Controller;

use App\Domain\Settings\SettingsPath;
use App\Service\SettingsService;
use Cake\Datasource\Exception\RecordNotFoundException;

/**
 * Settings Controller
 *
 * @property \App\Model\Table\SettingsTable $Settings
 */
class SettingsController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
    }

    /**
     * Edit method
     *
     * @param string $path Settings path (plugin.key[.subKey...]).
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(string $path)
    {
        $settingsPath = SettingsPath::fromString($path);

        if (!$settingsPath->isValid()) {
            throw new RecordNotFoundException(__('Invalid settings path: {path}', ['path' => $path]));
        }

        $settingsService = new SettingsService();

        // Default values
        $default = $settingsService->getDefault($path);

        if ($default === null) {
            throw new RecordNotFoundException(__('Unknown settings block: {path}', ['path' => $path]));
        }

        // Overlay from DB
        $overlay = $settingsService->getOverlay($path);

        if ($this->request->is(['post', 'put'])) {
            $overlay = $this->request->getData('overlay');

            if ($settingsService->set($path, $overlay)) {
                $this->Flash->success(__('The setting has been saved.'));

                return $this->redirect([
                    'action' => 'edit',
                    $path,
                ]);
            }

            $this->Flash->error(__('The setting could not be saved. Please, try again.'));
        }

        $this->set([
            'path' => $path,
            'settingsPath' => $settingsPath,
            'default' => $default,
            'overlay' => $overlay,
        ]);
    }
}
