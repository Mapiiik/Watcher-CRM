<?php
declare(strict_types=1);

namespace App\Command;

use App\Model\Table\LabelsTable;
use App\Service\OperatorReport;
use Cake\Collection\Collection;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Datasource\ConnectionManager;
use Cake\I18n\DateTime;
use Cake\Log\Log;
use Cake\Utility\Text;
use Override;
use PDO;
use PDOException;
use SplObjectStorage;
use Throwable;

/**
 * AutoAssignContractsToAccessPoints command.
 */
class UpdateCustomerLabelsCommand extends Command
{
    /**
     * Hook method for defining this command's option parser.
     *
     * @see https://book.cakephp.org/5/en/console-commands/commands.html#defining-arguments-and-options
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
     * @return \Cake\Console\ConsoleOptionParser The built parser.
     */
    #[Override]
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser->addArgument('label_id', [
            'help' => 'ID of the label to be updated',
            'required' => false,
        ]);

        return $parser;
    }

    /**
     * Implement this method with your command's logic.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int|null|void The exit code or null for success
     */
    #[Override]
    public function execute(Arguments $args, ConsoleIo $io)
    {
        try {
            $labelsTable = $this->fetchTable(LabelsTable::class);
            $startTime = DateTime::now();

            $labels = $labelsTable
                ->find()
                ->contain([
                    'CustomerLabels',
                ]);

            $labelId = $args->getArgument('label_id');
            if (!in_array($labelId, [null, '', '0'], true)) {
                $labels->where(['id' => $labelId]);
            }

            foreach ($labels as $label) {
                /** @var \App\Model\Entity\Label $label */

                $io->info(__('Processing') . ': ' . $label->name . ' (' . $label->id . ')');

                // is label dynamic?
                if ($label->dynamic) {
                    // DYNAMIC
                    // add customer labels (or update modified time) for IDs found in custom SQL query (for dynamic labels)
                    if (!empty($label->dynamic_sql)) {
                        try {
                            /** @var \Cake\Database\Connection $connection */
                            $connection = ConnectionManager::get('default');

                            $dynamicSqlResults = $connection
                                ->execute($label->dynamic_sql)
                                ->fetchAll(PDO::FETCH_ASSOC);
                        } catch (PDOException $e) {
                            Log::error(
                                'The dynamic SQL query could not be processed for label.' . PHP_EOL
                                . '- ID: ' . $label->id . PHP_EOL
                                . '- ' . $e->getMessage(),
                            );
                            $io->abort(
                                __('The dynamic SQL query could not be processed for label.') . PHP_EOL
                                . '- ID: ' . $label->id . PHP_EOL
                                . '- ' . $e->getMessage(),
                            );
                        }

                        // convert customer lables to collection
                        $customerLabels = new Collection($label->customer_labels);

                        foreach ($dynamicSqlResults as $dynamicSqlResult) {
                            // check required value
                            if (!isset($dynamicSqlResult['customer_id'])) {
                                Log::error(
                                    'The dynamic SQL query did not return a customer_id value for label.' . PHP_EOL
                                    . '- ID: ' . $label->id,
                                );
                                $io->abort(
                                    __('The dynamic SQL query did not return a customer_id value for label.') . PHP_EOL
                                    . '- ID: ' . $label->id,
                                );
                            }

                            // find an existing customer label (creates a reference) or create new
                            /** @var \App\Model\Entity\CustomerLabel $customerLabel */
                            $customerLabel =
                                $customerLabels->firstMatch([
                                    'customer_id' => $dynamicSqlResult['customer_id'],
                                    'contract_id' => $dynamicSqlResult['contract_id'] ?? null,
                                ])
                                ??
                                $labelsTable->CustomerLabels->newEmptyEntity();

                            // if it is a new record, add the entity to the array
                            if ($customerLabel->isNew()) {
                                $label->customer_labels[] = $customerLabel;
                            }

                            // patch customer label entity
                            $customerLabel = $labelsTable->CustomerLabels->patchEntity(
                                $customerLabel,
                                [
                                    'label_id' => $label->id,
                                    'customer_id' => $dynamicSqlResult['customer_id'],
                                    'contract_id' => $dynamicSqlResult['contract_id'] ?? null,
                                    'note' =>
                                        __('dynamic')
                                        . (empty($dynamicSqlResult['note']) ? '' : ' - ' . $dynamicSqlResult['note'])
                                    ,
                                ],
                            );

                            // update modification time
                            $customerLabel->modified = DateTime::now();

                            // unlink the reference to the CustomerLabel entity
                            unset($customerLabel);
                        }
                    }
                    // save changes for customer labels
                    if (
                        $labelsTable->CustomerLabels->saveMany(
                            $label->customer_labels,
                            [
                                // saveMany audit options kept intentionally:
                                // - mapiiik/audit-log (5.x, 6.x) logs nothing without them
                                // - even audit-stash 2.0.1+ groups the batch under one transaction id only
                                //   when they're passed (otherwise each record gets its own)
                                '_auditQueue' => new SplObjectStorage(),
                                '_auditTransaction' => Text::uuid(),
                            ],
                        ) === false
                    ) {
                        Log::error('The related dynamic customer labels could not be saved. Please, try again.');
                        $io->abort(
                            __('The related dynamic customer labels could not be saved. Please, try again.'),
                        );
                    }
                    // removal of expired customer labels (for dynamic labels - based on modification date)
                    if (
                        is_numeric($label->validity)
                        && $labelsTable->CustomerLabels->deleteMany(
                            $labelsTable->CustomerLabels->find()
                                ->where([
                                    'label_id' => $label->id,
                                    'modified <' => $startTime->subDays($label->validity),
                                ])
                                ->all(),
                        ) === false
                    ) {
                        Log::error('The related dynamic customer labels could not be deleted. Please, try again.');
                        $io->abort(
                            __('The related dynamic customer labels could not be deleted. Please, try again.'),
                        );
                    }
                } elseif (is_numeric($label->validity)) {
                    // NOT DYNAMIC
                    // removal of expired customer labels (for static labels - based on creation date)
                    if (
                        $labelsTable->CustomerLabels->deleteMany(
                            $labelsTable->CustomerLabels->find()->where([
                                'label_id' => $label->id,
                                'created <' => $startTime->subDays($label->validity),
                            ])->all(),
                        ) === false
                    ) {
                        Log::error('The related static customer labels could not be deleted. Please, try again.');
                        $io->abort(
                            __('The related static customer labels could not be deleted. Please, try again.'),
                        );
                    }
                }
            }

            return static::CODE_SUCCESS;
        } catch (Throwable $e) {
            Log::error(
                'Error during customer labels update: ' . PHP_EOL . $e->getMessage(),
            );

            $io->error(__(
                'Error during customer labels update: {0}',
                $e->getMessage(),
            ));

            OperatorReport::send(
                __('Customer labels update failed'),
                __(
                    'Customer labels update failed.' . PHP_EOL . PHP_EOL
                    . 'Error: {0}',
                    [$e->getMessage()],
                ),
            );

            return static::CODE_ERROR;
        }
    }
}
