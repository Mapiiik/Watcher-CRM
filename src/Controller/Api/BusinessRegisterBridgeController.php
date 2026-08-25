<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\BusinessRegister\Dto\Subject;
use App\BusinessRegister\Registry;
use Cake\Http\Exception\BadRequestException;
use Cake\Log\Log;
use RuntimeException;

/**
 * Business Register Bridge Controller
 *
 * This controller serves as a bridge between the CRM's customer form and the public business
 * registers. It searches whichever register was asked for and returns the hits in the shape the
 * Select2 widget expects, so the form never has to know how any one register answers.
 */
class BusinessRegisterBridgeController extends AppController
{
    /**
     * Search method for Select2 select widget integration.
     *
     * This method is designed to be called by the Select2 widget in customer forms.
     *
     * It accepts "query" and "source" as GET parameters, performs a search against the named
     * business register, and returns results formatted for Select2.
     *
     * @return void Renders view
     */
    public function search(): void
    {
        $source = $this->getRequest()->getQuery('source'); // Required parameter naming the register
        $query = $this->getRequest()->getQuery('query'); // Search query parameter

        if (empty($source) || empty($query)) {
            throw new BadRequestException(__('Invalid request parameters'));
        }

        // A register that is turned off is refused here rather than silently answering nothing.
        if (Registry::get((string)$source) === null) {
            throw new BadRequestException(__('Unknown business register.'));
        }

        $results = [];

        // Note: the registers are asked for one page of hits, so there is never more to fetch,
        // but the "pagination" key is included for future compatibility with Select2.
        $pagination = ['more' => false];

        $answer = Registry::search(
            key: (string)$source,
            query: (string)$query,
            limit: 25,
        );

        if (!$answer->ok()) {
            $failure = $answer->failure ?? __('The business register is not configured.');
            Log::error('Error when searching the business register: ' . $failure);

            throw new RuntimeException('Error when searching the business register: ' . $failure);
        }

        // Map business register → Select2
        foreach ($answer->data as $item) {
            $results[] = [
                'id' => $source . '|' . $item->reference,
                'text' => self::label($item),
            ];
        }

        $this->set(compact('results', 'pagination'));
        $this->viewBuilder()->setOption('serialize', ['results', 'pagination']);
    }

    /**
     * One entry as a single line, so two companies of the same name can still be told apart.
     *
     * @param \App\BusinessRegister\Dto\Subject $item The entry as the register answered it.
     * @return string
     */
    private static function label(Subject $item): string
    {
        $parts = array_filter([
            trim((string)$item->name),
            trim((string)$item->identityNumber),
            trim((string)$item->address),
        ]);

        return $parts === [] ? '—' : implode(', ', $parts);
    }
}
