<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Customer $customer
 * @var bool $compact
 */
?>
<div class="container nav-container">
    <div class="content nav-content top-nav">
        <div class="nav-content-left">
            <?= $this->AuthLink->link(
                '<h4>' . h($customer->name) . '</h4>',
                ['plugin' => null, 'controller' => 'Customers', 'action' => 'view', $customer->id],
                ['escape' => false, 'class' => '']
            ) ?>
        </div>
        <div class="nav-content-right">
            <?php foreach ($customer->contracts as $contract) : ?>
                <?php
                if ($this->getRequest()->getParam('contract_id') == $contract->id) {
                    echo $this->AuthLink->link(
                        $contract->name,
                        ['plugin' => null, 'controller' => 'Contracts', 'action' => 'view', $contract->id],
                        ['class' => 'button button-small button-selected']
                    );
                } elseif (!$compact) { //skip non selected contracts in compact mode
                    echo $this->AuthLink->link(
                        $contract->name,
                        ['plugin' => null, 'controller' => 'Contracts', 'action' => 'view', $contract->id],
                        ['class' => 'button button-small']
                    );
                }
                ?>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<br />