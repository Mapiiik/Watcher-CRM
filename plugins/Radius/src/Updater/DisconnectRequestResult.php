<?php
declare(strict_types=1);

namespace Radius\Updater;

use Radius\Model\Entity\Radacct;

/**
 * What came of asking for one RADIUS session to be dropped.
 *
 * Unlike the agent's own answer, there is one whatever happened - an agent that could not be
 * reached or was never configured leaves a session up just as a refusing access server does.
 */
final readonly class DisconnectRequestResult
{
    /**
     * @param \Radius\Model\Entity\Radacct $session The session asked about.
     * @param bool $disconnected Whether it was dropped.
     * @param string $detail What the access server or the agent said, in a line for the operator.
     */
    public function __construct(
        public Radacct $session,
        public bool $disconnected,
        public string $detail,
    ) {
    }
}
