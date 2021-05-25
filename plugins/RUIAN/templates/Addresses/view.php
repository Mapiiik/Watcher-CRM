<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\EntityInterface $address
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('Edit Address'), ['action' => 'edit', $address->kod_adm], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete Address'), ['action' => 'delete', $address->kod_adm], ['confirm' => __('Are you sure you want to delete # {0}?', $address->kod_adm), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Addresses'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Address'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-90">
        <div class="addresses view content">
            <h3><?= h($address->kod_adm) ?></h3>
            <table>
                <tr>
                    <th><?= __('Obec Nazev') ?></th>
                    <td><?= h($address->obec_nazev) ?></td>
                </tr>
                <tr>
                    <th><?= __('Momc Nazev') ?></th>
                    <td><?= h($address->momc_nazev) ?></td>
                </tr>
                <tr>
                    <th><?= __('Mop Nazev') ?></th>
                    <td><?= h($address->mop_nazev) ?></td>
                </tr>
                <tr>
                    <th><?= __('Cast Obce Nazev') ?></th>
                    <td><?= h($address->cast_obce_nazev) ?></td>
                </tr>
                <tr>
                    <th><?= __('Ulice Nazev') ?></th>
                    <td><?= h($address->ulice_nazev) ?></td>
                </tr>
                <tr>
                    <th><?= __('Typ So') ?></th>
                    <td><?= h($address->typ_so) ?></td>
                </tr>
                <tr>
                    <th><?= __('Cislo Orientacni Znak') ?></th>
                    <td><?= h($address->cislo_orientacni_znak) ?></td>
                </tr>
                <tr>
                    <th><?= __('Geometry') ?></th>
                    <td><?= h($address->geometry) ?></td>
                </tr>
                <tr>
                    <th><?= __('Geometry Jtsk') ?></th>
                    <td><?= h($address->geometry_jtsk) ?></td>
                </tr>
                <tr>
                    <th><?= __('Kod Adm') ?></th>
                    <td><?= $this->Number->format($address->kod_adm) ?></td>
                </tr>
                <tr>
                    <th><?= __('Obec Kod') ?></th>
                    <td><?= $this->Number->format($address->obec_kod) ?></td>
                </tr>
                <tr>
                    <th><?= __('Momc Kod') ?></th>
                    <td><?= $this->Number->format($address->momc_kod) ?></td>
                </tr>
                <tr>
                    <th><?= __('Mop Kod') ?></th>
                    <td><?= $this->Number->format($address->mop_kod) ?></td>
                </tr>
                <tr>
                    <th><?= __('Cast Obce Kod') ?></th>
                    <td><?= $this->Number->format($address->cast_obce_kod) ?></td>
                </tr>
                <tr>
                    <th><?= __('Ulice Kod') ?></th>
                    <td><?= $this->Number->format($address->ulice_kod) ?></td>
                </tr>
                <tr>
                    <th><?= __('Cislo Domovni') ?></th>
                    <td><?= $this->Number->format($address->cislo_domovni) ?></td>
                </tr>
                <tr>
                    <th><?= __('Cislo Orientacni') ?></th>
                    <td><?= $this->Number->format($address->cislo_orientacni) ?></td>
                </tr>
                <tr>
                    <th><?= __('Psc') ?></th>
                    <td><?= $this->Number->format($address->psc) ?></td>
                </tr>
                <tr>
                    <th><?= __('Plati Od') ?></th>
                    <td><?= h($address->plati_od) ?></td>
                </tr>
            </table>
        </div>
    </div>
</div>
