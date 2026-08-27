<?php
declare(strict_types=1);

namespace App\Contracts\Check;

use App\Check\CheckInterface;

/**
 * One thing that can be wrong with a contract and the records hanging off it.
 *
 * Everything a check does is settled by {@see \App\Check\CheckInterface}. This name exists so
 * that the contract card, the contract overview and the contract registry can say which
 * family they mean, and so that a check written for another family cannot be registered here.
 */
interface ContractCheckInterface extends CheckInterface
{
}
