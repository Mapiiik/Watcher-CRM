<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Api;

use Cake\Controller\Controller;
use Cake\Core\Configure\Engine\PhpConfig;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use CakeDC\Auth\Rbac\Rbac;
use PHPUnit\Framework\Attributes\DataProvider;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionMethod;

/**
 * Who reaches what under the Api prefix, asked of the real rules action by action.
 *
 * What guards a form does not stand in front of this prefix, so the list is written out in full
 * and an action nobody has decided about fails here instead of being open to whoever a rule for
 * the pages happens to fit.
 */
class ApiPermissionsTest extends TestCase
{
    /**
     * What the pages call on their own, which is all of the API the staff have any use for.
     *
     * @var list<string>
     */
    private const OPEN_TO_THE_STAFF = [
        'AddressesBridge::search',
        'AgentBridge::ping',
        'BusinessRegisterBridge::search',
        'GeocoderBridge::search',
        'NetworkManagementSystemBridge::accessPoints',
        'NetworkManagementSystemBridge::ipAddressRanges',
        'NetworkManagementSystemBridge::routerosDevices',
    ];

    /**
     * What each of the other applications asks for.
     *
     * @var array<string, list<string>>
     */
    private const OPEN_TO_THE_MACHINES = [
        'api' => [
            'AccessPoints::references',
            'Customers::customerPoints',
            'Customers::index',
            'Customers::view',
            'Tasks::index',
            'Tasks::search',
            'Tasks::view',
        ],
        'watcher-nms' => [
            'AccessPoints::references',
            'Customers::customerPoints',
            'Tasks::search',
        ],
        'customer-portal' => [
            'CustomerPortal::access',
            'CustomerPortal::accessRequest',
            'CustomerPortal::consents',
            'CustomerPortal::customer',
            'CustomerPortal::invoiceDownload',
            'CustomerPortal::invoices',
            'CustomerPortal::login',
            'CustomerPortal::password',
            'CustomerPortal::setAccess',
        ],
        'customer-identification' => [
            'CustomerPortal::identify',
        ],
    ];

    /**
     * The roles of the people working in the application.
     *
     * @return array<string, array{string}>
     */
    public static function staff(): array
    {
        $roles = [
            'user',
            'customer-service-technician',
            'network-technician',
            'network-manager',
            'sales-representative',
            'sales-manager',
            'bookkeeper',
            'manager',
        ];

        return array_combine($roles, array_map(fn(string $role): array => [$role], $roles));
    }

    /**
     * @param string $role The role.
     * @return void
     */
    #[DataProvider('staff')]
    public function testTheStaffReachOnlyWhatThePagesCall(string $role): void
    {
        $this->assertSame(self::OPEN_TO_THE_STAFF, $this->reachedBy($role));
    }

    /**
     * @return void
     */
    public function testEachMachineRoleReachesOnlyWhatItIsListedFor(): void
    {
        foreach (self::OPEN_TO_THE_MACHINES as $role => $actions) {
            $this->assertSame($actions, $this->reachedBy($role), $role);
        }
    }

    /**
     * Invoices leave through the customer portal, one customer at a time. A key of the network
     * management system opens none of them, it has no use for them.
     *
     * @return void
     */
    public function testTheNetworkManagementSystemDownloadsNoInvoice(): void
    {
        $request = new ServerRequest(['params' => [
            'prefix' => null,
            'plugin' => 'Bookkeeping',
            'controller' => 'Invoices',
            'action' => 'download',
            'pass' => [],
        ]]);

        $this->assertFalse($this->rbac()->checkPermissions(['id' => 'anybody', 'role' => 'watcher-nms'], $request));
    }

    /**
     * The actions under the prefix a role is let into, in a fixed order.
     *
     * A rule deciding by what the request carries is taken as a yes, since it is the reach that is
     * asked about here and no such request is being made.
     *
     * @param string $role The role.
     * @return list<string>
     */
    private function reachedBy(string $role): array
    {
        $rbac = $this->rbac();

        $reached = [];
        foreach ($this->actions() as [$prefix, $controller, $action]) {
            $request = new ServerRequest(['params' => [
                'prefix' => $prefix,
                'plugin' => null,
                'controller' => $controller,
                'action' => $action,
                'pass' => [],
            ]]);

            if ($rbac->checkPermissions(['id' => 'anybody', 'role' => $role], $request)) {
                $reached[] = substr($prefix . '/', 4) . $controller . '::' . $action;
            }
        }
        sort($reached);

        return $reached;
    }

    /**
     * The permissions of the application.
     *
     * @return \CakeDC\Auth\Rbac\Rbac
     */
    private function rbac(): Rbac
    {
        /** @var list<array<string, mixed>> $rules */
        $rules = (new PhpConfig())->read('permissions')['CakeDC/Auth.permissions'];
        foreach ($rules as $index => $rule) {
            if (is_callable($rule['allowed'] ?? null)) {
                $rules[$index]['allowed'] = true;
            }
        }

        return new Rbac(['permissions' => $rules, 'log' => false]);
    }

    /**
     * Every action of the controllers under the prefix.
     *
     * @return list<array{string, string, string}>
     */
    private function actions(): array
    {
        $actions = [];
        $root = APP . 'Controller' . DS . 'Api';

        /** @var iterable<\SplFileInfo> $files */
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
        foreach ($files as $file) {
            $name = $file->getBasename('Controller.php');
            if ($name === $file->getBasename() || $name === 'App') {
                continue;
            }

            $path = trim(str_replace([$root, DS], ['', '/'], $file->getPath()), '/');
            $prefix = $path === '' ? 'Api' : 'Api/' . $path;
            /** @var class-string $class */
            $class = 'App\\Controller\\' . str_replace('/', '\\', $prefix) . '\\' . $name . 'Controller';

            foreach ((new ReflectionClass($class))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                if (!method_exists(Controller::class, $method->getName())) {
                    $actions[] = [$prefix, $name, $method->getName()];
                }
            }
        }

        return $actions;
    }
}
