<?php
declare(strict_types=1);

use App\Command\AvailableConnectionsSyncCommand;
use App\Command\ProcessEmailsCommand;
use App\Command\ProcessSmsCommand;
use App\Command\ProcessUnsignedContractsCommand;
use App\Command\RebuildFulltextSearchCustomersCommand;
use App\Command\UpdateCustomerLabelsCommand;
use App\Command\UpdateHistoricalConnectionsCommand;
use Bookkeeping\Command\LoadInvoicesCommand;
use Bookkeeping\Command\ProcessDebtorsCommand;
use Bookkeeping\Command\SendIssuedInvoicesCommand;
use Bookkeeping\Command\SendPartnersCommand;
use Files\Command\CheckFilesCommand;
use Files\Command\FilesTrashPurgeCommand;
use Files\Command\GeneratePreviewsCommand;
use Radius\Command\UpdateRelatedRecordsForAccountsCommand;

/*
 * What the application schedules, see plugins/Scheduler/README.md. These only ever add a row that
 * is missing, an installation changes its own in the application. A row is off unless it says
 * `'enabled' => true`, which starts it on every installation with the next release.
 *
 * The letters to customers have a window: after an outage they may run late, but not at night. It
 * opens at the time of the row, or a day that caught up in the morning would run again at that time.
 */
return [
    'Scheduler' => [
        'defaults' => [
            'Send issued invoices' => [
                'command' => SendIssuedInvoicesCommand::class,
                'frequency' => '*/15 * * * *',
            ],
            'Process emails' => [
                'command' => ProcessEmailsCommand::class,
                'args' => ['--limit', '60'],
                'frequency' => '*/20 * * * *',
            ],
            // After the e-mails, which take a minute: an SMS at a time takes more than that
            'Process SMS' => [
                'command' => ProcessSmsCommand::class,
                'args' => ['--limit', '10'],
                'frequency' => '*/20 * * * *',
                'priority' => 9,
            ],
            'Process debtors' => [
                'command' => ProcessDebtorsCommand::class,
                'frequency' => '55 12 * * 1-5',
                'window' => ['from' => '12:55', 'to' => '18:00'],
            ],
            'Process debtors at the weekend' => [
                'command' => ProcessDebtorsCommand::class,
                'args' => ['--only_notify'],
                'frequency' => '55 12 * * 0,6',
                'window' => ['from' => '12:55', 'to' => '18:00'],
            ],
            // Does nothing until the notifications are switched on in the settings
            'Process unsigned contracts' => [
                'command' => ProcessUnsignedContractsCommand::class,
                'frequency' => '45 12 * * *',
                'window' => ['from' => '12:45', 'to' => '18:00'],
            ],
            'Load invoices' => [
                'command' => LoadInvoicesCommand::class,
                'frequency' => '30 6-17 * * *',
            ],
            'Send partners' => [
                'command' => SendPartnersCommand::class,
                'frequency' => '0 7,12 * * *',
            ],
            'Update RADIUS accounts' => [
                'command' => UpdateRelatedRecordsForAccountsCommand::class,
                'args' => [
                    '--radcheck',
                    '--radreply',
                    '--radusergroup',
                    '--reconnect_modified_accounts',
                    '--send_change_log_by_email',
                ],
                'frequency' => '0 1 * * *',
            ],
            'Update customer labels' => [
                'command' => UpdateCustomerLabelsCommand::class,
                'frequency' => '30 */12 * * *',
            ],
            'Update historical connections' => [
                'command' => UpdateHistoricalConnectionsCommand::class,
                'frequency' => '0 2 * * *',
            ],
            'Rebuild customer search' => [
                'command' => RebuildFulltextSearchCustomersCommand::class,
                'frequency' => '0 3 * * *',
            ],
            'Sync available connections' => [
                'command' => AvailableConnectionsSyncCommand::class,
                'frequency' => '0 4 * * *',
            ],
            'Purge files trash' => [
                'command' => FilesTrashPurgeCommand::class,
                'frequency' => '30 3 * * *',
            ],
            'Check files' => [
                'command' => CheckFilesCommand::class,
                'frequency' => '0 5 * * 0',
            ],
            'Generate previews' => [
                'command' => GeneratePreviewsCommand::class,
                'frequency' => '30 5 * * *',
            ],
        ],
    ],
];
