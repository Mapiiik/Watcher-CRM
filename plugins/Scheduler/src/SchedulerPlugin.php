<?php
declare(strict_types=1);

namespace Scheduler;

use Cake\Core\BasePlugin;
use Cake\Core\Configure;
use Cake\Core\PluginApplicationInterface;
use Cake\Event\EventManager;
use Override;
use Scheduler\Event\FailedJobListener;
use Scheduler\Event\LocalAssetsListener;
use Scheduler\Event\SchedulerRowsListener;

/**
 * What the application adds to the queue and its schedule.
 */
class SchedulerPlugin extends BasePlugin
{
    /**
     * @var bool
     */
    protected bool $middlewareEnabled = false;

    /**
     * @var bool
     */
    protected bool $routesEnabled = false;

    /**
     * Load the settings of the queue, the application's schedule and the listeners.
     *
     * @param \Cake\Core\PluginApplicationInterface $app The host application
     * @return void
     */
    #[Override]
    public function bootstrap(PluginApplicationInterface $app): void
    {
        Configure::load('Scheduler.queue');

        if (is_file(CONFIG . 'scheduler.php')) {
            Configure::load('scheduler');
        }

        $events = EventManager::instance();
        $events->on(new FailedJobListener());
        $events->on(new LocalAssetsListener());
        $events->on(new SchedulerRowsListener());
    }
}
