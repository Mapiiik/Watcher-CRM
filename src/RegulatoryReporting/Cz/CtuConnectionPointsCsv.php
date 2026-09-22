<?php
declare(strict_types=1);

namespace App\RegulatoryReporting\Cz;

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
     * The file itself: UTF-8 with a byte order mark, so that Excel reads the accents, and Windows
     * line ends, the way the files that were accepted came.
     *
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

        $csv = "\u{FEFF}" . self::line($headers);

        foreach ($rows as $row) {
            $line = [
                (string)$row->reference,
                $row->category->value,
                (string)$row->activeConnections,
                (string)$row->activeNonBusinessConnections,
                $row->covered ? 'ANO' : 'NE',
                $row->effectiveDownload->value,
                $row->effectiveUpload->value,
                $row->maximalDownload->value,
                $row->maximalUpload->value,
                $row->vhcn ? '1' : '0',
            ];

            if ($cable) {
                $line[] = $row->docsis31 ? 'ANO' : 'NE';
            }

            $line[] = (string)$row->address;

            $csv .= self::line($line);
        }

        return $csv;
    }

    /**
     * One line, a field put in quotes only when it would break the line otherwise.
     *
     * @param list<string> $fields What.
     * @return string
     */
    private static function line(array $fields): string
    {
        return implode(';', array_map(
            fn(string $field): string => strpbrk($field, ";\"\r\n") === false
                ? $field
                : '"' . str_replace('"', '""', $field) . '"',
            $fields,
        )) . "\r\n";
    }
}
