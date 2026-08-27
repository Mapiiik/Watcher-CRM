<?php
declare(strict_types=1);

namespace App\Customers\Check;

use App\Check\CheckInterface;

/**
 * One thing that can be missing from what is on record about a customer.
 *
 * Everything a check does is settled by {@see \App\Check\CheckInterface}. This name exists so
 * that the customer card, the customer overview and the customer registry can say which
 * family they mean, and so that a check written for another family cannot be registered here.
 */
interface CustomerCheckInterface extends CheckInterface
{
}
