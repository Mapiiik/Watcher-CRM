<?php
declare(strict_types=1);

namespace App\Command;

use Cake\Collection\Collection;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Datasource\ConnectionManager;
use Cake\I18n\DateTime;
use Cake\Log\Log;
use Cake\Utility\Text;
use PDO;
use PDOException;
use SplObjectStorage;

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
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
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
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingNativeTypeHint
     */
    public function execute(Arguments $args, ConsoleIo $io)
    {
        /** @var \App\Model\Table\LabelsTable $labels_table */
        $labels_table = $this->fetchTable('Labels');
        $start_time = DateTime::now();

        $labels = $labels_table
            ->find()
            ->contain([
                'CustomerLabels',
            ]);

        $label_id = $args->getArgument('label_id');
        if (!empty($label_id)) {
            $labels->where(['id' => $label_id]);
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

                        $dynamic_sql_results = $connection
                            ->execute($label->dynamic_sql)
                            ->fetchAll(PDO::FETCH_ASSOC);
                    } catch (PDOException $e) {
                        Log::error(
                            'The dynamic SQL query could not be processed for label.' . PHP_EOL
                            . '- ID: ' . $label->id . PHP_EOL
                            . '- ' . $e->getMessage()
                        );
                        $io->abort(
                            __('The dynamic SQL query could not be processed for label.') . PHP_EOL
                            . '- ID: ' . $label->id . PHP_EOL
                            . '- ' . $e->getMessage()
                        );
                    }

                    // convert customer lables to collection
                    $customer_labels = new Collection($label->customer_labels);

                    foreach ($dynamic_sql_results as $dynamic_sql_result) {
                        // check required value
                        if (!isset($dynamic_sql_result['customer_id'])) {
                            Log::error(
                                'The dynamic SQL query did not return a customer_id value for label.' . PHP_EOL
                                . '- ID: ' . $label->id
                            );
                            $io->abort(
                                __('The dynamic SQL query did not return a customer_id value for label.') . PHP_EOL
                                . '- ID: ' . $label->id
                            );
                        }

                        // find an existing customer label (creates a reference) or create new
                        /** @var \App\Model\Entity\CustomerLabel $customer_label */
                        $customer_label =
                            $customer_labels->firstMatch([
                                'customer_id' => $dynamic_sql_result['customer_id'],
                                'contract_id' => $dynamic_sql_result['contract_id'] ?? null,
                            ])
                            ??
                            $labels_table->CustomerLabels->newEmptyEntity();

                        // if it is a new record, add the entity to the array
                        if ($customer_label->isNew()) {
                            $label->customer_labels[] = $customer_label;
                        }

                        // patch customer label entity
                        $customer_label = $labels_table->CustomerLabels->patchEntity(
                            $customer_label,
                            [
                                'label_id' => $label->id,
                                'customer_id' => $dynamic_sql_result['customer_id'],
                                'contract_id' => $dynamic_sql_result['contract_id'] ?? null,
                                'note' =>
                                    __('dynamic')
                                    . (!empty($dynamic_sql_result['note']) ? ' - ' . $dynamic_sql_result['note'] : '')
                                ,
                            ]
                        );

                        // update modification time
                        $customer_label->modified = DateTime::now();

                        // unlink the reference to the CustomerLabel entity
                        unset($customer_label);
                    }
                }

                // save changes for customer labels
                if (
                    $labels_table->CustomerLabels->saveMany(
                        $label->customer_labels,
                        [
                            '_auditQueue' => new SplObjectStorage(),
                            '_auditTransaction' => Text::uuid(),
                        ]
                    ) === false
                ) {
                    Log::error('The related dynamic customer labels could not be saved. Please, try again.');
                    $io->abort(__('The related dynamic customer labels could not be saved. Please, try again.'));
                }

                // removal of expired customer labels (for dynamic labels - based on modification date)
                if (is_numeric($label->validity)) {
                    if (
                        $labels_table->CustomerLabels->deleteMany(
                            $labels_table->CustomerLabels->find()->where([
                                'label_id' => $label->id,
                                'modified <' => $start_time->subDays($label->validity),
                            ])->all()
                        ) === false
                    ) {
                        Log::error('The related dynamic customer labels could not be deleted. Please, try again.');
                        $io->abort(__('The related dynamic customer labels could not be deleted. Please, try again.'));
                    }
                }
            } else {
                // NOT DYNAMIC
                // removal of expired customer labels (for static labels - based on creation date)
                if (is_numeric($label->validity)) {
                    if (
                        $labels_table->CustomerLabels->deleteMany(
                            $labels_table->CustomerLabels->find()->where([
                                'label_id' => $label->id,
                                'created <' => $start_time->subDays($label->validity),
                            ])->all()
                        ) === false
                    ) {
                        Log::error('The related static customer labels could not be deleted. Please, try again.');
                        $io->abort(__('The related static customer labels could not be deleted. Please, try again.'));
                    }
                }
            }
        }
    }
}
