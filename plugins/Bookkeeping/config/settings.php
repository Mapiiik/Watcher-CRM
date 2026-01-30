<?php
/**
 * Application-wide default settings.
 *
 * This file defines baseline configuration values for the application and its plugins.
 * These defaults are loaded by the SettingsService and serve as a fallback when no
 * value is stored in the database.
 *
 * Structure:
 * - Top-level keys represent plugin namespaces (e.g. 'core', 'radius', 'bookkeeping').
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

use Bookkeeping\Provider\Eurofaktura\EurofakturaProvider;
use Bookkeeping\Provider\Pohoda\PohodaProvider;

return [
    'bookkeeping' => [
        'accounting' => [

            // Default provider (pohoda / eurofaktura)
            'default_provider' => 'pohoda',

            // Accounting providers
            'providers' => [

                // -----------------------------------------
                // Stormware POHODA
                // -----------------------------------------
                'pohoda' => [
                    // Class name
                    'class' => PohodaProvider::class,

                    // Issuer metadata
                    'issuer' => [
                        'identity_number' => (string)env('POHODA_COMPANY_ID', '00000000'),
                    ],
                    // API
                    'api' => [
                        'url' => (string)env('POHODA_MSERVER_URL', 'http://localhost:44444'),
                        'timeout' => 3600,
                    ],
                ],

                // -----------------------------------------
                // EUROFAKTURA / E‑RACUNI
                // -----------------------------------------
                'eurofaktura' => [
                    // Class name
                    'class' => EurofakturaProvider::class,

                    // Issuer metadata
                    'issuer' => [
                        'city' => 'Makarska',
                        'business_unit' => 'POSL1',
                        'business_year_format' => 'yyyy',
                    ],

                    // Invoices
                    'invoice' => [
                        'status' => 'Draft', // or 'IssuedInvoice'
                        'send_issued_invoice_by_email' => false,
                    ],

                    // Invoice items
                    'items' => [
                        'default_classification_code' => 'K61.10.00',
                    ],

                    // Documents
                    'document' => [
                        'currency' => 'EUR',
                        'language' => 'Croatian',
                    ],

                    // VAT / Reverse charge
                    'vat' => [
                        'clause' => 'Registered',
                        'transaction_type' => [
                            'standard' => '0',
                            'reverse_charge' => '14',
                        ],
                    ],

                    // Invoice types
                    'invoice_type' => [
                        'standard' => 'Retail',
                        'reverse_charge' => 'Gross',
                    ],

                    // Paymets
                    'payment' => [
                        'method' => 'BankTransfer',
                        'reference_prefix' => '00 ',
                    ],

                    // Customers (sync)
                    'customers' => [
                        'use_buyer_code' => false,
                        'code_prefix' => '',
                    ],

                    // API
                    'api' => [
                        'url' => (string)env('EUROFAKTURA_API_URL', 'https://e-racuni.com/H7i/API'),
                        'timeout' => 3600,
                    ],
                ],
            ],
        ],

        /*
        'invoices' => [
            'rounding_places' => env('BILLING_PERIOD_ROUNDING_PLACES', 2),
            'rounding_type' => env('BILLING_PERIOD_ROUNDING_TYPE', 'HALF_UP'),
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

        'invoices' => [
            'texts' => [
                'default' => 'Faktura za poskytované služby dle smlouvy {contract_number} za období {invoiced_month}',
                'separate' => 'Faktura za službu {service_name} za období {invoiced_month}',
            ],
            'emails' => [
                'subject' => 'NETAIR - {invoice_text} - {invoice_number} - VS: {variable_symbol}',
                'body_text' => <<<TEXT
                    Vážený zákazníku,

                    dne {creation_date} Vám byla vystavena faktura - daňový doklad č. {invoice_number} splatná {due_date}.

                    Variabilní symbol pro platbu: {variable_symbol}
                    Číslo našeho účtu: {bank_account_number}
                    Celková částka (včetně DPH): {total_amount}

                    V příloze Vám zasíláme doklad ve formátu PDF.

                    Tuto i další námi vystavené faktury je možné stahovat i v našem Uživatelském portálu,
                    kde si zároveň můžete zkontrolovat, zda jsou uhrazeny.
                    Pokud si nepřejete dostávat faktury e-mailem, můžete si zde změnit i formu zasílání.

                    Uživatelský portál: {user_portal_url}

                    Tento email byl vygenerován automaticky.

                    {company_name}
                    {company_address_line_1}
                    {company_address_line_2}
                    IČ: {identity_number}, DIČ: {vat_number}
                    TEXT,
            ],
        ],

        'debtors' => [
            'notifications' => [
                'enabled' => true, // global kill‑switch
                'channels' => [
                    'email' => [
                        'enabled' => true,
                    ],
                    'sms' => [
                        'enabled' => true,
                    ],
                ],
                'types' => [
                    'notify' => [
                        'enabled' => true,
                    ],
                    'block' => [
                        'enabled' => true,
                    ],
                    'inactive' => [
                        'enabled' => true,
                    ],
                ],
            ],
            'blocking' => [
                'enabled' => true, // global kill‑switch
                'services' => [
                    'sledovani_tv' => [
                        'enabled' => true,
                    ],
                    'routers' => [
                        'enabled' => true,
                    ],
                ],
            ],
            'tables' => [
                'invoices' => [
                    'headers' => [
                        'number' => 'Číslo faktury',
                        'variable_symbol' => 'Var. symbol',
                        'creation_date' => 'Datum',
                        'due_date' => 'Splatnost',
                        'total' => 'Cena',
                        'debt' => 'Dluh',
                    ],
                    'total_label' => 'Dluh celkem:',
                    'separator' => '-------------------------------------------------------------------------------------------',
                    'footer' => '===========================================================================================',
                ],
            ],
            'emails' => [
                'notify' => [
                    'subject' => 'NETAIR - neuhrazené pohledávky ke dni {date} - VS: {customer_number}',
                    'body_text' => <<<TEXT
                        Vážený zákazníku,

                        rádi bychom Vás upozornili, že k dnešnímu dni evidujeme neuhrazené pohledávky po splatnosti ve výši {total_overdue_debt}, VS: {customer_number}.

                        {invoices_table}

                        Pokud máte vše uhrazeno, kontaktujte nás prosím a sdělte nám datum, variabilní symbol a číslo účtu, ze kterého byly platby provedeny.

                        Pokud se jedná o nedoplatek, je to pravděpodobně způsobeno tím, že jste nedávno byli převedeni na nové tarify, o čemž jsme vás informovali e-mailem.

                        Kontakty na naše účetní oddělení
                        Mail: {company_invoices_email}
                        Telefon: {company_invoices_phone}
                        Číslo účtu: {bank_account_number}

                        Volat můžete od pondělí do pátku mezi 08:00-12:00 a 13:00-16:00.

                        Pokud Vám nepřichází faktury do emailu, zkontrolujte si prosím, zda jste odsouhlasili, že je chcete dostávat.
                        Potřebné souhlasy je možné udělit v sekci Uživatelské údaje, po přihlášení do Uživatelského portálu: {user_portal_url}
                        Pokud nemáte přihlašovací údaje, můžete si je vyžádat zde: https://netair.cz/podpora/zrizeni-pristupu-do-uzivatelskeho-portalu/

                        {company_name}
                        {company_address_line_1}
                        {company_address_line_2}
                        IČ: {identity_number}, DIČ: {vat_number}
                        TEXT,
                ],
                'block' => [
                    'subject' => 'NETAIR - pozastavení služeb - neuhrazené pohledávky ke dni {date} - VS: {customer_number}',
                    'body_text' => <<<TEXT
                        Vážený zákazníku,

                        rádi bychom Vás upozornili, že naše služby byly pozastaveny z důvodu neuhrazené pohledávky po splatnosti ve výši {total_overdue_debt}, VS: {customer_number}.

                        {invoices_table}

                        Pokud máte vše uhrazeno, kontaktujte nás prosím a sdělte nám datum, variabilní symbol a číslo účtu, ze kterého byly platby provedeny.

                        Kontakty na naše účetní oddělení
                        Mail: {company_invoices_email}
                        Telefon: {company_invoices_phone}
                        Číslo účtu: {bank_account_number}

                        Volat můžete od pondělí do pátku mezi 08:00-12:00 a 13:00-16:00.

                        Pokud Vám nepřichází faktury do emailu, zkontrolujte si prosím, zda jste odsouhlasili, že je chcete dostávat.
                        Potřebné souhlasy je možné udělit v sekci Uživatelské údaje, po přihlášení do Uživatelského portálu: {user_portal_url}
                        Pokud nemáte přihlašovací údaje, můžete si je vyžádat zde: https://netair.cz/podpora/zrizeni-pristupu-do-uzivatelskeho-portalu/

                        {company_name}
                        {company_address_line_1}
                        {company_address_line_2}
                        IČ: {identity_number}, DIČ: {vat_number}
                        TEXT,
                ],
                'inactive' => [
                    'subject' => 'NETAIR - neaktivní služby - neuhrazené pohledávky ke dni {date} - VS: {customer_number}',
                    'body_text' => <<<TEXT
                        Vážený zákazníku,

                        rádi bychom Vás upozornili, že k dnešnímu dni stále evidujeme neuhrazené pohledávky po splatnosti ve výši {total_overdue_debt}, VS: {customer_number}.

                        {invoices_table}

                        Pokud máte vše uhrazeno, kontaktujte nás prosím a sdělte nám datum, variabilní symbol a číslo účtu, ze kterého byly platby provedeny.

                        Kontakty na naše účetní oddělení
                        Mail: {company_invoices_email}
                        Telefon: {company_invoices_phone}
                        Číslo účtu: {bank_account_number}

                        Volat můžete od pondělí do pátku mezi 08:00-12:00 a 13:00-16:00.

                        Pokud Vám nepřichází faktury do emailu, zkontrolujte si prosím, zda jste odsouhlasili, že je chcete dostávat.
                        Potřebné souhlasy je možné udělit v sekci Uživatelské údaje, po přihlášení do Uživatelského portálu: {user_portal_url}
                        Pokud nemáte přihlašovací údaje, můžete si je vyžádat zde: https://netair.cz/podpora/zrizeni-pristupu-do-uzivatelskeho-portalu/

                        {company_name}
                        {company_address_line_1}
                        {company_address_line_2}
                        IČ: {identity_number}, DIČ: {vat_number}
                        TEXT,
                ],
            ],
            'sms' => [
                'notify' => [
                    'subject' => 'NETAIR - neuhrazené pohledávky ke dni {date} - VS: {customer_number}',
                    'body' => 'Vážený zákazníku, rádi bychom Vás upozornili, že k dnešnímu dni evidujeme neuhrazené pohledávky po splatnosti ve výši {total_overdue_debt}, VS: {customer_number}. Pokud máte vše uhrazeno, kontaktujte nás prosím (8:00-16:00). {company_name}, tel: {company_invoices_phone}, č.ú.: {bank_account_number}',
                ],
                'block' => [
                    'subject' => 'NETAIR - pozastavení služeb - neuhrazené pohledávky ke dni {date} - VS: {customer_number}',
                    'body' => 'Vážený zákazníku, naše služby byly pozastaveny z důvodu neuhrazené pohledávky po splatnosti ve výši {total_overdue_debt}, VS: {customer_number}. Pokud máte vše uhrazeno, kontaktujte nás prosím (8:00-16:00). {company_name}, tel: {company_invoices_phone}, č.ú.: {bank_account_number}',
                ],
                'inactive' => [
                    'subject' => 'NETAIR - neaktivní služby - neuhrazené pohledávky ke dni {date} - VS: {customer_number}',
                    'body' => 'Vážený zákazníku, rádi bychom Vás upozornili, že k dnešnímu dni stále evidujeme neuhrazené pohledávky po splatnosti ve výši {total_overdue_debt}, VS: {customer_number}. Pokud máte vše uhrazeno, kontaktujte nás prosím (8:00-16:00). {company_name}, tel: {company_invoices_phone}, č.ú.: {bank_account_number}',
                ],
            ],
        ],
    ],
];
