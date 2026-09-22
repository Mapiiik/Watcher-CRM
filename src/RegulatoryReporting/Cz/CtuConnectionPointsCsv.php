<?php
declare(strict_types=1);

namespace App\RegulatoryReporting\Cz;

use RuntimeException;

/**
 * The file ČTÚ takes the address points of one category in.
 */
final class CtuConnectionPointsCsv
{
    /**
     * The columns, in the words of ČTÚ's own form.
     */
    private const HEADERS = [
        'Adresní místo (kód RÚIAN)',
        'Technologická kategorie (identifikátor přílohy)',
        'Přístupy (aktivní přípojky) (počet)',
        'Přístupy (aktivní přípojky) nepodnikajících osob (počet)',
        'Pokryté adresní místo (disponibilní přípojkou) (ANO/NE)',
        'Efektivní rychlost download (interval)',
        'Efektivní rychlost upload (interval)',
        'Maximální dosažitelná rychlost download (interval)',
        'Maximální dosažitelná rychlost upload (interval)',
        'VHCN síť (třída)',
    ];

    /**
     * Only asked of cable networks.
     */
    private const DOCSIS_HEADER = 'Standard DOCSIS 3.1 a vyšší (ANO/NE)';

    /**
     * @param \App\RegulatoryReporting\Cz\CtuTechnologyCategory $category Which category the file is of.
     * @param iterable<\App\RegulatoryReporting\Cz\CtuConnectionPointRow> $rows Its points.
     * @return string
     */
    public static function render(CtuTechnologyCategory $category, iterable $rows): string
    {
        $cable = $category === CtuTechnologyCategory::Catv;

        $headers = self::HEADERS;
        if ($cable) {
            $headers[] = self::DOCSIS_HEADER;
        }
        $headers[] = 'Adresa';

        $csv = implode(';', $headers) . PHP_EOL;

        foreach ($rows as $row) {
            $line = [
                h($row->reference),
                h($row->category->value),
                $row->activeConnections,
                $row->activeNonBusinessConnections,
                $row->availableConnections > 0 ? 'ANO' : 'NE',
                h($row->effectiveDownload->value),
                h($row->effectiveUpload->value),
                h($row->maximalDownload->value),
                h($row->maximalUpload->value),
                (int)$row->vhcn,
            ];

            if ($cable) {
                $line[] = 'NE';
            }

            $line[] = h($row->address);

            $csv .= implode(';', $line) . PHP_EOL;
        }

        $converted = iconv('UTF-8', 'CP1250', $csv);
        if ($converted === false) {
            throw new RuntimeException('Unable to convert CSV data from UTF-8 encoding to CP1250 encoding.');
        }

        return $converted;
    }
}
