<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;
use Override;

/**
 * CustomerProposalsFixture
 *
 * One proposal, which the fixture's papers of a contract are a part of - there is one proposal and
 * nothing stands outside it. Anything else a customer has is what a test just opened.
 */
class CustomerProposalsFixture extends TestFixture
{
    /**
     * Init method
     *
     * @return void
     */
    #[Override]
    public function init(): void
    {
        $this->records = [
            [
                'id' => 'a7c1d5e2-3f48-4b90-9c61-2d0e7a5b8f34',
                'customer_id' => '403bab0e-52cd-4a8e-83f8-43c2457d0481',
                // Nothing of the customer's own: it is there to hold the papers of the contract.
                'purpose' => null,
                'effective_from' => '2026-09-01',
                'sent_date' => null,
                'delivery_type' => null,
                'conclusion_date' => null,
                'revoked' => null,
                'revoked_by' => null,
                'note' => null,
                'created' => 1772582400,
                'created_by' => '11edb519-be76-4d66-aea0-34188d31eae1',
                'modified' => 1772582400,
                'modified_by' => '11edb519-be76-4d66-aea0-34188d31eae1',
            ],
        ];

        parent::init();
    }
}
