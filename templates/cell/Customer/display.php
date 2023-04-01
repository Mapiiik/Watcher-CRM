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
            <h4><?= $this->AuthLink->link(
                h($customer->name),
                ['plugin' => null, 'controller' => 'Customers', 'action' => 'view', $customer->id],
                ['escape' => false, 'class' => '']
            ) ?></h4>
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
                        [
                            'class' => 'button button-small',
                            'style' => $contract->style . ' color: inherit;',
                        ]
                    );
                }
                ?>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<br />