<?php
declare(strict_types=1);

namespace Settings\Controller\Trait;

use Cake\Datasource\Exception\RecordNotFoundException;
use Settings\Service\SettingsService;
use Settings\ValueObject\SettingsPath;

/**
 * Settings Controller Trait
 *
 * @psalm-require-extends \Cake\Controller\Controller
 */
trait SettingsControllerTrait
{
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
            throw new RecordNotFoundException(__d('settings', 'Invalid settings path: {path}', ['path' => $path]));
        }

        $settingsService = new SettingsService();

        // Default values
        $default = $settingsService->getDefault($path);

        if ($default === null) {
            throw new RecordNotFoundException(__d('settings', 'Unknown settings block: {path}', ['path' => $path]));
        }

        // Overlay from DB
        $overlay = $settingsService->getOverlay($path);

        if ($this->request->is(['post', 'put'])) {
            $overlay = $this->request->getData('overlay');

            if ($settingsService->set($path, $overlay)) {
                $this->Flash->success(__d('settings', 'The setting has been saved.'));

                return $this->redirect([
                    'action' => 'edit',
                    $path,
                ]);
            }

            $this->Flash->error(__d('settings', 'The setting could not be saved. Please, try again.'));
        }

        $this->set([
            'path' => $path,
            'settingsPath' => $settingsPath,
            'default' => $default,
            'overlay' => $overlay,
        ]);
    }
}
