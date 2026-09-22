<?php
declare(strict_types=1);

namespace App\Model\Enum;

use App\Model\Enum\Trait\EnumOptionsTrait;
use Cake\Database\Type\EnumLabelInterface;
use Override;

/**
 * How a connection reaches the customer.
 *
 * Neutral on purpose: each regulator sorts these into categories of its own, and they do not
 * agree on how finely. The labels are what the operator picks from, so each says the medium,
 * where the fibre ends and what stands at the customer's end.
 */
enum AccessTechnology: string implements EnumLabelInterface
{
    use EnumOptionsTrait;

    case FwaUnlicensed = 'fwa_unlicensed';
    case FwaLicensed = 'fwa_licensed';
    case FttbEthernet = 'fttb_ethernet';
    case FtthP2pEthernet = 'ftth_p2p_ethernet';
    case FtthP2mpPon = 'ftth_p2mp_pon';
    case CatvDocsis30 = 'catv_docsis30';
    case CatvDocsis31 = 'catv_docsis31';
    case Xdsl = 'xdsl';
    case Satellite = 'satellite';

    /**
     * @return string
     */
    #[Override]
    public function label(): string
    {
        return match ($this) {
            self::FwaUnlicensed => __('Fixed wireless in an unlicensed band (Wi-Fi 5 GHz, 60 GHz and similar)'),
            self::FwaLicensed => __('Fixed wireless in a licensed band (assigned frequency, LTE/5G FWA)'),
            self::FttbEthernet => __('Fibre to the building, copper Ethernet inside (FTTB)'),
            self::FtthP2pEthernet => __('Fibre to the home, point-to-point Ethernet (FTTH P2P)'),
            self::FtthP2mpPon => __('Fibre to the home, GPON/XGS-PON with the customer\'s ONU (FTTH PON)'),
            self::CatvDocsis30 => __('Cable TV coaxial network, DOCSIS 3.0 or older'),
            self::CatvDocsis31 => __('Cable TV coaxial network, DOCSIS 3.1 or newer'),
            self::Xdsl => __('Telephone copper line (ADSL, VDSL)'),
            self::Satellite => __('Satellite link'),
        };
    }

    /**
     * What the last stretch runs over.
     *
     * @return \App\Model\Enum\AccessMedium
     */
    public function medium(): AccessMedium
    {
        return match ($this) {
            self::FwaUnlicensed, self::FwaLicensed => AccessMedium::Wireless,
            self::FttbEthernet, self::FtthP2pEthernet, self::FtthP2mpPon => AccessMedium::Fibre,
            self::CatvDocsis30, self::CatvDocsis31 => AccessMedium::Coaxial,
            self::Xdsl => AccessMedium::Copper,
            self::Satellite => AccessMedium::Satellite,
        };
    }

    /**
     * Whether it is a very high capacity network in the sense of the European code.
     *
     * Fibre at least to the building, or cable that can match it.
     *
     * @return bool
     */
    public function isVhcn(): bool
    {
        return match ($this) {
            self::FttbEthernet, self::FtthP2pEthernet, self::FtthP2mpPon, self::CatvDocsis31 => true,
            default => false,
        };
    }

    /**
     * The options grouped by medium, so that fibre and wireless are not picked one for the other.
     *
     * @return array<string, array<string, string>>
     */
    public static function groupedOptions(): array
    {
        $groups = [];

        foreach (self::cases() as $case) {
            $groups[$case->medium()->label()][$case->value] = $case->label();
        }

        return $groups;
    }
}
