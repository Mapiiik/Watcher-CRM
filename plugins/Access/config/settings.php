<?php
/**
 * Default settings of signing in, merged into the application's own.
 */

use Settings\ValueObject\Type\BoolType;
use Settings\ValueObject\Type\ListType;
use Settings\ValueObject\Type\NumberType;

return [
    'access' => [
        'security' => [
            'high_security_roles' => ListType::ofStrings(
                default: ['admin'],
                hint: __d('access', 'Roles held to the stricter rules, together with superusers.'),
            ),
            'password_min_length' => NumberType::ofInt(
                default: 12,
                hint: __d('access', 'The shortest password an account may be given.'),
            ),
            'password_min_length_high_security' => NumberType::ofInt(
                default: 17,
                hint: __d('access', 'The shortest password of an account held to the stricter rules.'),
            ),
            'password_reject_simple' => new BoolType(
                default: true,
                hint: __d(
                    'access',
                    'Refuse passwords containing the username or e-mail, repeated or running characters, '
                    . 'and the most common passwords.',
                ),
            ),
        ],
    ],
];
