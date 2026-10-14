<?php
declare(strict_types=1);

namespace App\Controller;

use Access\Controller\UsersController as AccessUsersController;
use App\Controller\Traits\UserSettingsTrait;

/**
 * Users Controller
 *
 * @property \App\Model\Table\UsersTable $Users
 */
class UsersController extends AccessUsersController
{
    use UserSettingsTrait;
}
