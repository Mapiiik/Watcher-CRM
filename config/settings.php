<?php
/**
 * Application-wide default settings.
 *
 * This file defines baseline configuration values for the application and its plugins.
 * These defaults are loaded by the SettingsService and serve as a fallback when no
 * value is stored in the database.
 *
 * Structure:
 * - Top-level keys represent plugin namespaces (e.g. 'core', 'radius', 'bookkeeping_pohoda').
 * - Each plugin contains one or more logical blocks (keys), which may contain nested values.
 * - Values can be scalars or arrays, and may use env() to allow environment-specific overrides.
 *
 * Example:
 * [
 *     'core' => [
 *         'company' => [
 *             'name' => env('APP_COMPANY', 'NETAIR s.r.o.'),
 *             'timezone' => 'Europe/Prague',
 *         ],
 *     ],
 *     'radius' => [
 *         'defaults' => [
 *             'secret' => env('RADIUS_SECRET', ''),
 *         ],
 *     ],
 * ]
 *
 * Notes:
 * - Secrets and credentials should remain in environment variables and not be stored in the database.
 * - This file is versioned and acts as a declarative source of truth for default values.
 * - Values defined here can be overridden via the SettingsService::set() method and stored in DB.
 */

return [
    'core' => [
        'company' => [
            'name' => 'NETAIR, s.r.o.',
            'address' => '512 43 Jablonec nad Jizerou, č.p. 299',
            'ic' => '27496139',
            'dic' => 'CZ27496139',
            'phone' => '+420 488 572 050',
            'mobile' => '+420 604 553 444',
            'email' => 'mail@netair.cz',
            'executive' => 'Marko Jujnović, jednatel',
            'price_list_url' => 'https://netair.cz/cenik-pripojeni/',
            'contracts' => [
                'phone' => '+420 488 572 512',
                'mobile' => '+420 604 553 444',
                'email' => 'smlouvy@netair.cz',
            ],
        ],
        /*
        'options' => [
            'app_name' => env('APP_COMPANY', 'ISP')
            'timezone' => env('APP_DEFAULT_TIMEZONE', 'UTC'), # Europe/Prague
            'locale' => env('APP_DEFAULT_LOCALE', 'en_US'), # cs_CZ
            'currency' => env('APP_DEFAULT_CURRENCY', null), # CZK
            'phone_region' => env('APP_DEFAULT_PHONE_REGION', null), # CZ
            'date_format' => env('APP_DATE_FORMAT', null), # dd.MM.yyyy
            'time_format' => env('APP_TIME_FORMAT', null), # dd.MM.yyyy HH:mm:ss
        ],
        */
        /*
        'features' => [
            'enable_select2' => env('ENABLE_SELECT2', true),
            'strip_phone_prefix_for_summary_text' => env('STRIP_PHONE_PREFIX_FOR_SUMMARY_TEXT', false),
        ],
        */
        'documents' => [
            'locale' => 'cs_CZ',
            'gdpr' => [
                'title' => 'SOUHLAS',
                'subtitle' => 'se zpracováním osobních údajů',

                'labels' => [
                    'new_or_change' => 'nový / změna:',
                    'agreement_number' => 'číslo souhlasu:',
                    'agreement_duration' => 'doba trvání souhlasu:',
                    'new' => 'nový',
                    'change' => 'změna',
                    'duration_indefinite' => 'na dobu neurčitou',
                ],

                'between' => 'mezi',
                'and' => 'a',

                'controller_label' => 'Správcem:',
                'controller_company' => 'NETAIR, s.r.o.',
                'controller_address_line1' => 'Jablonec nad Jizerou 299',
                'controller_address_line2' => '512 43 Jablonec nad Jizerou',
                'controller_ic' => '27496139',
                'controller_dic' => 'CZ27496139',
                'controller_phone' => '+420 488 572 050',
                'controller_mobile' => '+420 604 553 444',
                'controller_email' => 'mail@netair.cz',
                'controller_executive' => 'zastoupeným Marko Jujnovićem, jednatelem',
                'controller_registry' => 'zapsaným v obchodním rejstříku vedeném u Krajského soudu v Hradci Králové, oddíl C, vložka 22450.',

                'user_label' => 'Uživatelem:',
                'user_types' => [
                    'non_business' => 'fyzická osoba nepodnikající',
                    'business' => 'fyzická osoba podnikající',
                    'legal' => 'právnická osoba',
                ],

                'personal_data_label' => 'Osobní údaje:',
                'business_data_label' => 'Obchodní údaje:',

                'fields' => [
                    'name' => 'jméno:',
                    'company' => 'firma:',
                    'birth_date' => 'datum narození:',
                    'ic' => 'IČ:',
                    'identity_card' => 'číslo OP:',
                    'dic' => 'DIČ:',
                    'phone' => 'tel:',
                    'mobile' => 'mobil:',
                    'email' => 'e-mail:',
                ],

                'declaration' => 'Prohlášení Správce:

Správce prohlašuje, že bude zpracovávat osobní údaje v rozsahu nezbytném pro naplnění níže stanovených účelů, plnění smlouvy, plnění zákonných povinností a ochrany oprávněných zájmů. Zaměstnanci Správce nebo jiné fyzické osoby, které zpracovávají osobní údaje na základě smlouvy se Správcem a další osoby jsou povinni zachovávat mlčenlivost o osobních údajích, a to i po skončení pracovního poměru nebo prací.

Já, níže podepsaný:

1. Uděluji tímto souhlas se zpracováním osobních údajů Správcem, pro účely stanovené níže. Tento souhlas uděluji pro následující údaje:
Jméno, příjmení, emailová adresa, telefonní číslo, adresa trvalého pobytu, adresa místa připojení, fakturační adresa, korespondenční adresa, datum narození, IP adresa, typ a objem poskytnutých služeb, daňové a účetní doklady

2. Tento souhlas uděluji na dobu neurčitou a můžu ho kdykoli vzít zpět, a to stejným způsobem, jakým jsem jej udělil nebo pomocí Uživatelského portálu Správce.

3. Zpracování osobních údajů je prováděno Správcem.

4. Beru na vědomí, že podle zákona o ochraně osobních údajů mám právo:
    a) vzít souhlas kdykoliv zpět
    b) požadovat po Správci informaci, jaké moje osobní údaje zpracovává
    c) požadovat po Správci vysvětlení ohledně zpracování osobních údajů
    d) vyžádat si u Správce přístup k těmto údajům a tyto nechat aktualizovat nebo opravit
    e) požadovat po Správci výmaz těchto osobních údajů, pokud Správce neprokáže oprávněné důvody pro zpracování těchto osobních údajů
    f) v případě pochybností o dodržování povinností souvisejících se zpracováním osobních údajů obrátit se na Správce nebo na Úřad pro ochranu osobních údajů',

                'checkboxes' => [
                    'billing' => '▢ souhlasím se zasíláním veškeré korespondence spojené s měsíčním vyúčtováním *',
                    'outages' => '▢ souhlasím se zasíláním informací o odstávkách a poruchách *',
                    'marketing' => '▢ souhlasím se zasíláním obchodních sdělení *',
                    'note' => '* zaškrtněte prosím jaké typy zpráv chcete dostávat',
                ],

                'signature' => [
                    'date' => 'Datum podpisu: ____________________',
                    'line' => '......................................................',
                    'user' => 'Uživatel',
                ],
            ],
        ],
        'emails' => [
            /*
            'service_change' => [
                'subject' => __d(
                    'messages',
                    'Service change notification from {new_billing_from} on your connection no. {contract_number}',
                ),
                'greeting' => __d(
                    'messages',
                    'Dear customer,',
                ),
                'intro' => __d(
                    'messages',
                    'From {new_billing_from} your connection no. {contract_number}{installation_address_info} will be',
                ),
                'installation_address_info' => __d(
                    'messages',
                    ', at the address {installation_address}',
                ),
                'current_tariff_label' => __d(
                    'messages',
                    'changed current tariff:',
                ),
                'current_tariff_discounted' => __d(
                    'messages',
                    '{original_billing_name} at a discounted price {original_billing_total_price} per month',
                ),
                'current_tariff' => __d(
                    'messages',
                    '{original_billing_name} at a price {original_billing_total_price} per month',
                ),
                'new_tariff_label' => __d(
                    'messages',
                    'to new tariff:',
                ),
                'new_tariff_discounted' => __d(
                    'messages',
                    '{new_billing_name} at a discounted price {new_billing_total_price} per month (standard price {new_billing_sum})',
                ),
                'new_tariff' => __d(
                    'messages',
                    '{new_billing_name} at a price {new_billing_total_price} per month',
                ),
                'historical_discount' => __d(
                    'messages',
                    'Your connection had a historical discount, therefore the new tariff will also be discounted.',
                ),
                'no_change' => __d(
                    'messages',
                    'From the billing perspective, nothing changes for you.',
                ),
                'first_payment' => __d(
                    'messages',
                    'Since billing is retrospective, the first payment for the new tariff should occur between {billing_date} and {billing_date_plus_10}.',
                ),
                'price_list' => __d(
                    'messages',
                    'The current price list is published on our website here: {price_list_url}',
                ),
                'legislative_information' => __d(
                    'messages',
                    'In accordance with §63(6) of Act No. 127/2005 Coll. on electronic communications, if you do not accept this change, you are entitled to terminate the contract without penalty at the effective date of this change.',
                ),
                'closing' => __d(
                    'messages',
                    'If you have any questions or wish to choose another tariff, please contact us.',
                ),
            ],
            */
            'service_change' => [
                'subject' => 'NETAIR - změna služeb od {new_billing_from} na Vaší přípojce č. {contract_number}',

                'greeting' => 'Vážený zákazníku,',

                'intro' => 'od {new_billing_from} bude na Vaší přípojce č. {contract_number}{installation_address_info}',
                'installation_address_info' => ', na adrese {installation_address}',

                'current_tariff_label' => 'změněn stávající tarif:',
                'current_tariff_discounted' => '{original_billing_name} za zvýhodněnou cenu {original_billing_total_price} měsíčně',
                'current_tariff' => '{original_billing_name} za cenu {original_billing_total_price} měsíčně',

                'new_tariff_label' => 'na nový tarif:',
                'new_tariff_discounted' => '{new_billing_name} za zvýhodněnou cenu {new_billing_total_price} měsíčně (standardní cena tarifu je {new_billing_sum})',
                'new_tariff' => '{new_billing_name} za cenu {new_billing_total_price} měsíčně',

                'historical_discount' => 'Vaše přípojka měla historicky nastavenou zvýhodněnou sazbu. Proto i nový tarif, bude pro Vás zvýhodněn, oproti standardní ceně.',

                'no_change' => 'Z hlediska fakturace se pro Vás tímto nic nemění.',
                'first_payment' => 'Jelikož se fakturuje zpětně, první platba za nový tarif by měla proběhnout v období od {billing_date} do {billing_date_plus_10}.',

                'price_list' => 'Aktuálně platný ceník je uveřejněný na našich internetových stránkách zde: {price_list_url}',

                'legislative_information' => 'V souladu s ust. § 63 odst. 6 zákona č. 127/2005 Sb. o elektronických komunikacích Vás také informujeme, že jestliže tuto změnu neakceptujete, jste oprávněn smlouvu s naší společností bez sankce vypovědět k datu nabytí účinnosti této změny.',

                'closing' => 'Pokud budete mít jakékoliv dotazy nebo máte zájem o jiný tarif, než který navrhujeme, neváhejte nás kontaktovat.',
            ],
        ],
    ],

    /*
    'radius' => [
        'defaults' => [
            'secret' => env('RADIUS_SECRET', ''),
            'default_user_group' => env('RADIUS_DEFAULT_USER_GROUP', 'default'),
        ],
    ],
    */

    /*
    'bookkeeping_pohoda' => [
        'connection' => [
            'username' => env('POHODA_USERNAME', ''),
            'password' => env('POHODA_PASSWORD', ''),
            'company_id' => env('POHODA_COMPANY_ID', '00000000'),
            'mserver_url' => env('POHODA_MSERVER_URL', 'http://localhost:44444'),
        ],
        'invoices' => [
            'rounding_places' => env('BILLING_PERIOD_ROUNDING_PLACES', 2),
            'rounding_type' => env('BILLING_PERIOD_ROUNDING_TYPE', 'HALF_UP'),
        ],
    ],
    */

    /*
    'debtors' => [
        'address_list' => env('DEBTORS_ADDRESS_LIST', 'DODGERS'),
        'allowed_payment_delay' => env('DEBTORS_ALLOWED_PAYMENT_DELAY', 0),
        'allowed_total_overdue_debt' => env('DEBTORS_ALLOWED_TOTAL_OVERDUE_DEBT', 0),
        'blocked_label_id' => env('DEBTORS_BLOCKED_LABEL_ID', null),
        'routers' => [
            'ip_addresses' => env('DEBTORS_ROUTERS_IP_ADDRESSES', ''),
            'username' => env('DEBTORS_ROUTERS_USERNAME', ''),
            'password' => env('DEBTORS_ROUTERS_PASSWORD', ''),
        ],
        'sledovani_tv' => [
            'username' => env('DEBTORS_SLEDOVANITV_USERNAME', ''),
            'password' => env('DEBTORS_SLEDOVANITV_PASSWORD', ''),
        ],
    ],
    */

    /*
    'routeros' => [
        'credentials' => [
            'username' => env('ROUTEROS_USERNAME', ''),
            'password' => env('ROUTEROS_PASSWORD', ''),
        ],
    ],
    */

    /*
    'android_sms_gateway' => [
        'connection' => [
            'login' => env('ANDROID_SMS_GATEWAY_LOGIN', ''),
            'password' => env('ANDROID_SMS_GATEWAY_PASSWORD', ''),
            'passphrase' => env('ANDROID_SMS_GATEWAY_PASSPHRASE', ''),
            'url' => env('ANDROID_SMS_GATEWAY_URL', ''),
        ],
    ],
    */
];
