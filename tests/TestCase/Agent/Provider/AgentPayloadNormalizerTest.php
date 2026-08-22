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
}
