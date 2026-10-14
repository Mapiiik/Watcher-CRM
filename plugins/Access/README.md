# Access

How people sign in to the Watcher applications: the accounts, the pages around them and what
decides who may open which page. Every application carrying this plugin holds a byte-identical
copy, so that signing in works and is secured the same way everywhere.

CakeDC/Auth lends it the second factor, the permissions of `config/permissions.php` and the services
of other identity providers. The rest is this plugin's own: the middleware, the addresses, the pages,
the accounts and the mail.

## What the application supplies

| Plugin | Application |
|---|---|
| `Access\Controller\UsersController` | `App\Controller\UsersController extends` it |
| `Access\Model\Table\UsersTable` | `App\Model\Table\UsersTable extends` it |
| `Access\Model\Entity\User` | `App\Model\Entity\User extends` it and lists the roles |
| `AuthLinkHelper` | `App\View\AppView` loads it as `AuthLink`: links shown only where they may be opened |

The application's classes carry the same short names in its own namespace, and its configuration
and permissions name them `Users`. The pages are the plugin's. An application overrides in
`templates/plugin/Access/` only what it shows otherwise, its own fields of an account through the
element `account_fields`.

The plugin reads `config/users.php` on its own, and adds the authentication and the authorization
middleware after the application's. What differs between installations comes from the environment,
not from this plugin.

## A forgotten password

The sign-in page offers a link for a new password, sent to the e-mail of the account a username or
an e-mail names. The answer is the same whether such an account exists or not. The link works for
an hour and once, and only its hash is kept.

## Other identity providers

Signing in with Google is offered where `GOOGLE_OAUTH_CLIENT_ID` and `GOOGLE_OAUTH_CLIENT_SECRET`
are set. An identity there signs in only the account its owner linked it to in the profile: it
never makes an account, nor changes one, not even one with the same e-mail. An identity nobody
linked is logged as `social account not linked`, with its reference. The second factor is asked for
after it as after a password.

## The first account

`bin/cake access add_admin <username> <email>` adds an admin, with a password made up for it and
shown once. No account comes with the schema, so a new installation has no other way in.

## Settings, log and cache

The rules for passwords and for failed sign-ins are the settings block `access.security`.

Signing in, failures and refusals are written to the log scope `auth`. Unless the application
configures a log of that name, they go to syslog with the facility `auth`, under the name
`watcher-` followed by `APP_NAME`. Passwords, codes and tokens are never written.

Two-step verification asks for a verification code from an authenticator application where
`access.security` requires it of the account, or where its owner turned it on of their own accord.
The codes are TOTP of RFC 6238, made here; the QR code is drawn by `tecnickcom/tc-lib-barcode`, so
the secret goes to no other service. A code is accepted once. Ten recovery codes stand in for the
phone, each once. The pages name the method "Authenticator Application (TOTP)", so another method
can stand beside it under the same heading.

API clients sign in with a key sent as a Bearer token. A key in the address still works and is
logged as `api key in the address`. Only the SHA-256 of a key is kept, so an admin makes a new
one on the user's page and sees it once. Passwords, one-time secrets and keys are written to the
audit log as `****`.

Failed sign-ins are counted in the cache `login_throttle`. Where an installation runs on more
than one server, the cache has to be shared by all of them, such as one Redis database.
