<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\EntityInterface $address
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __d('ruian', 'Actions') ?></h4>
            <?= $this->AuthLink->link(
                __d('ruian', 'Edit Address'),
                ['action' => 'edit', $address->kod_adm],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->postLink(
                __d('ruian', 'Delete Address'),
                ['action' => 'delete', $address->kod_adm],
                [
                    'confirm' => __d('ruian', 'Are you sure you want to delete # {0}?', $address->kod_adm),
                    'class' => 'side-nav-item',
                ]
            ) ?>
            <?= $this->AuthLink->link(
                __d('ruian', 'List Addresses'),
                ['action' => 'index'],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->link(
                __d('ruian', 'New Address'),
                ['action' => 'add'],
                ['class' => 'side-nav-item']
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="addresses view content">
            <h3><?= h($address->kod_adm) ?></h3>
            <table>
                <tr>
                    <th><?= __d('ruian', 'Obec Nazev') ?></th>
                    <td><?= h($address->obec_nazev) ?></td>
                </tr>
                <tr>
                    <th><?= __d('ruian', 'Momc Nazev') ?></th>
                    <td><?= h($address->momc_nazev) ?></td>
                </tr>
                <tr>
                    <th><?= __d('ruian', 'Mop Nazev') ?></th>
                    <td><?= h($address->mop_nazev) ?></td>
                </tr>
                <tr>
                    <th><?= __d('ruian', 'Cast Obce Nazev') ?></th>
                    <td><?= h($address->cast_obce_nazev) ?></td>
                </tr>
                <tr>
                    <th><?= __d('ruian', 'Ulice Nazev') ?></th>
                    <td><?= h($address->ulice_nazev) ?></td>
                </tr>
                <tr>
                    <th><?= __d('ruian', 'Typ So') ?></th>
                    <td><?= h($address->typ_so) ?></td>
                </tr>
                <tr>
                    <th><?= __d('ruian', 'Cislo Orientacni Znak') ?></th>
                    <td><?= h($address->cislo_orientacni_znak) ?></td>
                </tr>
                <tr>
                    <th><?= __d('ruian', 'Geometry') ?></th>
                    <td><?= h($address->geometry) ?></td>
                </tr>
                <tr>
                    <th><?= __d('ruian', 'Geometry Jtsk') ?></th>
                    <td><?= h($address->geometry_jtsk) ?></td>
                </tr>
                <tr>
                    <th><?= __d('ruian', 'Kod Adm') ?></th>
                    <td><?= $this->Number->format($address->kod_adm) ?></td>
                </tr>
                <tr>
                    <th><?= __d('ruian', 'Obec Kod') ?></th>
                    <td><?= $this->Number->format($address->obec_kod) ?></td>
                </tr>
                <tr>
                    <th><?= __d('ruian', 'Momc Kod') ?></th>
                    <td><?= $this->Number->format($address->momc_kod) ?></td>
                </tr>
                <tr>
                    <th><?= __d('ruian', 'Mop Kod') ?></th>
                    <td><?= $this->Number->format($address->mop_kod) ?></td>
                </tr>
                <tr>
                    <th><?= __d('ruian', 'Cast Obce Kod') ?></th>
                    <td><?= $this->Number->format($address->cast_obce_kod) ?></td>
                </tr>
                <tr>
                    <th><?= __d('ruian', 'Ulice Kod') ?></th>
                    <td><?= $this->Number->format($address->ulice_kod) ?></td>
                </tr>
                <tr>
                    <th><?= __d('ruian', 'Cislo Domovni') ?></th>
                    <td><?= $this->Number->format($address->cislo_domovni) ?></td>
                </tr>
                <tr>
                    <th><?= __d('ruian', 'Cislo Orientacni') ?></th>
                    <td><?= $this->Number->format($address->cislo_orientacni) ?></td>
                </tr>
                <tr>
                    <th><?= __d('ruian', 'Psc') ?></th>
                    <td><?= $this->Number->format($address->psc) ?></td>
                </tr>
                <tr>
                    <th><?= __d('ruian', 'Plati Od') ?></th>
                    <td><?= h($address->plati_od) ?></td>
                </tr>
            </table>
        </div>
    </div>
</div>
