<?php
declare(strict_types=1);

namespace App\Model\Enum;

use Cake\Database\Type\EnumLabelInterface;
use Cake\Utility\Inflector;

/**
 * CustomerMessageBodyFormat Enum
 */
enum CustomerMessageBodyFormat: int implements EnumLabelInterface
{
    case Plaintext = 0;
    case Html = 10;

    /**
     * @return string
     */
    public function label(): string
    {
        return Inflector::humanize(Inflector::underscore($this->name));
    }
}
