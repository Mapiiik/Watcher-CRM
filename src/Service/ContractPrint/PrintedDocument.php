<?php
declare(strict_types=1);

namespace App\Service\ContractPrint;

/**
 * A paper, as bytes and under a name.
 *
 * What comes back from the store and what comes back from the printing are the same thing to
 * whoever asked for it, so they are the same thing here.
 */
final class PrintedDocument
{
    /**
     * @param string $bytes The paper itself.
     * @param string $filename What it is called when it is handed over.
     * @param string $mimeType What kind of thing it is.
     */
    public function __construct(
        public readonly string $bytes,
        public readonly string $filename,
        public readonly string $mimeType = 'application/pdf',
    ) {
    }
}
