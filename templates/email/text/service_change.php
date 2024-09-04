<?php
use Cake\I18n\Date;

/**
 * @var \Cake\View\View $this
 * @var array<string|int|float> $data
 */

$billing_date = (new Date((string)$data['new_billing_from']))->lastOfMonth();
?>
Vážený zákazníku,

více než 15 let se nám dařilo držet naše ceny připojení k internetu stále stejné, ale bohužel z důvodu současné ekonomické situace, kdy se neustále navyšují náklady na provoz, jsme byli nuceni pro Vás připravit nové tarify.

<?php if (
    ($data['original_billing_sum'] > $data['original_billing_total_price'])
    && ($data['new_billing_sum'] > $data['new_billing_total_price'])
) : ?>
Vaše přípojka měla historicky nastavenou zvýhodněnou sazbu. Proto i nový tarif, bude pro Vás zvýhodněn, oproti standardní ceně.

<?php endif; ?>
Od <?= $data['new_billing_from'] ?> bude na Vaší přípojce č. <?= $data['contract_number'] ?><?= $data['installation_address'] ? ', na adrese ' . $data['installation_address'] : '' ?>


změněn stávající tarif:
<?php if ($data['original_billing_sum'] > $data['original_billing_total_price']) : ?>
    <?= $data['original_billing_name'] ?> za zvýhodněnou cenu <?= $this->Number->currency($data['original_billing_total_price']) ?> měsíčně
<?php else : ?>
    <?= $data['original_billing_name'] ?> za cenu <?= $this->Number->currency($data['original_billing_total_price']) ?> měsíčně
<?php endif; ?>

na nový tarif:
<?php if ($data['new_billing_sum'] > $data['new_billing_total_price']) : ?>
    <?= $data['new_billing_name'] ?> za zvýhodněnou cenu <?= $this->Number->currency($data['new_billing_total_price']) ?> měsíčně (standardní cena tarifu je <?= $this->Number->currency($data['new_billing_sum']) ?>)
<?php else : ?>
    <?= $data['new_billing_name'] ?> za cenu <?= $this->Number->currency($data['new_billing_total_price']) ?> měsíčně
<?php endif; ?>

Jelikož se fakturuje zpětně, první platba za nový tarif by měla proběhnout v období od <?= $billing_date ?> do <?= $billing_date->addDays(10) ?>.

Nový ceník platný od 01.10.2023 je uveřejněný na našich internetových stránkách zde: https://netair.cz/cenik-pripojeni/

V souladu s ust. § 63 odst. 6 zákona č. 127/2005 Sb. o elektronických komunikacích Vás také informujeme, že jestliže tuto změnu neakceptujete, jste oprávněn smlouvu s naší společností bez sankce vypovědět k datu nabytí účinnosti této změny.

Pokud budete mít jakékoliv dotazy nebo máte zájem o jiný tarif, než který navrhujeme, neváhejte nás kontaktovat.

NETAIR, s.r.o.
512 43 Jablonec nad Jizerou, č.p. 299
Mail: smlouvy@netair.cz
Telefon: +420 488 572 512