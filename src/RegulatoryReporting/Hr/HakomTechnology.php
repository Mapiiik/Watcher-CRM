<?php
declare(strict_types=1);

namespace App\RegulatoryReporting\Hr;

use App\Model\Enum\AccessMedium;
use App\Model\Enum\AccessTechnology;

/**
 * Where a technology lands on HAKOM's quarterly forms.
 *
 * The forms number their rows as a tree. The codes here are the branches below the private or
 * business node, for the operator's own network, and the words are the form's own.
 */
final class HakomTechnology
{
    /**
     * The branch of section I that counts the connections, below "1.1.1." or "1.1.2.".
     *
     * @param \App\Model\Enum\AccessTechnology $technology The technology.
     * @return array{string, string}|null The code and the form's words, null for none.
     */
    public static function connections(AccessTechnology $technology): ?array
    {
        return match ($technology) {
            AccessTechnology::FwaUnlicensed => [
                '4.2',
                'Fiksni bežični pristup putem vlastite mreže u nelicenciranom spektru',
            ],
            AccessTechnology::FwaLicensed => [
                '4.1',
                'Fiksni bežični pristup putem vlastite mreže u licenciranom spektru',
            ],
            AccessTechnology::FttbEthernet => ['2.2.4', 'FTTB - Ethernet'],
            AccessTechnology::FtthP2pEthernet => ['2.3.1', 'FTTH - P2P Ethernet'],
            AccessTechnology::FtthP2mpPon => ['2.3.4', 'FTTH - P2MP PON (GPON, NG-PON1, NG-PON2, XGS-PON)'],
            AccessTechnology::CatvDocsis30 => ['3.1.2', 'Broj priključaka putem DOCSIS 3.0 tehnologije'],
            AccessTechnology::CatvDocsis31 => ['3.1.3', 'Broj priključaka putem DOCSIS 3.1 tehnologije'],
            AccessTechnology::Satellite => ['5', 'Broj priključaka putem satelitskih veza'],
            // which of the copper branches depends on where the DSLAM stands, which is not known here
            AccessTechnology::Xdsl => null,
        };
    }

    /**
     * The row of section II that takes the traffic.
     *
     * @param \App\Model\Enum\AccessTechnology $technology The technology.
     * @return array{string, string}
     */
    public static function traffic(AccessTechnology $technology): array
    {
        return match ($technology) {
            AccessTechnology::FwaUnlicensed, AccessTechnology::FwaLicensed => [
                '2.1.9',
                'Ukupan promet putem bežičnih tehnologija u nepokretnoj mreži',
            ],
            AccessTechnology::FttbEthernet => ['2.1.5', 'Ukupan promet putem FTTB pristupne tehnologije'],
            AccessTechnology::FtthP2pEthernet, AccessTechnology::FtthP2mpPon => [
                '2.1.6',
                'Ukupan promet putem FTTH pristupne tehnologije',
            ],
            AccessTechnology::CatvDocsis30, AccessTechnology::CatvDocsis31 => [
                '2.1.8',
                'Ukupan promet putem kabelskih mreža',
            ],
            AccessTechnology::Xdsl => [
                '2.1.1',
                'Ukupan promet putem bakrene pristupne tehnologije (ADSL, VDSL i ostalo)',
            ],
            AccessTechnology::Satellite => ['2.1.10', 'Ukupan promet putem satelitskih veza'],
        };
    }

    /**
     * The row of section III that takes the revenue, below "3.1." or "3.2.".
     *
     * @param \App\Model\Enum\AccessTechnology $technology The technology.
     * @return array{string, string}
     */
    public static function revenue(AccessTechnology $technology): array
    {
        return match ($technology->medium()) {
            AccessMedium::Copper => [
                '1.1',
                'Prihod od pretplatnika koji pristup ostvaruju putem bakrene pristupne tehnologije',
            ],
            AccessMedium::Fibre => [
                '1.2',
                'Prihod od pretplatnika koji pristup ostvaruju putem svjetlovodnih pristupnih tehnologija',
            ],
            AccessMedium::Coaxial => [
                '1.3',
                'Prihod od pretplatnika koji pristup ostvaruju putem kabelskih mreža',
            ],
            AccessMedium::Wireless => [
                '1.5',
                'Prihod od pretplatnika koji pristup ostvaruju putem bežičnih tehnologija u nepokretnoj mreži',
            ],
            AccessMedium::Satellite => [
                '4',
                'Prihod od pretplatnika koji pristup ostvaruju putem satelitskih veza',
            ],
        };
    }

    /**
     * The type of infrastructure as the address listing names it.
     *
     * @param \App\Model\Enum\AccessTechnology $technology The technology.
     * @return string
     */
    public static function infrastructureType(AccessTechnology $technology): string
    {
        return match ($technology) {
            AccessTechnology::FwaUnlicensed, AccessTechnology::FwaLicensed => 'FWA-WiFi',
            AccessTechnology::FttbEthernet => 'FTTB',
            AccessTechnology::FtthP2pEthernet, AccessTechnology::FtthP2mpPon => 'FTTH',
            AccessTechnology::CatvDocsis30, AccessTechnology::CatvDocsis31 => 'HFC',
            AccessTechnology::Xdsl => 'xDSL',
            AccessTechnology::Satellite => 'SAT',
        };
    }
}
