<?php
/**
 * What does not add up about one customer, drawn on its own so the page need not wait for it.
 *
 * @var \App\View\AppView $this
 * @var list<array{check: \App\Check\CheckInterface, records: iterable<\Cake\Datasource\EntityInterface>}> $problems
 */

echo $this->element('common/problems', ['problems' => $problems, 'customer_column' => false]);
