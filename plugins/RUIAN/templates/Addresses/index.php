<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\EntityInterface[]|\Cake\Collection\CollectionInterface $addresses
 */
?>
<div class="addresses index content">
    <?= $this->Html->link(__('New Address'), ['action' => 'add'], ['class' => 'button float-right win-link']) ?>
    <h3><?= __('Addresses') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('kod_adm') ?></th>
                    <th><?= $this->Paginator->sort('obec_kod') ?></th>
                    <th><?= $this->Paginator->sort('obec_nazev') ?></th>
                    <th><?= $this->Paginator->sort('momc_kod') ?></th>
                    <th><?= $this->Paginator->sort('momc_nazev') ?></th>
                    <th><?= $this->Paginator->sort('mop_kod') ?></th>
                    <th><?= $this->Paginator->sort('mop_nazev') ?></th>
                    <th><?= $this->Paginator->sort('cast_obce_kod') ?></th>
                    <th><?= $this->Paginator->sort('cast_obce_nazev') ?></th>
                    <th><?= $this->Paginator->sort('ulice_kod') ?></th>
                    <th><?= $this->Paginator->sort('ulice_nazev') ?></th>
                    <th><?= $this->Paginator->sort('typ_so') ?></th>
                    <th><?= $this->Paginator->sort('cislo_domovni') ?></th>
                    <th><?= $this->Paginator->sort('cislo_orientacni') ?></th>
                    <th><?= $this->Paginator->sort('cislo_orientacni_znak') ?></th>
                    <th><?= $this->Paginator->sort('psc') ?></th>
                    <th><?= $this->Paginator->sort('plati_od') ?></th>
                    <th><?= $this->Paginator->sort('geometry') ?></th>
                    <th><?= $this->Paginator->sort('geometry_jtsk') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($addresses as $address) : ?>
                <tr>
                    <td><?= $this->Number->format($address->kod_adm) ?></td>
                    <td><?= $this->Number->format($address->obec_kod) ?></td>
                    <td><?= h($address->obec_nazev) ?></td>
                    <td><?= $this->Number->format($address->momc_kod) ?></td>
                    <td><?= h($address->momc_nazev) ?></td>
                    <td><?= $this->Number->format($address->mop_kod) ?></td>
                    <td><?= h($address->mop_nazev) ?></td>
                    <td><?= $this->Number->format($address->cast_obce_kod) ?></td>
                    <td><?= h($address->cast_obce_nazev) ?></td>
                    <td><?= $this->Number->format($address->ulice_kod) ?></td>
                    <td><?= h($address->ulice_nazev) ?></td>
                    <td><?= h($address->typ_so) ?></td>
                    <td><?= $this->Number->format($address->cislo_domovni) ?></td>
                    <td><?= $this->Number->format($address->cislo_orientacni) ?></td>
                    <td><?= h($address->cislo_orientacni_znak) ?></td>
                    <td><?= $this->Number->format($address->psc) ?></td>
                    <td><?= h($address->plati_od) ?></td>
                    <td><?= h($address->geometry) ?></td>
                    <td><?= h($address->geometry_jtsk) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('View'), ['action' => 'view', $address->kod_adm]) ?>
                        <?= $this->Html->link(
                            __('Edit'),
                            ['action' => 'edit', $address->kod_adm],
                            ['class' => 'win-link']
                        ) ?>
                        <?= $this->Form->postLink(
                            __('Delete'),
                            ['action' => 'delete', $address->kod_adm],
                            ['confirm' => __('Are you sure you want to delete # {0}?', $address->kod_adm)]
                        ) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="paginator">
        <ul class="pagination">
            <?= $this->Paginator->first('<< ' . __('first')) ?>
            <?= $this->Paginator->prev('< ' . __('previous')) ?>
            <?= $this->Paginator->numbers() ?>
            <?= $this->Paginator->next(__('next') . ' >') ?>
            <?= $this->Paginator->last(__('last') . ' >>') ?>
        </ul>
        <p><?= $this->Paginator->counter(
            __('Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total')
        ) ?></p>
    </div>
</div>
