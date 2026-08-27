<?php
declare(strict_types=1);

namespace App\Addresses\Check;

use App\Check\CheckInterface;

/**
 * One thing that can be wrong with the addresses on record.
 *
 * Everything a check does is settled by {@see \App\Check\CheckInterface}. This name exists so
 * that the address card, the address overview and the address registry can say which family
 * they mean, and so that a check written for another family cannot be registered here.
 */
interface AddressCheckInterface extends CheckInterface
{
}
