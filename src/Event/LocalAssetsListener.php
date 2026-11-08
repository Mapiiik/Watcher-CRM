<?php
declare(strict_types=1);

namespace App\Event;

use Cake\Event\EventInterface;
use Cake\Event\EventListenerInterface;
use Cake\Routing\Router;
use Cake\View\View;
use Override;

/**
 * Serves from here the libraries that the pages of other packages fetch from a CDN.
 *
 * The layouts of the queue, of its schedule and of the audit log name the addresses outright, and
 * opening a page must not tell anybody else who opened it. Replacing the addresses in what is
 * rendered saves carrying a copy of each layout. The files are the very same ones, so the integrity
 * the layouts ask for still holds.
 */
class LocalAssetsListener implements EventListenerInterface
{
    /**
     * The addresses in the layouts, and where the file is under `webroot/vendor`.
     *
     * @var array<string, string>
     */
    private const ASSETS = [
        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' => 'bootstrap/bootstrap.min.css',
        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js' =>
            'bootstrap/bootstrap.bundle.min.js',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css' => 'font-awesome/css/all.min.css',
        'https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js' => 'chart.js/chart.umd.min.js',
        'https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css' => 'flatpickr/flatpickr.min.css',
        'https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js' => 'flatpickr/flatpickr.min.js',
        // the audit log asks for whatever version is the latest, which has been 4.6.13 since 2022, and
        // `strtr()` tries the longer addresses above before the bare package
        'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css' => 'flatpickr/flatpickr.min.css',
        'https://cdn.jsdelivr.net/npm/flatpickr' => 'flatpickr/flatpickr.min.js',
    ];

    /**
     * The packages whose layouts name the addresses.
     *
     * @var array<string>
     */
    private const PLUGINS = ['AuditStash', 'Queue', 'QueueScheduler'];

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function implementedEvents(): array
    {
        return ['View.afterLayout' => 'useLocalAssets'];
    }

    /**
     * @param \Cake\Event\EventInterface<\Cake\View\View> $event The event.
     * @return void
     */
    public function useLocalAssets(EventInterface $event): void
    {
        $view = $event->getSubject();

        if (!$view instanceof View || !in_array($view->getPlugin(), self::PLUGINS, true)) {
            return;
        }

        $local = array_map(
            fn(string $file): string => Router::url('/vendor/' . $file),
            self::ASSETS,
        );

        $view->assign('content', strtr($view->fetch('content'), $local));
    }
}
