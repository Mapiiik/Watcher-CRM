<?php
/**
 * @var \App\View\AppView $this
 * @var \Ruian\Model\Entity\Address $address
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __d('ruian', 'Actions') ?></h4>
            <?= $this->AuthLink->link(
                __d('ruian', 'List Addresses'),
                ['action' => 'index'],
                ['class' => 'side-nav-item']
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="addresses form content">
            <?= $this->Form->create($address) ?>
            <fieldset>
                <legend><?= __d('ruian', 'Add Address') ?></legend>
                <?php
                    echo $this->Form->control('obec_kod');
                    echo $this->Form->control('obec_nazev');
                    echo $this->Form->control('momc_kod');
                    echo $this->Form->control('momc_nazev');
                    echo $this->Form->control('mop_kod');
                    echo $this->Form->control('mop_nazev');
                    echo $this->Form->control('cast_obce_kod');
                    echo $this->Form->control('cast_obce_nazev');
                    echo $this->Form->control('ulice_kod');
                    echo $this->Form->control('ulice_nazev');
                    echo $this->Form->control('typ_so');
                    echo $this->Form->control('cislo_domovni');
                    echo $this->Form->control('cislo_orientacni');
                    echo $this->Form->control('cislo_orientacni_znak');
                    echo $this->Form->control('psc');
                    echo $this->Form->control('plati_od', ['empty' => true]);
                    echo $this->Form->control('geometry');
                    echo $this->Form->control('geometry_jtsk');
                ?>
            </fieldset>
            <?= $this->Form->button(__d('ruian', 'Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
