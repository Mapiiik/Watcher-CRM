<div class="container nav-container">
    <div class="content nav-content top-nav">
        <div class="nav-content-left">
            <?= $this->AuthLink->link(
                '<h4>' . h($customer->name) . ' (' . h($customer->number) . ')' . '</h4>',
                ['plugin' => null, 'controller' => 'Customers', 'action' => 'view', $customer->id], ['escape' => false, 'class' => ''])
            ?>
        </div>
        <div class="nav-content-right">
            <?php foreach ($customer->contracts as $contract): ?>
                <?= $this->AuthLink->link(
                    $contract->number .
                        ($contract->has('service_type') ? ' - ' . $contract->service_type->name : '') .
                        ($contract->has('installation_address') ? ' - ' . $contract->installation_address->address : ''),
                    ['plugin' => null, 'controller' => 'Contracts', 'action' => 'view', $contract->id], ['class' => 'button button-small' . (($contract->id == $this->request->getParam('contract_id')) ? ' button-selected' : '')])
                ?>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<br />