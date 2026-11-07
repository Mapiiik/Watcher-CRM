<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Api;

use App\Test\Traits\ControllerTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * What the actions other systems reach send out, field by field.
 *
 * These answers are entities turned into JSON, so a column added to a table would go out with them
 * on its own. A new field has to be written down in `answer-fields.json` first, which is the moment
 * to ask whether it should leave the application at all.
 */
class AnswerFieldsTest extends TestCase
{
    use ControllerTestTrait;
    use IntegrationTestTrait;

    private const CUSTOMER_ID = '403bab0e-52cd-4a8e-83f8-43c2457d0481';

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Users',
        'app.AccountingProfiles',
        'app.Countries',
        'app.Commissions',
        'app.Customers',
        'app.Addresses',
        'app.Emails',
        'app.Phones',
        'app.Logins',
        'app.Labels',
        'app.CustomerLabels',
        'app.ContractStates',
        'app.ServiceTypes',
        'app.Contracts',
        'app.ContractVersions',
        'app.ConnectionProfiles',
        'app.Services',
        'app.Billings',
        'app.EquipmentTypes',
        'app.BorrowedEquipments',
        'app.SoldEquipments',
        'app.IpAddresses',
        'app.RemovedIpAddresses',
        'app.IpNetworks',
        'app.RemovedIpNetworks',
        'app.TaskStates',
        'app.TaskTypes',
        'app.Tasks',
    ];

    /**
     * @param string $url The action.
     * @param string $key What the records are under in the answer.
     * @param list<string> $fields What a record is allowed to carry.
     * @return void
     */
    #[DataProvider('answers')]
    public function testOnlyTheFieldsWrittenDownGoOut(string $url, string $key, array $fields): void
    {
        $this->login('api');
        $this->configRequest(['headers' => ['Accept' => 'application/json']]);

        $this->get($url);

        $this->assertResponseOk();
        $answer = json_decode($this->_getBodyAsString(), true);
        $records = $answer[$key];
        $this->assertNotEmpty($records, 'There is nothing to look at.');

        $this->assertSame($fields, $this->fieldsOf(array_is_list($records) ? $records : [$records]));
    }

    /**
     * @return array<string, array{string, string, list<string>}>
     */
    public static function answers(): array
    {
        $written = json_decode((string)file_get_contents(__DIR__ . DS . 'answer-fields.json'), true);

        return [
            'customers' => ['/api/customers.json', 'customers', $written['customers']],
            'customer' => ['/api/customers/' . self::CUSTOMER_ID . '.json', 'customer', $written['customer']],
            'tasks' => ['/api/tasks.json', 'tasks', $written['tasks']],
        ];
    }

    /**
     * The fields of the records, sorted, and those of what is nested in them with its path.
     *
     * @param list<array<string, mixed>> $records The records.
     * @param string $path Where they are.
     * @return list<string>
     */
    private function fieldsOf(array $records, string $path = ''): array
    {
        $fields = [];
        foreach ($records as $record) {
            foreach ($record as $field => $value) {
                $fields[] = $path . $field;
                if (is_array($value) && $value !== []) {
                    $nested = array_is_list($value) ? $value : [$value];
                    if (is_array($nested[0])) {
                        $fields = [...$fields, ...$this->fieldsOf($nested, $path . $field . '.')];
                    }
                }
            }
        }
        $fields = array_values(array_unique($fields));
        sort($fields);

        return $fields;
    }
}
