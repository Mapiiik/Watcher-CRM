<?php
/**
 * Copyright 2010 - 2019, Cake Development Corporation (https://www.cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2010 - 2018, Cake Development Corporation (https://www.cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

use Cake\Http\ServerRequest;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use Cake\Validation\Validation;

/*
 * This is a quick roles-permissions implementation
 * Rules are evaluated top-down, first matching rule will apply
 * Each line define
 *      [
 *          'role' => 'role' | ['roles'] | '*'
 *          'prefix' => 'Prefix' | , (default = null)
 *          'plugin' => 'Plugin' | , (default = null)
 *          'controller' => 'Controller' | ['Controllers'] | '*',
 *          'action' => 'action' | ['actions'] | '*',
 *          'allowed' => true | false | callback (default = true)
 *      ]
 * You could use '*' to match anything
 * 'allowed' will be considered true if not defined. It allows a callable to manage complex
 * permissions, like this
 * 'allowed' => function (array $user, $role, Request $request) {}
 *
 * Example, using allowed callable to define permissions only for the owner of the Posts to edit/delete
 *
 * (remember to add the 'uses' at the top of the permissions.php file for Hash, TableRegistry and Request
   [
        'role' => ['user'],
        'controller' => ['Posts'],
        'action' => ['edit', 'delete'],
        'allowed' => function(array $user, $role, Request $request) {
            $postId = Hash::get($request->params, 'pass.0');
            $post = TableRegistry::getTableLocator()->get('Posts')->get($postId);
            $userId = Hash::get($user, 'id');
            if (!empty($post->user_id) && !empty($userId)) {
                return $post->user_id === $userId;
            }
            return false;
        }
    ],
 */

/*
 * Default permissions
 */
$permissions = [
    'CakeDC/Auth.permissions' => [
        //all bypass
        [
            'prefix' => false,
            'plugin' => null,
            'controller' => 'AppUsers',
            'action' => [
                // LoginTrait
                'socialLogin',
                'login',
                'logout',
                'socialEmail',
                'verify',
                // RegisterTrait
                'register',
                'validateEmail',
                // PasswordManagementTrait used in RegisterTrait
                'changePassword',
                'resetPassword',
                'requestResetPassword',
                // UserValidationTrait used in PasswordManagementTrait
                'resendTokenValidation',
                'linkSocial',
                //U2F actions
                'u2f',
                'u2fRegister',
                'u2fRegisterFinish',
                'u2fAuthenticate',
                'u2fAuthenticateFinish',
            ],
            'bypassAuth' => true,
        ],
        [
            'prefix' => false,
            'plugin' => 'CakeDC/Users',
            'controller' => 'SocialAccounts',
            'action' => [
                'validateAccount',
                'resendValidation',
            ],
            'bypassAuth' => true,
        ],
        //admin role allowed to all the things
        [
            'role' => 'admin',
            'prefix' => '*',
            'extension' => '*',
            'plugin' => '*',
            'controller' => '*',
            'action' => '*',
        ],
        //specific actions allowed for the all roles in Users plugin
        [
            'role' => '*',
            'plugin' => null,
            'controller' => 'AppUsers',
            'action' => [
                'profile',
                'logout',
                'linkSocial',
                'callbackLinkSocial',
                'userSettings',
            ],
        ],
        [
            'role' => '*',
            'plugin' => null,
            'controller' => 'AppUsers',
            'action' => 'resetOneTimePasswordAuthenticator',
            'allowed' => function (array $user, $role, ServerRequest $request) {
                $userId = Hash::get($request->getAttribute('params'), 'pass.0');
                if (!empty($userId) && !empty($user)) {
                    return $userId === $user['id'];
                }

                return false;
            },
        ],
        //all roles allowed to Pages/display
        [
            'role' => '*',
            'controller' => 'Pages',
            'action' => 'display',
        ],
        //the root is where everybody arrives and is only ever a redirect, so no role may be
        //stopped at it - the page it then sends them to is what gets checked, there
        [
            'role' => '*',
            'prefix' => false,
            'plugin' => null,
            'controller' => 'Home',
            'action' => 'index',
        ],
        //the dashboard is the landing page, so every role has to get through it -
        //which cards it then draws is decided per role by the card registry
        [
            'role' => '*',
            'prefix' => false,
            'plugin' => null,
            'controller' => 'Dashboard',
            'action' => [
                'index',
                'card',
            ],
        ],
        //always allow access to DebugKit
        [
            'role' => '*',
            'plugin' => 'DebugKit',
            'controller' => '*',
            'action' => '*',
            'bypassAuth' => true,
        ],
        //API access
        [
            'role' => [
                'api',
            ],
            'prefix' => 'Api',
            'plugin' => null,
            'controller' => [
                'Customers',
            ],
            'action' => [
                'index',
                'view',
                'customerPoints',
            ],
        ],
        //what the network management system asks before it lets a place of the network go
        [
            'role' => [
                'api',
            ],
            'prefix' => 'Api',
            'plugin' => null,
            'controller' => [
                'AccessPoints',
            ],
            'action' => [
                'references',
            ],
        ],
        //the tasks of the company are kept here, so this is where another application asks about
        //them - reading only, the same as above: the write actions exist on the controller but
        //are deliberately not granted, and would want Bearer authentication first
        [
            'role' => [
                'api',
            ],
            'prefix' => 'Api',
            'plugin' => null,
            'controller' => [
                'Tasks',
            ],
            'action' => [
                'index',
                'view',
                'search',
            ],
        ],
        //allow invoice download for API
        [
            'role' => [
                'api',
            ],
            'plugin' => 'Bookkeeping',
            'controller' => [
                'Invoices',
            ],
            'action' => [
                'download',
            ],
        ],
        //all roles access
        [
            'role' => '*',
            'plugin' => null,
            'controller' => [
                'Settings',
            ],
            'action' => [
                'index',
            ],
        ],
        [
            'role' => '*',
            'plugin' => null,
            'controller' => [
                'Customers',
            ],
            'action' => [
                'index',
                'view',
            ],
        ],
        //the findings on a record are fetched on their own so they do not hold its page up,
        //which makes them an action of their own - open to whoever may look at the record
        [
            'role' => '*',
            'plugin' => null,
            'controller' => [
                'Customers',
                'Contracts',
            ],
            'action' => [
                'problems',
            ],
        ],
        [
            'role' => '*',
            'plugin' => null,
            'controller' => [
                'Contracts',
                'ContractVersions',
                'Emails',
                'Phones',
                'Logins',
                'Addresses',
                'Billings',
                'ServiceOverrides',
                'BorrowedEquipments',
                'SoldEquipments',
                'IpAddresses',
                'IpNetworks',
                'RemovedIpAddresses',
                'RemovedIpNetworks',
                'CustomerLabels',
            ],
            'action' => [
                'view',
            ],
        ],
        [
            'role' => '*',
            'prefix' => 'Api',
            'plugin' => null,
            'controller' => [
                'AddressesBridge',
                'BusinessRegisterBridge',
                'GeocoderBridge',
            ],
            'action' => [
                'search',
            ],
        ],
        [
            'role' => '*',
            'prefix' => 'Api',
            'plugin' => null,
            'controller' => [
                'AgentBridge',
            ],
            'action' => [
                'ping',
            ],
        ],
        [
            'role' => '*',
            'prefix' => 'Api',
            'plugin' => null,
            'controller' => [
                'NetworkManagementSystemBridge',
            ],
            'action' => [
                'accessPoints',
                'routerosDevices',
                'ipAddressRanges',
            ],
        ],
        //allow tasks for technicians, sales and bookkeepers
        [
            'role' => [
                'customer-service-technician',
                'network-technician',
                'network-manager',
                'sales-representative',
                'sales-manager',
                'bookkeeper',
            ],
            'plugin' => null,
            'controller' => [
                'Tasks',
            ],
            'action' => [
                'index',
                'view',
                'add',
                'edit',
            ],
        ],
        //allow index when customer_id is set for sales and bookkeepers
        [
            'role' => [
                'sales-representative',
                'bookkeeper',
            ],
            'plugin' => null,
            'controller' => [
                'Contracts',
                'ContractVersions',
                'Emails',
                'Phones',
                'Logins',
                'Addresses',
                'Billings',
                'ServiceOverrides',
                'BorrowedEquipments',
                'SoldEquipments',
                'IpAddresses',
                'IpNetworks',
                'RemovedIpAddresses',
                'RemovedIpNetworks',
                'CustomerLabels',
            ],
            'action' => [
                'index',
            ],
            'allowed' => function ($user, $role, ServerRequest $request) {
                return !empty($request->getParam('customer_id'));
            },
        ],
        //the maps are another way of looking, so whoever may look at a contract or a task may map it
        [
            'role' => [
                'network-manager',
                'sales-representative',
                'sales-manager',
                'bookkeeper',
            ],
            'plugin' => null,
            'controller' => [
                'Contracts',
                'Tasks',
            ],
            'action' => [
                'map',
            ],
        ],
        //allow all indexes and views for sales-managers and network-managers
        [
            'role' => [
                'network-manager',
                'sales-manager',
            ],
            'plugin' => null,
            'controller' => '*',
            'action' => [
                'index',
                'view',
            ],
        ],
        //allow add/edit for sales and bookkeepers and network-managers
        [
            'role' => [
                'network-manager',
                'sales-representative',
                'sales-manager',
                'bookkeeper',
            ],
            'plugin' => null,
            'controller' => [
                'Customers',
                'Contracts',
                'ContractVersions',
                'Emails',
                'Phones',
                'Logins',
                'Addresses',
                'Billings',
                'ServiceOverrides',
                'BorrowedEquipments',
                'SoldEquipments',
                'IpAddresses',
                'IpNetworks',
                'CustomerLabels',
            ],
            'action' => [
                'add',
                'addFromRange',
                'edit',
                'print',
                'revoke',
                'serviceChange',
                'setDatesForRelatedBorrowedEquipments',
                'terminateRelatedBillings',
            ],
        ],
        //allow delete of some items for sales and bookkeepers and network-managers
        [
            'role' => [
                'network-manager',
                'sales-representative',
                'sales-manager',
                'bookkeeper',
            ],
            'plugin' => null,
            'controller' => [
                'Emails',
                'Phones',
                'Logins',
                'BorrowedEquipments',
                'SoldEquipments',
                'IpAddresses',
                'IpNetworks',
                'CustomerLabels',
            ],
            'action' => [
                'delete',
            ],
        ],
        //allow delete of a billing nobody has been invoiced for yet
        [
            'role' => [
                'network-manager',
                'sales-representative',
                'sales-manager',
                'bookkeeper',
            ],
            'plugin' => null,
            'controller' => [
                'Billings',
            ],
            'action' => [
                'delete',
            ],
            //The table says what may be taken back. The condition reads the record, so it also
            //settles whether AuthLink draws the button - the link and the request that follows
            //it are asked the very same question. Keep it last: the rule is read key by key and
            //this one ends the reading, so the role has to be matched before it.
            'allowed' => function ($_user, $_role, ServerRequest $request): bool {
                $id = $request->getParam('pass.0');

                if (!is_string($id) || !Validation::uuid($id)) {
                    return false;
                }

                /** @var \App\Model\Table\BillingsTable $billings */
                $billings = TableRegistry::getTableLocator()->get('Billings');
                /** @var \App\Model\Entity\Billing|null $billing */
                $billing = $billings->find()
                    ->select(['Billings.billing_from'])
                    ->where(['Billings.id' => $id])
                    ->first();

                return $billing !== null && $billings->mayBeDeleted($billing);
            },
        ],
        //allow delete of a contract version that is not signed and is not history yet
        [
            'role' => [
                'network-manager',
                'sales-representative',
                'sales-manager',
                'bookkeeper',
            ],
            'plugin' => null,
            'controller' => [
                'ContractVersions',
            ],
            'action' => [
                'delete',
            ],
            //The table says what may be taken back. The condition reads the record, so it also
            //settles whether AuthLink draws the button - the link and the request that follows
            //it are asked the very same question. Keep it last: the rule is read key by key and
            //this one ends the reading, so the role has to be matched before it.
            'allowed' => function ($_user, $_role, ServerRequest $request): bool {
                $id = $request->getParam('pass.0');

                if (!is_string($id) || !Validation::uuid($id)) {
                    return false;
                }

                /** @var \App\Model\Table\ContractVersionsTable $versions */
                $versions = TableRegistry::getTableLocator()->get('ContractVersions');
                /** @var \App\Model\Entity\ContractVersion|null $version */
                $version = $versions->find()
                    ->select(['ContractVersions.conclusion_date', 'ContractVersions.valid_from'])
                    ->where(['ContractVersions.id' => $id])
                    ->first();

                return $version !== null && $versions->mayBeDeleted($version);
            },
        ],
        //allow add/edit/delete of access credentials for network-managers
        [
            'role' => [
                'network-manager',
            ],
            'plugin' => null,
            'controller' => [
                'AccessCredentials',
            ],
            'action' => [
                'add',
                'edit',
                'delete',
            ],
        ],
        //allow some overviews for network-managers, the checks among them: they are let into
        //the rack of overviews and work the whole file rather than one customer at a time, so
        //what does not add up in it is theirs to look up as much as anybody's
        [
            'role' => [
                'network-manager',
            ],
            'plugin' => null,
            'controller' => [
                'Overviews',
            ],
            'action' => [
                'index',
                'overviewOfActiveServices',
                'overviewOfCzechCustomerConnectionPoints',
                'overviewOfCzechCustomerConnectionSpeeds',
                'overviewOfAddressProblems',
                'overviewOfContractProblems',
                'overviewOfCustomerProblems',
            ],
        ],
        //historical connections - only index and view
        //it is recorded by the historical connections update from the sources
        [
            'role' => [
                'network-technician',
                'network-manager',
                'sales-representative',
                'sales-manager',
                'bookkeeper',
            ],
            'plugin' => null,
            'controller' => [
                'HistoricalConnections',
            ],
            'action' => [
                'index',
                'view',
            ],
        ],
        //allow all in overviews for sales-managers
        [
            'role' => [
                'sales-manager',
            ],
            'plugin' => null,
            'controller' => [
                'Overviews',
            ],
            'action' => '*',
        ],
        //the office roles reach the checks from the dashboard and from the rack of overviews
        //alike; the rack itself only lists what whoever opened it may follow, so admitting
        //them to it shows them a shorter one rather than everything in it
        [
            'role' => [
                'bookkeeper',
                'sales-representative',
            ],
            'plugin' => null,
            'controller' => [
                'Overviews',
            ],
            'action' => [
                'index',
                'overviewOfAddressProblems',
                'overviewOfContractProblems',
                'overviewOfCustomerProblems',
            ],
        ],
        //enable customer message sending for network managers and sales managers
        [
            'role' => [
                'network-manager',
                'sales-manager',
            ],
            'plugin' => null,
            'controller' => [
                'CustomerMessages',
            ],
            'action' => [
                'add',
                'addBulk',
            ],
        ],
        //allow invoice view/download for sales
        [
            'role' => [
                'sales-representative',
            ],
            'plugin' => 'Bookkeeping',
            'controller' => [
                'Invoices',
            ],
            'action' => [
                'view',
                'download',
            ],
        ],
        //enable debtor blocking/unblocking for network managers and sales
        [
            'role' => [
                'network-manager',
                'sales-representative',
            ],
            'plugin' => 'Bookkeeping',
            'controller' => [
                'Debtors',
            ],
            'action' => [
                'block',
                'unblock',
            ],
        ],
        //allow all in bookkeeping plugin for sales-managers and bookkeepers
        [
            'role' => [
                'sales-manager',
                'bookkeeper',
            ],
            'plugin' => 'Bookkeeping',
            'controller' => '*',
            'action' => '*',
        ],
        //allow view/disconnect operations in RADIUS plugin for customer service technicians
        [
            'role' => [
                'customer-service-technician',
            ],
            'plugin' => 'Radius',
            'controller' => [
                'Accounts',
            ],
            'action' => [
                'view',
                'monitoring',
                'disconnectRequest',
            ],
        ],
        //allow all standard operations in RADIUS plugin for sales, bookkeepers and networks
        [
            'role' => [
                'network-technician',
                'network-manager',
                'sales-representative',
                'sales-manager',
                'bookkeeper',
            ],
            'plugin' => 'Radius',
            'controller' => [
                'Accounts',
            ],
            'action' => [
                'view',
                'add',
                'edit',
                'monitoring',
                'disconnectRequest',
                'updateRelatedRecords',
                'removeMacAddress',
            ],
        ],
    ],
];

/*
 * Load local permissions if exists
 */
$localPermissionsFile = CONFIG . 'permissions_local.php';
if (file_exists($localPermissionsFile)) {
    $localPermissions = include $localPermissionsFile;
    /** @psalm-suppress RedundantCondition */
    if (is_array($localPermissions)) {
        // merge permissions - local first in order
        $permissions = array_merge_recursive($localPermissions, $permissions);
    }
}

return $permissions;
