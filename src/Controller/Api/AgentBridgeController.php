<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Agent\ApiClient as AgentApiClient;
use App\Controller\AppController;
use App\View\AjaxView;
use Cake\Http\Exception\BadRequestException;
use Cake\Log\Log;
use Cake\View\JsonView;
use Override;
use Throwable;

/**
 * Network Management System Bridge Controller
 */
class AgentBridgeController extends AppController
{
    /**
     * Returns supported output types
     */
    #[Override]
    public function viewClasses(): array
    {
        return [JsonView::class, AjaxView::class];
    }

    /**
     * Ping method
     *
     * @return void Renders view
     */
    public function ping(): void
    {
        $ip_address = $this->getRequest()->getParam('ip_address');

        if (!filter_var($ip_address, FILTER_VALIDATE_IP)) {
            throw new BadRequestException(__('Invalid IP address'));
        }

        try {
            $pingResults = AgentApiClient::ping($ip_address);
            $pingImage = $this->AgentPingImage($pingResults);
        } catch (Throwable $e) {
            Log::error('Error pinging host via Watcher Agent: ' . $e->getMessage());
            $pingResults = [];
            $pingImage = 'unknown.png';
        }

        $this->set('pingResults', $pingResults);
        $this->set('pingImage', $pingImage);
        $this->viewBuilder()->setOption('serialize', ['pingResults', 'pingImage']);
    }

    /**
     * Returns the appropriate ping image based on the ping results
     *
     * @param array $pingResults The ping results
     * @return string The path to the ping image
     */
    private function AgentPingImage(array $pingResults): string
    {
        if (!empty($pingResults['reachable']) && ($pingResults['loss_pct'] ?? 100) === 0) {
            return 'up.png';
        }

        if (!empty($pingResults['reachable'])) {
            return 'bad.png';
        }

        return 'down.png';
    }
}
