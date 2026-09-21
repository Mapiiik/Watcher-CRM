<?php
declare(strict_types=1);

namespace WorkReports\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;
use Override;

/**
 * WorkReportItemTypesFixture
 *
 * One type for each way of stating the time: work from and until, vacation for the whole
 * day, and a doctor's visit that can be either.
 */
class WorkReportItemTypesFixture extends TestFixture
{
    public const WORK = 'a1111111-0000-4000-8000-000000000001';

    public const VACATION = 'a1111111-0000-4000-8000-000000000002';

    public const DOCTOR = 'a1111111-0000-4000-8000-000000000003';

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
                'id' => self::WORK,
                'name' => 'Work',
                'description_required' => true,
                'time_mode' => 'range',
                'counts_as_worked' => true,
                'reduces_fund' => false,
                'active' => true,
                'sort' => 0,
            ],
            [
                'id' => self::VACATION,
                'name' => 'Vacation',
                'description_required' => false,
                'time_mode' => 'whole_day',
                'counts_as_worked' => false,
                'reduces_fund' => true,
                'active' => true,
                'sort' => 0,
            ],
            [
                'id' => self::DOCTOR,
                'name' => 'Doctor',
                'description_required' => false,
                'time_mode' => 'either',
                'counts_as_worked' => false,
                'reduces_fund' => true,
                'active' => true,
                'sort' => 0,
            ],
        ];

        parent::init();
    }
}
