<?php
declare(strict_types=1);

namespace Bookkeeping\Test\TestCase\Provider\Eurofaktura;

use App\Model\Entity\Customer;
use App\Model\Enum\CustomerLegalStatus;
use Bookkeeping\Provider\Eurofaktura\JsonRequestBuilder;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Bookkeeping\Provider\Eurofaktura\JsonRequestBuilder Test Case
 *
 * Only who the partner is, which is what the customer's legal status decides.
 */
#[CoversClass(JsonRequestBuilder::class)]
class JsonRequestBuilderTest extends TestCase
{
    /**
     * A sole trader does business under a name of their own, so the partner is the entrepreneur
     * e-računi has a legal type for, going by that name.
     *
     * @return void
     * @link \Bookkeeping\Provider\Eurofaktura\JsonRequestBuilder::buildPartner()
     */
    public function testASoleTraderIsAContractor(): void
    {
        $partner = (new JsonRequestBuilder())->buildPartner(new Customer([
            'nid' => 1,
            'addresses' => [],
            'company' => 'Obrt Primjer',
            'legal_status' => CustomerLegalStatus::SoleTrader,
        ]));

        $this->assertSame('Contractor', $partner['companyType']);
        $this->assertSame('Obrt Primjer', $partner['companyName']);
    }

    /**
     * A legal entity is an organization in general, as its legal form is not kept.
     *
     * @return void
     * @link \Bookkeeping\Provider\Eurofaktura\JsonRequestBuilder::buildPartner()
     */
    public function testALegalEntityIsAnOrganization(): void
    {
        $partner = (new JsonRequestBuilder())->buildPartner(new Customer([
            'nid' => 1,
            'addresses' => [],
            'company' => 'Primjer d.o.o.',
            'legal_status' => CustomerLegalStatus::LegalEntity,
        ]));

        $this->assertSame('Organization', $partner['companyType']);
    }

    /**
     * A natural person not in business has no legal type - e-računi refuses one for a consumer -
     * a company written down for them or not, and neither has a customer nobody has said anything
     * about.
     *
     * @return void
     * @link \Bookkeeping\Provider\Eurofaktura\JsonRequestBuilder::buildPartner()
     */
    public function testANaturalPersonIsNoOrganization(): void
    {
        $builder = new JsonRequestBuilder();

        $person = $builder->buildPartner(new Customer([
            'nid' => 1,
            'addresses' => [],
            'company' => 'Primjer',
            'legal_status' => CustomerLegalStatus::NaturalPerson,
        ]));
        $nobody = $builder->buildPartner(new Customer(['nid' => 1, 'addresses' => [], 'company' => 'Primjer']));

        $this->assertArrayNotHasKey('companyType', $person);
        $this->assertSame('Primjer', $person['companyName']);
        $this->assertArrayNotHasKey('companyType', $nobody);
    }
}
