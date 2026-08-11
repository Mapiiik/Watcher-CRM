<?php
declare(strict_types=1);

namespace Settings\Controller\Trait;

use Cake\Datasource\Exception\RecordNotFoundException;
use Settings\Exception\SettingValueException;
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

        // refusals, by the full path of the setting they belong to
        $errors = [];

        if ($this->request->is(['post', 'put'])) {
            // what was submitted answers for the overlay from here on, so a refused form comes
            // back with what was typed into it rather than with what is stored
            $overlay = $this->request->getData('overlay');

            try {
                if ($settingsService->set($path, $overlay)) {
                    $this->Flash->success(__d('settings', 'The setting has been saved.'));

                    return $this->redirect([
                        'action' => 'edit',
                        $path,
                    ]);
                }

                $this->Flash->error(__d('settings', 'The setting could not be saved. Please, try again.'));
            } catch (SettingValueException $exception) {
                $refused = $exception->getPath();

                // the message goes to the field it belongs to, and a form long enough to scroll
                // needs saying so at the top as well
                if ($refused !== null) {
                    $errors[$refused] = $exception->getMessage();

                    $this->Flash->error(__d(
                        'settings',
                        'The setting {path} could not be saved: {reason}',
                        ['path' => $refused, 'reason' => $exception->getMessage()],
                    ));
                } else {
                    $this->Flash->error($exception->getMessage());
                }
            }
        }

        $this->set([
            'path' => $path,
            'settingsPath' => $settingsPath,
            'default' => $default,
            'overlay' => $overlay,
            'types' => $settingsService->getTypes($path),
            'errors' => $errors,
        ]);
    }
}
