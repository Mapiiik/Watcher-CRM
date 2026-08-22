<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Addresses\ApiClient as AddressesApiClient;
use App\Controller\AppController;
use Cake\Http\Exception\BadRequestException;
use Cake\Log\Log;
use Cake\View\JsonView;
use Override;
use RuntimeException;
use Throwable;

/**
 * Addresses Bridge Controller
 *
 * This controller serves as a bridge between the CRM's address entities and the
 * geo-addresses-postgis API. It provides endpoints for searching addresses and
 * retrieving formatted labels, allowing the CRM to leverage the authoritative
 * data from the registry without coupling its internal models directly to the
 * API's data structures.
 */
class AddressesBridgeController extends AppController
{
    /**
     * Returns supported output types
     */
    #[Override]
    public function viewClasses(): array
    {
        return [JsonView::class];
    }

    /**
     * Search method for Select2 select widget integration.
     *
     * This method is designed to be called by the Select2 widget in address forms.
     *
     * It accepts "query" and "country_code" as GET parameters, performs a search against the
     * Addresses API, and returns results formatted for Select2.
     *
     * @return void Renders view
     */
    public function search(): void
    {
        $country = $this->getRequest()->getQuery('country_code'); // Required parameter to scope the search
        $query = $this->getRequest()->getQuery('query'); // Search query parameter
        //$page = (int)($this->getRequest()->getQuery('page') ?? 1); // Optional pagination parameter (not currently supported by the API)

        if (empty($country) || empty($query)) {
            throw new BadRequestException(__('Invalid request parameters'));
        }

        $results = [];

        // Note: The API currently doesn't support pagination, but we include the "pagination" key in the response
        // for future compatibility with Select2's infinite scrolling.
        $pagination = ['more' => false];

        try {
            /** @var \Cake\Collection\CollectionInterface<int, \App\Addresses\Dto\Address> $found */
            $found = AddressesApiClient::search(
                country: strtolower((string)$country),
                q: $query,
                limit: 50,
            )->orFail(__('The national address registry is not configured.'));

            // Map Addresses API → Select2
            foreach ($found as $item) {
                $results[] = [
                    'id' => $item->key(),
                    'text' => $item->formattedAddress ?? '—',
                ];
            }
        } catch (Throwable $e) {
            Log::error('Error when searching addresses: ' . $e->getMessage());
            throw new RuntimeException(
                'Error when searching addresses: ' . $e->getMessage(),
                $e->getCode(),
                previous: $e,
            );
        }

        $this->set(compact('results', 'pagination'));
        $this->viewBuilder()->setOption('serialize', ['results', 'pagination']);
    }
}
