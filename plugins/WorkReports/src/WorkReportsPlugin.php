<?php
declare(strict_types=1);

namespace WorkReports;

use Cake\Core\BasePlugin;
use Cake\Routing\RouteBuilder;
use Override;

/**
 * Plugin for WorkReports
 *
 * Monthly work reports of the staff, whose items are also the record of the work done at
 * customers. The application links to it only where the plugin is loaded.
 *
 * @psalm-suppress UnusedClass
 */
class WorkReportsPlugin extends BasePlugin
{
    /**
     * Add routes for the plugin.
     *
     * The items are also reached from a customer or a contract, so the nested routes of the
     * application are repeated here the way the RADIUS plugin repeats them.
     *
     * @param \Cake\Routing\RouteBuilder $routes The route builder to update.
     * @return void
     */
    #[Override]
    public function routes(RouteBuilder $routes): void
    {
        $routes->plugin(
            'WorkReports',
            ['path' => '/work-reports'],
            function (RouteBuilder $builder): void {
                $builder
                    ->connect('/customers/{customer_id}/contracts/{contract_id}/{controller}', ['action' => 'index'])
                    ->setPatterns([
                        'customer_id' => RouteBuilder::UUID,
                        'contract_id' => RouteBuilder::UUID,
                    ]);
                $builder
                    ->connect('/customers/{customer_id}/contracts/{contract_id}/{controller}/{action}/*', [])
                    ->setPatterns([
                        'customer_id' => RouteBuilder::UUID,
                        'contract_id' => RouteBuilder::UUID,
                    ]);
                $builder
                    ->connect('/customers/{customer_id}/{controller}', ['action' => 'index'])
                    ->setPatterns(['customer_id' => RouteBuilder::UUID]);
                $builder
                    ->connect('/customers/{customer_id}/{controller}/{action}/*', [])
                    ->setPatterns(['customer_id' => RouteBuilder::UUID]);

                $builder->fallbacks();
            },
        );
        parent::routes($routes);
    }
}
