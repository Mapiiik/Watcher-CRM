<?php
declare(strict_types=1);

namespace Scheduler\Event;

use Cake\Event\EventInterface;
use Cake\Event\EventListenerInterface;
use Cake\Routing\Router;
use Cake\View\View;
use Override;

/**
 * Serves the libraries of the queue's pages from here instead of a CDN.
 *
 * Their layouts name the addresses outright, and opening a page must not tell anybody else who
 * opened it. Replacing the addresses in what is rendered saves carrying a copy of both layouts.
 * The files are the very same ones, so the integrity the layouts ask for still holds.
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
    ];

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

        if (!$view instanceof View || !in_array($view->getPlugin(), ['Queue', 'QueueScheduler'], true)) {
            return;
        }

        $local = array_map(
            fn(string $file): string => Router::url('/scheduler/vendor/' . $file),
            self::ASSETS,
        );

        $view->assign('content', strtr($view->fetch('content'), $local));
    }
}
