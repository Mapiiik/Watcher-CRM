<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Agent\ApiClient as AgentApiClient;
use App\Agent\Dto\PingResult;
use App\View\AjaxView;
use Cake\Http\Exception\BadRequestException;
use Cake\Log\Log;
use Cake\View\JsonView;
use Override;

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

        $ping = AgentApiClient::ping($ip_address);

        if ($ping->unanswered()) {
            Log::error('Error pinging host via Watcher Agent: ' . $ping->failure);
        }

        // The answer goes out as it arrived, so whatever reads this endpoint as JSON keeps
        // reading what the agent said rather than what this application made of it.
        $this->set('pingResults', $ping->data->raw ?? []);
        $pingImage = $this->AgentPingImage($ping->data);
        $this->set('pingImage', $pingImage);
        $this->viewBuilder()->setOption('serialize', ['pingResults', 'pingImage']);
    }

    /**
     * Returns the appropriate ping image based on the ping results
     *
     * A host nobody could ask about is not a host that is down: the first says the agent did not
     * answer, the second that the host did not, and an operator acts on them differently.
     *
     * @param \App\Agent\Dto\PingResult|null $ping What came of the ping, where anything did.
     * @return string The path to the ping image
     */
    private function AgentPingImage(?PingResult $ping): string
    {
        if ($ping === null) {
            return 'unknown.png';
        }

        if ($ping->isHealthy()) {
            return 'up.png';
        }

        return $ping->reachable ? 'bad.png' : 'down.png';
    }
}
