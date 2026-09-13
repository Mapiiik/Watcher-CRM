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

    /**
     * What a folder may be filed against, where the address did not already say.
     *
     * Opened under a customer or a connection there is nothing to choose and nothing is offered.
     * Opened from the shelf itself there is, because somebody now and then has a folder in hand
     * before they have the record it belongs to.
     *
     * @return void
     */
    protected function setFormViewVars(): void
    {
        if ($this->customer_id === null) {
            $this->set('customers', $this->Documentations->Customers->find('list', order: [
                'company',
                'last_name',
                'first_name',
            ]));
        }

        if ($this->contract_id === null) {
            $contracts = $this->Documentations->Contracts->find(
                'list',
                contain: ['InstallationAddresses', 'ServiceTypes'],
                order: ['Contracts.number'],
            );

            // under a customer the contract is still chosen, but only from among their own
            if ($this->customer_id !== null) {
                $contracts->where(['Contracts.customer_id' => $this->customer_id]);
            }

            $this->set('contracts', $contracts);
        }
    }
}
