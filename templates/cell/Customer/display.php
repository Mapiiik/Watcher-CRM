<div class="container nav-container">
    <div class="content nav-content">
        <?= $this->AuthLink->link(
            h($customer->name) . ' (' . h($customer->number) . ')',
            ['plugin' => null, 'controller' => 'Customers', 'action' => 'view', $customer->id], ['class' => 'button button-small button-outline'])
        ?>

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
<br />