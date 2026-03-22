<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Agent\ApiClient;
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
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function ping()
    {
        $ip_address = $this->getRequest()->getParam('ip_address');

        if (!filter_var($ip_address, FILTER_VALIDATE_IP)) {
            throw new BadRequestException(__('Invalid IP address'));
        }

        $agentEnabled = filter_var(
            env('WATCHER_AGENT_ENABLED', false),
            FILTER_VALIDATE_BOOLEAN,
        );

        if ($agentEnabled) {
            try {
                $pingResults = ApiClient::ping($ip_address);
                $pingImage = $this->AgentPingImage($pingResults);
            } catch (Throwable $e) {
                Log::error('Error pinging host via Watcher Agent: ' . $e->getMessage());
                $pingResults = [];
                $pingImage = 'unknown.png';
            }
        } else {
            $pingResults = [];
            try {
                $pingImage = $this->LocalPingImage($ip_address);
            } catch (Throwable $e) {
                Log::error('Error pinging host locally: ' . $e->getMessage());
                $pingImage = 'unknown.png';
            }
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

    /**
     * Executes a local ping command and returns the appropriate image based on the results
     *
     * @param string $ipAddress The IP address to ping
     * @return string The path to the ping image
     */
    private function LocalPingImage(string $ipAddress): string
    {
        Log::warning('Local ping backend is deprecated; consider enabling Watcher Agent.');

        $cmd = sprintf(
            'ping -c 10 -W 1 -f -i 0.2 %s 2>&1',
            escapeshellarg($ipAddress),
        );

        $pingOutput = shell_exec($cmd) ?: '';

        if (strpos($pingOutput, ' 0% packet loss') !== false) {
            return 'up.png';
        }

        if (strpos($pingOutput, ' 100% packet loss') !== false) {
            return 'down.png';
        }

        return 'bad.png';
    }
}
