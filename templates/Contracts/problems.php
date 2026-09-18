<?php
/**
 * What does not add up on one contract, drawn on its own so the page need not wait for it.
 *
 * @var \App\View\AppView $this
 * @var list<array{check: \App\Check\CheckInterface, records: iterable<\Cake\Datasource\EntityInterface>}> $problems
 */

// The contract is the page's and so is its customer, so neither is repeated on every row.
echo $this->element('common/problems', [
    'problems' => $problems,
    'contract_column' => false,
    'customer_column' => false,
]);
