<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         0.10.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 * @var \App\View\AppView $this
 * @var array<string> $data
 */
?>
Vážený zákazníku,

více než 10 let se nám dařilo držet naše ceny připojení k internetu stále stejné, ale bohužel z důvodu současné ekonomické situace, kdy se neustále navyšují náklady na provoz, jsme byli nuceni pro Vás připravit nové tarify s vyššími rychlostmi, ale i mírným navýšením ceny.

Od <?= $data['new_billing_from'] ?> bude na Vaší přípojce č. <?= $data['contract_number'] ?><?= $data['installation_address'] ? ', na adrese ' . $data['installation_address'] : '' ?>

změněn stávající tarif <?= $data['original_billing_name'] ?> za <?= $this->Number->currency($data['original_billing_sum']) ?>

na nový tarif <?= $data['new_billing_name'] ?> za <?= $this->Number->currency($data['new_billing_sum']) ?>.

Nový ceník platný od 01.10.2023 je uveřejněny na našich internetových stránkách zde: https://netair.cz/cenik-pripojeni/

V souladu s ust. § 63 odst. 6 zákona č. 127/2005 Sb. o elektronických komunikacích Vás také informujeme, že jestliže tuto změnu neakceptujete, jste oprávněn smlouvu s naší společností bez sankce vypovědět k datu nabytí účinnosti této změny.

Pokud budete mít jakékoliv dotazy neváhejte nás kontaktovat

NETAIR, s.r.o.
512 43 Jablonec nad Jizerou, č.p. 299
Mail: smlouvy@netair.cz
Telefon: +420 488572050