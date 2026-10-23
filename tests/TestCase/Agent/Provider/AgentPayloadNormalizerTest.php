<?php
declare(strict_types=1);

namespace App\Test\TestCase\Agent\Provider;

use App\Agent\Provider\AgentPayloadNormalizer;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * App\Agent\Provider\AgentPayloadNormalizer Test Case
 *
 * The distinction the ping cell is drawn from is the one worth pinning down: a host that answers
 * everything, a host that answers some of it, and a host that answers nothing are three states, and
 * an operator acts on each of them differently.
 */
#[CoversClass(AgentPayloadNormalizer::class)]
class AgentPayloadNormalizerTest extends TestCase
{
    /**
     * A host that answered everything it was asked is well.
     *
     * @return void
     * @link \App\Agent\Provider\AgentPayloadNormalizer::ping()
     */
    public function testAHostThatAnsweredEverythingIsWell(): void
    {
        $ping = AgentPayloadNormalizer::ping(['reachable' => true, 'loss_pct' => 0]);

        $this->assertTrue($ping->reachable);
        $this->assertTrue($ping->isHealthy());
    }

    /**
     * A host that answered some of it is up and in trouble, which is not the same as being well
     * and not the same as being down.
     *
     * @return void
     * @link \App\Agent\Provider\AgentPayloadNormalizer::ping()
     */
    public function testAHostThatAnsweredSomeOfItIsUpAndInTrouble(): void
    {
        $ping = AgentPayloadNormalizer::ping(['reachable' => true, 'loss_pct' => 30]);

        $this->assertTrue($ping->reachable);
        $this->assertFalse($ping->isHealthy());
        $this->assertSame(30.0, $ping->lossPercent);
    }

    /**
     * A host that answered nothing is down.
     *
     * @return void
     * @link \App\Agent\Provider\AgentPayloadNormalizer::ping()
     */
    public function testAHostThatAnsweredNothingIsDown(): void
    {
        $ping = AgentPayloadNormalizer::ping(['reachable' => false, 'loss_pct' => 100]);

        $this->assertFalse($ping->reachable);
        $this->assertFalse($ping->isHealthy());
    }

    /**
     * The answer is kept as it arrived, because the endpoint hands it on to whoever reads it as
     * JSON and that reader knows the agent's own words rather than ours.
     *
     * @return void
     * @link \App\Agent\Provider\AgentPayloadNormalizer::ping()
     */
    public function testTheAnswerIsKeptAsItArrived(): void
    {
        $body = ['reachable' => true, 'loss_pct' => 0, 'rtt_avg_ms' => 4.2];

        $this->assertSame($body, AgentPayloadNormalizer::ping($body)->raw);
    }

    /**
     * A refused disconnect carries the numbers the access server gives its reasons, and they are
     * kept as numbers - what they mean is said in words further up, where an operator reads them.
     *
     * @return void
     * @link \App\Agent\Provider\AgentPayloadNormalizer::disconnect()
     */
    public function testARefusedDisconnectKeepsTheReasonsAsNumbers(): void
    {
        $result = AgentPayloadNormalizer::disconnect([
            'success' => false,
            'result' => 'Disconnect-NAK',
            'error_causes' => [503, 'nonsense', 404],
        ]);

        $this->assertFalse($result->success);
        $this->assertSame('Disconnect-NAK', $result->result);
        $this->assertSame([503, 404], $result->errorCauses);
    }

    /**
     * A disconnect that went through says so and has nothing to explain.
     *
     * @return void
     * @link \App\Agent\Provider\AgentPayloadNormalizer::disconnect()
     */
    public function testADisconnectThatWentThroughHasNothingToExplain(): void
    {
        $result = AgentPayloadNormalizer::disconnect(['success' => true, 'result' => 'Disconnect-ACK']);

        $this->assertTrue($result->success);
        $this->assertSame([], $result->errorCauses);
    }

    /**
     * What changed on an address list comes back entry by entry, and an entry without an address
     * says nothing and is left out. A family the agent did not name is read off the address.
     *
     * @return void
     * @link \App\Agent\Provider\AgentPayloadNormalizer::addressList()
     */
    public function testAddressListChangesAreReadEntryByEntry(): void
    {
        $changes = AgentPayloadNormalizer::addressList([
            'host' => '10.0.0.1',
            'transport' => 'api-ssl',
            'added' => [
                ['address' => '192.0.2.1', 'family' => 'ipv4', 'comment' => '1001 - A'],
                ['address' => '2001:db8::/48', 'comment' => '1002 - B'],
                ['comment' => 'no address'],
                'nonsense',
            ],
            'removed' => [['address' => '198.51.100.0/24', 'family' => 'ipv4']],
            'unchanged' => '41',
        ]);

        $this->assertSame('10.0.0.1', $changes->host);
        $this->assertSame('api-ssl', $changes->transport);
        $this->assertCount(2, $changes->added);
        $this->assertFalse($changes->added[0]->isIpv6());
        $this->assertTrue($changes->added[1]->isIpv6());
        $this->assertSame('1002 - B', $changes->added[1]->comment);
        $this->assertSame([], $changes->updated);
        $this->assertSame('', $changes->removed[0]->comment);
        $this->assertSame(41, $changes->unchanged);
    }

    /**
     * What was taken off the access lists comes back with the list and interface it was on.
     *
     * @return void
     * @link \App\Agent\Provider\AgentPayloadNormalizer::accessList()
     */
    public function testAccessListChangesAreReadEntryByEntry(): void
    {
        $changes = AgentPayloadNormalizer::accessList([
            'host' => '10.0.0.1',
            'transport' => 'api',
            'removed' => [
                ['menu' => '/interface/wifi/access-list', 'interface' => 'wifi1', 'mac_address' => 'AA:BB:CC:DD:EE:FF'],
                ['menu' => '/interface/wifi/access-list'],
            ],
        ]);

        $this->assertSame('api', $changes->transport);
        $this->assertCount(1, $changes->removed);
        $this->assertSame('wifi1', $changes->removed[0]->interface);
        $this->assertSame('AA:BB:CC:DD:EE:FF', $changes->removed[0]->macAddress);
    }
}
