<?php
declare(strict_types=1);

namespace App\Controller;

use Files\Controller\Trait\DocumentationsControllerTrait;

/**
 * Documentations Controller
 *
 * @property \App\Model\Table\DocumentationsTable $Documentations
 */
class DocumentationsController extends AppController
{
    use DocumentationsControllerTrait;

    /**
     * Which records the route is standing under.
     *
     * A folder filed under a connection carries the customer as well, so a listing under a
     * customer is of everything filed about them, their connections included.
     *
     * @return array<string, string>
     */
    protected function filedUnder(): array
    {
        $under = [];

        foreach (['customer_id' => $this->customer_id, 'contract_id' => $this->contract_id] as $column => $id) {
            if ($id !== null) {
                $under[$column] = $id;
            }
        }

        return $under;
    }

    /**
     * What a folder is read together with.
     *
     * @return array<mixed>
     */
    protected function viewContain(): array
    {
        return ['DocumentationTypes', 'Customers', 'Contracts'];
    }
}
