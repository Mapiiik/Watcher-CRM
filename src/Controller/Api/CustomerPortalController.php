<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\CustomerPortal\AccessLinks;
use App\CustomerPortal\Lookups;
use App\CustomerPortal\Passwords;
use App\CustomerPortal\Throttle;
use App\CustomerPortal\Tokens;
use App\Model\Entity\Billing;
use App\Model\Entity\Contract;
use App\Model\Entity\Customer;
use App\Model\Entity\IpAddress;
use App\Model\Entity\IpNetwork;
use App\Model\Entity\Login;
use App\Model\Enum\AddressType;
use App\Model\Enum\IpAddressTypeOfUse;
use App\Model\Enum\IpNetworkTypeOfUse;
use App\Model\Table\LoginsTable;
use BackedEnum;
use Bookkeeping\Model\Entity\Invoice;
use Bookkeeping\Service\BookkeepingService;
use Cake\Chronos\ChronosDate;
use Cake\Core\Configure;
use Cake\Datasource\EntityInterface;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\HttpException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Exception\UnauthorizedException;
use Cake\Http\Response;
use Cake\I18n\Date;
use Cake\I18n\DateTime;
use Cake\Log\Log;
use Cake\ORM\Query\SelectQuery;
use DateTimeInterface;
use Settings\Utility\Settings;
use Stringable;

/**
 * What a customer portal may ask about the customers signed in to it.
 *
 * The portal itself signs in as a user with the `customer-portal` role, with a Bearer token.
 * A customer signs in through `login`, and the token that returns, sent back as
 * `X-Customer-Token`, limits every other answer to that one customer. Without it the portal can
 * read nothing but the `identify` answer.
 */
class CustomerPortalController extends AppController
{
    public const TOKEN_HEADER = 'X-Customer-Token';

    /**
     * Signs a customer in with the login and password they typed.
     *
     * Answers `granted` with a customer token, or `denied`, `locked` or `blocked` (too many failed
     * attempts, the password is then not looked at).
     *
     * @return void
     */
    public function login(): void
    {
        $this->getRequest()->allowMethod(['post']);
        $login = trim((string)$this->getRequest()->getData('login'));
        $password = (string)$this->getRequest()->getData('password');
        $ip = (string)$this->getRequest()->getData('ip');
        if ($login === '' || $password === '' || filter_var($ip, FILTER_VALIDATE_IP) === false) {
            throw new BadRequestException('A login, a password and the customer\'s ip address are required.');
        }

        $throttle = Throttle::fromSettings();
        if ($throttle->blocked($login, $ip)) {
            Log::notice(sprintf('Customer portal: sign-in of "%s" from %s refused, too many attempts', $login, $ip));
            $this->answer(['status' => 'blocked']);

            return;
        }

        $logins = $this->fetchTable('Logins');
        $record = $logins->find()
            ->contain(['Customers'])
            ->where(['Logins.login' => $login])
            ->first();
        $stored = $record?->get('password');
        $verified = $record instanceof Login && is_string($stored)
            ? Passwords::verify($password, $stored)
            : Passwords::verifyUnknown($password);

        if (!$verified) {
            $throttle->failed($login, $ip);
            if ($record instanceof Login) {
                $logins->updateAll(['last_denied' => DateTime::now(), 'last_denied_ip' => $ip], ['id' => $record->id]);
            }
            Log::notice(sprintf('Customer portal: sign-in of "%s" from %s denied', $login, $ip));
            $this->answer(['status' => 'denied']);

            return;
        }

        $throttle->succeeded($login, $ip);
        $changes = ['last_granted' => DateTime::now(), 'last_granted_ip' => $ip];
        if (Passwords::needsRehash($stored)) {
            $changes['password'] = Passwords::hash($password);
        }
        $logins->updateAll($changes, ['id' => $record->id]);

        if ($record->locked) {
            $this->answer(['status' => 'locked']);

            return;
        }

        $token = Tokens::issue(
            $record->id,
            $record->customer_id,
            (int)Settings::get(Throttle::SETTINGS_PATH . '.token_hours', 8),
        );
        $this->answer([
            'status' => 'granted',
            'token' => $token['token'],
            'expires' => $token['expires'],
            'login' => [
                'id' => $record->id,
                'login' => $record->login,
                'rights' => $this->scalar($record->rights),
            ],
            'customer' => $this->customerSummary($record->customer),
            'password_min_length' => self::passwordMinLength(),
        ]);
    }

    /**
     * The signed-in customer's details, contacts, addresses and contracts with active services,
     * with the countries an address may be in.
     *
     * @return void
     */
    public function customer(): void
    {
        $this->getRequest()->allowMethod(['get']);
        $customer = $this->fetchTable('Customers')->get($this->signedIn()->customer_id, contain: [
            'Emails' => ['sort' => ['Emails.email' => 'ASC']],
            'Phones' => ['sort' => ['Phones.phone' => 'ASC']],
            'Addresses' => ['sort' => ['Addresses.type' => 'ASC']],
        ]);
        assert($customer instanceof Customer);

        $details = $this->customerSummary($customer) + $this->fields($customer, [
            'title', 'first_name', 'last_name', 'suffix', 'company', 'date_of_birth', 'identity_card_number',
            'identity_number', 'vat_number', 'www', 'bank_name', 'bank_account', 'bank_code',
            'invoice_delivery_type', 'agree_gdpr', 'agree_mailing_billing', 'agree_mailing_outages',
            'agree_mailing_commercial',
        ]);
        $details['emails'] = array_map(fn(EntityInterface $email): array => $this->fields($email, [
            'id', 'email', 'use_for_billing', 'use_for_outages', 'use_for_commercial',
        ]), $customer->emails);
        $details['phones'] = array_map(fn(EntityInterface $phone): array => $this->fields($phone, [
            'id', 'phone', 'use_for_billing', 'use_for_outages', 'use_for_commercial',
        ]), $customer->phones);
        $details['addresses'] = array_map(fn(EntityInterface $address): array => $this->fields($address, [
            'id', 'type', 'title', 'first_name', 'last_name', 'suffix', 'company', 'street', 'number', 'city',
            'zip', 'country_id',
        ]), $customer->addresses);
        $details['contracts'] = $this->contractList($customer->id);

        $countries = $this->fetchTable('Countries')->find('list')->orderBy(['Countries.name' => 'ASC'])->toArray();

        $this->answer(['customer' => $details, 'countries' => $countries]);
    }

    /**
     * Saves the consents the signed-in customer gave or withdrew, and labels a given GDPR consent.
     *
     * @return void
     */
    public function consents(): void
    {
        $this->getRequest()->allowMethod(['post']);
        $customerId = $this->signedIn()->customer_id;
        $fields = ['agree_gdpr', 'agree_mailing_billing', 'agree_mailing_outages', 'agree_mailing_commercial'];
        $data = [];
        foreach ($fields as $field) {
            $value = $this->getRequest()->getData($field);
            if ($value !== null) {
                $data[$field] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
            }
        }

        $customers = $this->fetchTable('Customers');
        $customer = $customers->get($customerId);
        $customers->patchEntity($customer, $data, ['fields' => $fields, 'validate' => false]);
        // only the consents change, the rest of the record is not the customer's to answer for
        $customers->saveOrFail($customer, ['checkRules' => false]);

        if ($customer->get('agree_gdpr')) {
            $this->labelConsent($customerId);
        }

        $this->answer(['status' => 'saved']);
    }

    /**
     * Sets a new password for the signed-in login.
     *
     * A password shorter than the settings allow is answered `too_short` with 422 and the length.
     *
     * @return void
     */
    public function password(): void
    {
        $this->getRequest()->allowMethod(['post']);
        $password = (string)$this->getRequest()->getData('password');
        if ($password === '') {
            throw new BadRequestException('A password is required.');
        }
        $minLength = self::passwordMinLength();
        if (mb_strlen($password) < $minLength) {
            $this->setResponse($this->getResponse()->withStatus(422));
            $this->answer(['status' => 'too_short', 'min_length' => $minLength]);

            return;
        }

        $logins = $this->fetchTable('Logins');
        $login = $logins->get($this->signedIn()->id);
        $login->set('password', $password);
        $logins->saveOrFail($login);

        $this->answer(['status' => 'saved']);
    }

    /**
     * A customer who forgot their password, or has no login yet, asks for a link to set one, by
     * their customer number and an e-mail they have.
     *
     * Answers `found` with the link's token for the portal to send to that e-mail, `locked` when
     * every login of the customer is locked (the portal says so in the e-mail instead), `unknown`
     * when the two do not belong together, or `blocked` after too many requests. What the portal
     * shows the customer must not tell these apart.
     *
     * @return void
     */
    public function accessRequest(): void
    {
        $this->getRequest()->allowMethod(['post']);
        $number = trim((string)$this->getRequest()->getData('customer_number'));
        $email = trim((string)$this->getRequest()->getData('email'));
        $ip = (string)$this->getRequest()->getData('ip');
        if ($number === '' || $email === '' || filter_var($ip, FILTER_VALIDATE_IP) === false) {
            throw new BadRequestException('A customer number, an e-mail and the customer\'s ip address are required.');
        }

        $perCustomer = Throttle::setting('access_requests_per_customer', 3);
        $throttle = new Throttle($perCustomer, Throttle::setting('access_requests_per_address', 10), $perCustomer, 60);
        // every request counts, as each sends an e-mail
        $key = 'access-request|' . $number;
        if ($throttle->blocked($key, $ip)) {
            Log::notice(sprintf('Customer portal: access request for %s from %s refused, too many', $number, $ip));
            $this->answer(['status' => 'blocked']);

            return;
        }
        $throttle->failed($key, $ip);

        $customer = ctype_digit($number) ? $this->fetchTable('Customers')->find()
            ->where(['Customers.nid' => (int)$number - (int)Configure::read('Customers.series')])
            ->first() : null;
        $links = new AccessLinks();
        if (!$customer instanceof Customer || !$links->isEmailOf($customer->id, $email)) {
            Log::notice(sprintf('Customer portal: access request for %s from %s, no such pair', $number, $ip));
            $this->answer(['status' => 'unknown']);

            return;
        }

        $logins = $this->fetchTable('Logins')->find()->where(['customer_id' => $customer->id])->all();
        if (!$logins->isEmpty() && $logins->every(fn(Login $login): bool => (bool)$login->locked)) {
            Log::notice(sprintf('Customer portal: access request for %s from %s, every login locked', $number, $ip));
            $this->answer(['status' => 'locked', 'email' => $email, 'customer' => $this->customerSummary($customer)]);

            return;
        }

        $link = $links->issue($customer->id, $email);
        Log::notice(sprintf('Customer portal: access link for %s sent from %s', $number, $ip));
        $this->answer([
            'status' => 'found',
            'token' => $link['token'],
            'expires' => $link['expires'],
            'email' => $email,
            'customer' => $this->customerSummary($customer),
        ]);
    }

    /**
     * What a link from `accessRequest` lets the customer do: set the password of one of their
     * logins that is not locked, or get the login offered where they have none.
     *
     * Answers `valid` with the logins or the new login, or `invalid` for a link that is not one,
     * has expired or has been used.
     *
     * @return void
     */
    public function access(): void
    {
        $this->getRequest()->allowMethod(['get']);
        $link = $this->accessLink((string)$this->getRequest()->getQuery('token'));
        if ($link === null) {
            $this->answer(['status' => 'invalid']);

            return;
        }

        [$customer, $email] = $link;
        $logins = $this->openLogins($customer->id);
        $this->answer([
            'status' => 'valid',
            'email' => $email,
            'customer' => $this->customerSummary($customer),
            'logins' => array_map(fn(Login $login): array => ['id' => $login->id, 'login' => $login->login], $logins),
            'new_login' => $logins === [] ? $this->fetchTable(LoginsTable::class)->suggestLogin($customer) : null,
            'password_min_length' => self::passwordMinLength(),
        ]);
    }

    /**
     * Sets the password a link from `accessRequest` was sent for: of the login chosen, the only
     * one, or of a new login where the customer has none. The link is spent by it.
     *
     * Answers `saved` with the login to sign in with, `too_short` with 422 and the length, or
     * `invalid` with 404 for a link that no longer works or a login that is not the customer's.
     *
     * @return void
     */
    public function setAccess(): void
    {
        $this->getRequest()->allowMethod(['post']);
        $password = (string)$this->getRequest()->getData('password');
        $ip = (string)$this->getRequest()->getData('ip');
        if ($password === '' || filter_var($ip, FILTER_VALIDATE_IP) === false) {
            throw new BadRequestException('A password and the customer\'s ip address are required.');
        }
        $minLength = self::passwordMinLength();
        if (mb_strlen($password) < $minLength) {
            $this->setResponse($this->getResponse()->withStatus(422));
            $this->answer(['status' => 'too_short', 'min_length' => $minLength]);

            return;
        }

        $customer = $this->accessLink((string)$this->getRequest()->getData('token'))[0] ?? null;
        $logins = $this->fetchTable(LoginsTable::class);
        $open = $customer === null ? [] : $this->openLogins($customer->id);
        $chosen = (string)$this->getRequest()->getData('login_id');
        $login = match (true) {
            $customer === null => null,
            $open === [] => $logins->newEntity(
                ['login' => $logins->suggestLogin($customer), 'customer_id' => $customer->id],
                ['validate' => false, 'accessibleFields' => ['login' => true, 'customer_id' => true]],
            ),
            count($open) === 1 && $chosen === '' => $open[0],
            default => array_values(array_filter($open, fn(Login $login): bool => $login->id === $chosen))[0] ?? null,
        };
        if (!$customer instanceof Customer || !$login instanceof Login) {
            $this->setResponse($this->getResponse()->withStatus(404));
            $this->answer(['status' => 'invalid']);

            return;
        }

        $made = $login->isNew();
        $login->set('password', $password);
        $logins->saveOrFail($login);
        Log::notice(sprintf(
            'Customer portal: %s of "%s" for customer %s set from %s with an access link',
            $made ? 'new login' : 'password',
            $login->login,
            $customer->number,
            $ip,
        ));

        $this->answer(['status' => 'saved', 'login' => $login->login]);
    }

    /**
     * The signed-in customer's invoices, newest first.
     *
     * @return void
     */
    public function invoices(): void
    {
        $this->getRequest()->allowMethod(['get']);
        $invoices = $this->fetchTable('Bookkeeping.Invoices')->find()
            ->where(['Invoices.customer_id' => $this->signedIn()->customer_id])
            ->orderBy(['Invoices.creation_date' => 'DESC', 'Invoices.number' => 'DESC'])
            ->all()
            ->map(fn(EntityInterface $invoice): array => $this->fields($invoice, [
                'id', 'number', 'variable_symbol', 'creation_date', 'due_date', 'payment_date', 'text', 'total',
                'debt',
            ]))
            ->toList();

        $this->answer(['invoices' => $invoices]);
    }

    /**
     * The PDF of one of the signed-in customer's invoices.
     *
     * @param string $id Invoice id.
     * @return \Cake\Http\Response
     */
    public function invoiceDownload(string $id): Response
    {
        $this->getRequest()->allowMethod(['get']);
        $invoice = $this->fetchTable('Bookkeeping.Invoices')->find()
            ->contain(['Customers'])
            ->where(['Invoices.id' => $id, 'Invoices.customer_id' => $this->signedIn()->customer_id])
            ->first();
        if (!$invoice instanceof Invoice) {
            throw new NotFoundException('No such invoice of this customer.');
        }
        $filePath = (new BookkeepingService())->getInvoicePdfPath($invoice);

        return $this->getResponse()->withFile($filePath, ['download' => true, 'name' => basename($filePath)]);
    }

    /**
     * Who uses an ip address, for services that recognise a customer by where they come from,
     * with only what such a service passes on. Answers `customer` null when nobody does.
     *
     * Every lookup is logged, and a caller asking for more than the settings allow in a minute is
     * refused, so that going through whole ranges shows and stays slow.
     *
     * @return void
     * @throws \Cake\Http\Exception\HttpException With 429 over the limit.
     */
    public function identify(): void
    {
        $this->getRequest()->allowMethod(['get']);
        $ip = (string)$this->getRequest()->getQuery('ip');
        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            throw new BadRequestException('An ip address is required.');
        }

        $caller = (string)($this->getRequest()->getAttribute('identity')['username'] ?? 'unknown');
        $lookups = Lookups::fromSettings();
        if (!$lookups->allow($caller)) {
            Log::warning(sprintf(
                'Customer portal: %s asked who uses %s, refused over %d lookups a minute',
                $caller,
                $ip,
                $lookups->perMinute(),
            ));

            throw new HttpException('Too many lookups, try again in a minute.', 429);
        }

        $ipAddress = $this->fetchTable('IpAddresses')->find()
            ->contain(['Customers' => [
                'Emails' => ['sort' => ['Emails.email' => 'ASC']],
                'Addresses' => ['conditions' => ['Addresses.type' => AddressType::Installation->value]],
            ]])
            ->where(['IpAddresses.ip_address' => $ip])
            ->orderBy(['Customers.company' => 'ASC', 'Customers.last_name' => 'ASC', 'Customers.first_name' => 'ASC'])
            ->first();
        $customer = $ipAddress?->get('customer');
        Log::info(sprintf(
            'Customer portal: %s asked who uses %s: %s',
            $caller,
            $ip,
            $customer instanceof Customer ? 'customer ' . $customer->number : 'nobody',
        ));
        if (!$customer instanceof Customer) {
            $this->answer(['customer' => null]);

            return;
        }

        $address = $customer->addresses[0] ?? null;
        $this->answer(['customer' => ['number' => $customer->number] + $this->fields($customer, [
            'title', 'first_name', 'last_name', 'suffix', 'company',
        ]) + [
            'email' => $customer->emails[0]->email ?? null,
            'address' => $address === null ? null : $this->fields($address, ['street', 'number', 'city', 'zip']),
        ]]);
    }

    /**
     * The login the customer token in the request names, still able to sign in.
     *
     * @return \App\Model\Entity\Login
     * @throws \Cake\Http\Exception\UnauthorizedException When there is no such token or it no longer holds.
     */
    private function signedIn(): Login
    {
        $token = Tokens::read($this->getRequest()->getHeaderLine(self::TOKEN_HEADER));
        $login = $token === null ? null : $this->fetchTable('Logins')->find()
            ->where(['Logins.id' => $token['login_id'], 'Logins.customer_id' => $token['customer_id']])
            ->first();
        if (!$login instanceof Login || $login->locked) {
            throw new UnauthorizedException('The customer token is missing, expired or no longer valid.');
        }

        return $login;
    }

    /**
     * The customer an access link is for, and the e-mail it was sent to, while it works.
     *
     * @param string $token The token of the link.
     * @return array{0: \App\Model\Entity\Customer, 1: string}|null
     */
    private function accessLink(string $token): ?array
    {
        $link = $token === '' ? null : (new AccessLinks())->read($token);
        $customer = $link === null ? null : $this->fetchTable('Customers')->find()
            ->where(['Customers.id' => $link['customer_id']])
            ->first();

        return $link !== null && $customer instanceof Customer ? [$customer, $link['email']] : null;
    }

    /**
     * The customer's logins an access link may set a password for: those not locked.
     *
     * @param string $customerId The customer.
     * @return list<\App\Model\Entity\Login>
     */
    private function openLogins(string $customerId): array
    {
        /** @var list<\App\Model\Entity\Login> */
        return $this->fetchTable('Logins')->find()
            ->where(['customer_id' => $customerId, 'locked' => false])
            ->orderBy(['login' => 'ASC'])
            ->all()
            ->toList();
    }

    /**
     * The shortest password a customer may set.
     *
     * @return int
     */
    private static function passwordMinLength(): int
    {
        return Throttle::setting('password_min_length', 8);
    }

    /**
     * Who a customer is, in the few words every answer about them starts with.
     *
     * @param \App\Model\Entity\Customer $customer The customer.
     * @return array<string, mixed>
     */
    private function customerSummary(Customer $customer): array
    {
        return ['id' => $customer->id, 'number' => $customer->number, 'name' => $customer->name];
    }

    /**
     * The customer's contracts with active services: where each is installed, its subscriber
     * verification code, the services billed on it today, and its ip addresses and networks
     * without those of the operator's technology.
     *
     * @param string $customerId The customer.
     * @return list<array<string, mixed>>
     */
    private function contractList(string $customerId): array
    {
        $today = Date::now();
        $contracts = $this->fetchTable('Contracts')->find('withActiveServices')
            ->contain([
                'ServiceTypes',
                'InstallationAddresses',
                'Billings' => fn(SelectQuery $query): SelectQuery => $query
                    ->contain(['Services'])
                    ->where([
                        'Billings.billing_from <=' => $today,
                        'OR' => ['Billings.billing_until IS' => null, 'Billings.billing_until >=' => $today],
                    ])
                    ->orderBy(['Billings.billing_from' => 'ASC']),
                'IpAddresses' => fn(SelectQuery $query): SelectQuery => $query
                    ->where(['IpAddresses.type_of_use IN' => IpAddressTypeOfUse::customerCases()])
                    ->orderBy(['IpAddresses.ip_address' => 'ASC']),
                'IpNetworks' => fn(SelectQuery $query): SelectQuery => $query
                    ->where(['IpNetworks.type_of_use IN' => IpNetworkTypeOfUse::customerCases()])
                    ->orderBy(['IpNetworks.ip_network' => 'ASC']),
            ])
            ->where(['Contracts.customer_id' => $customerId])
            ->orderBy(['Contracts.number' => 'ASC'])
            ->all();

        $list = [];
        foreach ($contracts as $contract) {
            assert($contract instanceof Contract);
            $address = $contract->installation_address;
            $list[] = [
                'number' => $contract->number,
                'service_type' => $contract->service_type?->name,
                'subscriber_verification_code' => $contract->subscriber_verification_code,
                'installation_address' => $address === null ? null : $this->fields($address, [
                    'id', 'type', 'title', 'first_name', 'last_name', 'suffix', 'company', 'street', 'number',
                    'city', 'zip', 'country_id',
                ]),
                'services' => array_map(fn(Billing $billing): string => $billing->name, $contract->billings),
                'ip_addresses' => array_map(
                    fn(IpAddress $ipAddress): string => (string)$ipAddress->ip_address,
                    $contract->ip_addresses,
                ),
                'ip_networks' => array_map(
                    fn(IpNetwork $ipNetwork): string => (string)$ipNetwork->ip_network,
                    $contract->ip_networks,
                ),
            ];
        }

        return $list;
    }

    /**
     * Named fields of an entity as plain values.
     *
     * @param \Cake\Datasource\EntityInterface $entity The entity.
     * @param list<string> $fields The fields.
     * @return array<string, mixed>
     */
    private function fields(EntityInterface $entity, array $fields): array
    {
        $values = [];
        foreach ($fields as $field) {
            $values[$field] = $this->scalar($entity->get($field));
        }

        return $values;
    }

    /**
     * A value as JSON carries it: enums by their value, dates as Y-m-d, numbers as written.
     *
     * @param mixed $value The value.
     * @return mixed
     */
    private function scalar(mixed $value): mixed
    {
        return match (true) {
            $value instanceof BackedEnum => $value->value,
            $value instanceof ChronosDate => $value->format('Y-m-d'),
            $value instanceof DateTimeInterface => $value->format(DATE_ATOM),
            $value instanceof Stringable => (string)$value,
            default => $value,
        };
    }

    /**
     * Gives the customer the label of a confirmed GDPR consent, unless they have it already.
     *
     * @param string $customerId The customer.
     * @return void
     */
    private function labelConsent(string $customerId): void
    {
        $name = Settings::getString(Throttle::SETTINGS_PATH . '.consent_label', '');
        $label = $name === '' ? null : $this->fetchTable('Labels')->find()->where(['Labels.name' => $name])->first();
        if ($label === null) {
            return;
        }

        $customerLabels = $this->fetchTable('CustomerLabels');
        $conditions = ['customer_id' => $customerId, 'label_id' => $label->get('id')];
        if (!$customerLabels->exists($conditions)) {
            $customerLabels->saveOrFail($customerLabels->newEntity($conditions + ['note' => 'form']));
        }
    }

    /**
     * Sends the given values as the JSON answer.
     *
     * @param array<string, mixed> $data The answer.
     * @return void
     */
    private function answer(array $data): void
    {
        $this->set($data);
        $this->viewBuilder()->setOption('serialize', array_keys($data));
    }
}
