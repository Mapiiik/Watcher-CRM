<?php
declare(strict_types=1);

namespace App\Agent\Provider;

use App\Agent\Dto\DisconnectResult;
use App\Agent\Dto\PingResult;

/**
 * What the agent answers with, turned into results.
 *
 * The agent runs beside the network rather than beside this application and is deployed on its own
 * schedule, so a field that is missing or of a type nobody expected is read as not being there. The
 * verdict is the exception: an answer with no verdict in it is not a verdict of no, it is not an
 * answer to this question at all, and the client turns it away before it reaches here.
 */
final class AgentPayloadNormalizer
{
    /**
     * What came of a ping.
     *
     * @param array<mixed> $body The answer as it arrived.
     * @return \App\Agent\Dto\PingResult
     */
    public static function ping(array $body): PingResult
    {
        /** @var array<string, mixed> $body */
        return new PingResult(
            reachable: (bool)($body['reachable'] ?? false),
            lossPercent: self::floatOrNull($body['loss_pct'] ?? null),
            message: self::stringOrNull($body['message'] ?? null),
            raw: $body,
        );
    }

    /**
     * What came of a disconnect.
     *
     * @param array<mixed> $body The answer as it arrived.
     * @return \App\Agent\Dto\DisconnectResult
     */
    public static function disconnect(array $body): DisconnectResult
    {
        $causes = $body['error_causes'] ?? [];
        $causes = is_array($causes) ? $causes : [];

        /** @var array<string, mixed> $body */
        return new DisconnectResult(
            success: (bool)($body['success'] ?? false),
            result: self::stringOrNull($body['result'] ?? null),
            message: self::stringOrNull($body['message'] ?? null),
            errorCauses: array_values(array_map(intval(...), array_filter($causes, is_numeric(...)))),
            raw: $body,
        );
    }

    /**
     * @param mixed $value Value to read.
     * @return string|null
     */
    private static function stringOrNull(mixed $value): ?string
    {
        return is_scalar($value) && trim((string)$value) !== '' ? trim((string)$value) : null;
    }

    /**
     * @param mixed $value Value to read.
     * @return float|null
     */
    private static function floatOrNull(mixed $value): ?float
    {
        return is_scalar($value) && is_numeric($value) ? (float)$value : null;
    }
}
