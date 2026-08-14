<?php
declare(strict_types=1);

namespace App\BusinessRegister;

use Cake\Database\Type\EnumLabelInterface;
use Override;

/**
 * What a register says about an identification number.
 *
 * The check digit already says whether the number is well formed, which the CRM works out on its
 * own and without asking anybody. What only a register can say is whether anyone actually holds
 * it - a number can be arithmetically sound and still belong to nobody.
 */
enum IdentityNumberStatus: string implements EnumLabelInterface
{
    case Found = 'found';
    case NotFound = 'not_found';

    /**
     * @return string
     */
    #[Override]
    public function label(): string
    {
        return match ($this) {
            self::Found => __('in the register'),
            self::NotFound => __('not in the register'),
        };
    }
}
