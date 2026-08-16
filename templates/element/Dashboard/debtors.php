<?php
use Cake\I18n\Number;

/**
 * @var \App\View\AppView $this
 * @var int $total
 * @var float $overdue_debt
 */
?>
<?php if ($total === 0) : ?>
    <p><?= __('Nobody is overdue past the set tolerances.') ?></p>
<?php else : ?>
    <p class="dashboard-figure"><?= h((string)$total) ?></p>
    <p><?= __('owing {0} past the due date.', Number::currency($overdue_debt)) ?></p>
    <?php $url = [
        'plugin' => 'Bookkeeping',
        'controller' => 'Debtors',
        'action' => 'index',
        'customer_id' => false,
    ] ?>
    <?php // a role that may not open the listing is turned back with a flash, which says
          // more than a link that quietly is not there ?>
    <p><?= $this->Html->link(__('Open the debtor listing'), $url) ?></p>
<?php endif ?>
