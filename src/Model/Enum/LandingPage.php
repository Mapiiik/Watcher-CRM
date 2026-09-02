<?php
declare(strict_types=1);

namespace App\Model\Enum;

use App\Model\Enum\Trait\EnumOptionsTrait;
use Cake\Database\Type\EnumLabelInterface;
use Override;

/**
 * The page a user arrives at when they open the application.
 *
 * The dashboard is what most people want to see first, and for some it is a page they click
 * past every morning on the way to the one they actually work in. What is on offer here is the
 * top navigation, no more: a landing page has to be somewhere the user can also get back to,
 * and it is checked against their permissions before they are sent there.
 */
enum LandingPage: string implements EnumLabelInterface
{
    use EnumOptionsTrait;

    case Dashboard = 'dashboard';
    case Customers = 'customers';
    case Tasks = 'tasks';
    case Bookkeeping = 'bookkeeping';
    case Radius = 'radius';
    case Overviews = 'overviews';
    case Settings = 'settings';

    /**
     * @return string
     */
    #[Override]
    public function label(): string
    {
        return match ($this) {
            self::Dashboard => __('Dashboard'),
            self::Customers => __('Customers'),
            self::Tasks => __('Tasks'),
            self::Bookkeeping => __('Bookkeeping'),
            self::Radius => __('RADIUS'),
            self::Overviews => __('Overviews'),
            self::Settings => __('Settings'),
        };
    }

    /**
     * Where the page lives, as the navigation names it.
     *
     * The nesting a URL is built under is left alone: this is only ever built on the root,
     * where there is no customer or contract for the URL filter to carry over.
     *
     * @return array<string, mixed>
     */
    public function url(): array
    {
        return match ($this) {
            self::Dashboard => ['plugin' => null, 'controller' => 'Dashboard', 'action' => 'index'],
            self::Customers => ['plugin' => null, 'controller' => 'Customers', 'action' => 'index'],
            self::Tasks => ['plugin' => null, 'controller' => 'Tasks', 'action' => 'index'],
            self::Bookkeeping => ['plugin' => 'Bookkeeping', 'controller' => 'Invoices', 'action' => 'index'],
            self::Radius => ['plugin' => 'Radius', 'controller' => 'Accounts', 'action' => 'index'],
            self::Overviews => ['plugin' => null, 'controller' => 'Overviews', 'action' => 'index'],
            self::Settings => ['plugin' => null, 'controller' => 'Settings', 'action' => 'index'],
        };
    }
}
