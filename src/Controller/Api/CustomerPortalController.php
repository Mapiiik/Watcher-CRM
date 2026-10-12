<?php
declare(strict_types=1);

namespace App\Controller\Api;

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
use BackedEnum;
use Bookkeeping\Model\Entity\Invoice;
use Bookkeeping\Service\BookkeepingService;
use Cake\Chronos\ChronosDate;
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
        $details['phones'] = array_map(
            fn(EntityInterface $phone): array => $this->fields($phone, ['id', 'phone']),
            $customer->phones,
        );
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
