<?php
declare(strict_types=1);

namespace App\Controller;

use Access\Controller\UsersController;
use App\Controller\Traits\UserSettingsTrait;

/**
 * Users Controller
 *
 * @property \App\Model\Table\AppUsersTable $Users
 */
class AppUsersController extends UsersController
{
    use UserSettingsTrait;
}
