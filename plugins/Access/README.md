# Access

How people sign in to the Watcher applications: the CakeDC/Users configuration, the accounts and
the pages around them. Every application carrying this plugin holds a byte-identical copy, so that
signing in works and is secured the same way everywhere.

## What the application supplies

| Plugin | Application |
|---|---|
| `Access\Controller\UsersController` | `App\Controller\AppUsersController extends` it |
| `Access\Model\Table\UsersTable` | `App\Model\Table\AppUsersTable extends` it |
| `Access\Model\Entity\User` | `App\Model\Entity\AppUser extends` it and lists the roles |
| `config/users.php` | `Users.config` names `Access.users` in `Application::bootstrap()` |

The classes keep their application names because the configuration, the permissions and many
templates name them. The templates under `templates/AppUsers/` stay in the application too, as
they show fields that only the application has.

Load the plugin after `CakeDC/Users`. What differs between installations comes from the
environment, not from this plugin.

## Settings, log and cache

The rules for passwords and for failed sign-ins are the settings block `access.security`.

Signing in, failures and refusals are written to the log scope `auth`. Unless the application
configures a log of that name, they go to syslog with the facility `auth`, under the name
`watcher-` followed by `APP_NAME`. Passwords, codes and tokens are never written.

Failed sign-ins are counted in the cache `login_throttle`. Where an installation runs on more
than one server, the cache has to be shared by all of them, such as one Redis database.
