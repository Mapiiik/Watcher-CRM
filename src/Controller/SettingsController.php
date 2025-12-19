<?php
declare(strict_types=1);

namespace App\Controller;

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
     * @param string $plugin Plugin.
     * @param string $key Key.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(string $plugin, string $key)
    {
        $settingsService = new SettingsService();

        // Default values
        $default = $settingsService->getDefault("$plugin.$key");

        if ($default === null) {
            throw new RecordNotFoundException(__('Unknown settings block: {path}', ['path' => $plugin . '.' . $key]));
        }

        // Overlay from DB (if exists)
        $setting = $this->Settings->findOrNewEntity(
            [
                'plugin' => $plugin,
                'key' => $key,
            ],
        );

        if ($this->request->is(['post', 'put'])) {
            $data = $this->request->getData('value');
            $setting->value = $data;

            if ($settingsService->set("$plugin.$key", $data)) {
                $this->Flash->success(__('The setting has been saved.'));

                return $this->redirect([
                    'action' => 'edit',
                    $plugin,
                    $key,
                ]);
            }
            $this->Flash->error(__('The setting could not be saved. Please, try again.'));
        }

        $this->set(compact(
            'plugin',
            'key',
            'default',
            'setting',
        ));
    }
}
