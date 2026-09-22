<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;
use Override;

/**
 * AvailableConnectionsFixture
 *
 * A building wired with fibre and nobody in it yet, which only the operator could have told.
 */
class AvailableConnectionsFixture extends TestFixture
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
                'id' => 'ac000000-0000-4000-8000-000000000001',
                'address_registry_source' => 'cz',
                'address_registry_reference' => '16936132',
                'address_label' => 'Luční 464, Podmoklice, 51301 Semily',
                'gps_x' => 15.33,
                'gps_y' => 50.6,
                'access_technology' => 'ftth_p2mp_pon',
                'speed_down_max' => 1024000,
                'speed_up_max' => 1024000,
                'access_point_id' => null,
                'origin' => 'manual',
                'contract_id' => null,
                'retired' => null,
                'note' => 'Wired, nobody in yet',
            ],
        ];
        parent::init();
    }
}
