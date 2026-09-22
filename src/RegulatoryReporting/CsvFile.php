<?php
declare(strict_types=1);

namespace App\RegulatoryReporting;

/**
 * A file for a regulator's portal: UTF-8 with a byte order mark, so that Excel reads the accents,
 * semicolons between the fields and Windows line ends, the way the files that were accepted came.
 */
final class CsvFile
{
    /**
     * @param list<string> $headers The first line.
     * @param iterable<list<string|int|float|null>> $lines The rest.
     * @return string
     */
    public static function render(array $headers, iterable $lines): string
    {
        $csv = "\u{FEFF}" . self::line($headers);
        foreach ($lines as $line) {
            $csv .= self::line($line);
        }

        return $csv;
    }

    /**
     * One line, a field put in quotes only when it would break the line otherwise.
     *
     * @param list<string|int|float|null> $fields What.
     * @return string
     */
    private static function line(array $fields): string
    {
        return implode(';', array_map(
            function (string|int|float|null $field): string {
                $field = (string)$field;

                return strpbrk($field, ";\"\r\n") === false ? $field : '"' . str_replace('"', '""', $field) . '"';
            },
            $fields,
        )) . "\r\n";
    }
}
