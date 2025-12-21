<?php
declare(strict_types=1);

namespace App\Controller;

use Settings\Controller\Trait\SettingsControllerTrait;

/**
 * Settings Controller
 */
class SettingsController extends AppController
{
    use SettingsControllerTrait;

    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
    }
}
