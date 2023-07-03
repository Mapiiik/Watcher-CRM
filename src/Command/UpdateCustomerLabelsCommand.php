<?php
declare(strict_types=1);

namespace App\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Datasource\ConnectionManager;
use Cake\I18n\DateTime;
use Cake\Log\Log;
use Cake\Utility\Hash;
use Cake\Utility\Text;
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
     * @see https://book.cakephp.org/4/en/console-commands/commands.html#defining-arguments-and-options
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
     */
    public function execute(Arguments $args, ConsoleIo $io): null|int|null
    {
        /** @var \App\Model\Table\LabelsTable $labels_table */
        $labels_table = $this->fetchTable('Labels');
        $start_time = DateTime::now();

        $labels = $labels_table
            ->find()
            ->contain('CustomerLabels');

        $label_id = $args->getArgument('label_id');
        if (!empty($label_id)) {
            $labels->where(['id' => $label_id]);
        }

        /** @var array<\App\Model\Entity\Label> $labels */
        foreach ($labels as $label) {
            $io->info(__('Processing') . ': ' . $label->name . ' (' . $label->id . ')');

            $current_customer_ids = Hash::extract($label->customer_labels, '{n}.customer_id');

            // is label dynamic?
            if ($label->dynamic) {
                // DYNAMIC
                // add customer labels (or update modified time) for IDs found in custom SQL query (for dynamic labels)
                if (!empty($label->dynamic_sql)) {
                    try {
                        $dynamic_sql_results = ConnectionManager::get('default')
                            ->execute($label->dynamic_sql)
                            ->fetchAll();
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

                    foreach ($dynamic_sql_results as $dynamic_sql_result) {
                        $customer_label_data = [
                            'label_id' => $label->id,
                            'customer_id' => $dynamic_sql_result[0],
                            'note' =>
                                __('dynamic')
                                . (!empty($dynamic_sql_result[1]) ? ' - ' . $dynamic_sql_result[1] : '')
                            ,
                        ];

                        $current_customer_label_key = array_search($dynamic_sql_result[0], $current_customer_ids);

                        if ($current_customer_label_key === false) {
                            // prepare new entity
                            $label->customer_labels[] = $labels_table->CustomerLabels
                                ->newEntity($customer_label_data);
                        } else {
                            // patch current entity
                            $label->customer_labels[$current_customer_label_key] = $labels_table->CustomerLabels
                                ->patchEntity(
                                    $label->customer_labels[$current_customer_label_key],
                                    $customer_label_data
                                );
                            // update modification time
                            $label->customer_labels[$current_customer_label_key]
                                ->modified = DateTime::now();
                        }

                        unset($customer_label_data);
                        unset($current_customer_label_key);
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
