<?php
/**
 * WorkReports plugin — default settings.
 *
 * Loaded by the SettingsService as a fallback when no value is stored in the database; override
 * them through the settings UI rather than here (priority: DB > defaults).
 */

use Settings\ValueObject\Type\NumberType;

return [
    'work_reports' => [
        // Which days are working days, and how long one of them is
        'calendar' => [
            // Yasumi provider the public holidays and working days are taken from
            'country' => 'CzechRepublic',

            // Length of a working day at a full workload, which the monthly fund is counted in
            'daily_hours' => NumberType::ofDecimal(
                default: 8.0,
                hint: __d('work_reports', 'Hours in a working day at a full workload.'),
            ),
        ],

        // Hours an on-call day is worth, by the kind of the day
        'on_call_hours' => [
            'working_day' => NumberType::ofDecimal(
                default: 3.0,
                hint: __d('work_reports', 'Hours an on-call working day is worth.'),
            ),
            'weekend' => NumberType::ofDecimal(
                default: 12.0,
                hint: __d('work_reports', 'Hours an on-call weekend day is worth.'),
            ),
            'holiday' => NumberType::ofDecimal(
                default: 12.0,
                hint: __d('work_reports', 'Hours an on-call public holiday is worth.'),
            ),
        ],
    ],
];
