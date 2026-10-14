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
            'two_step_verification_required' => new BoolType(
                default: false,
                hint: __d('access', 'Everybody signs in with two-step verification.'),
            ),
            'two_step_verification_required_high_security' => new BoolType(
                default: false,
                hint: __d('access', 'Accounts held to the stricter rules sign in with two-step verification.'),
            ),
            'login_attempts' => NumberType::ofInt(
                default: 5,
                hint: __d(
                    'access',
                    'Failed sign-ins of one account from one address before more are refused for a while.',
                ),
            ),
            'login_attempts_per_address' => NumberType::ofInt(
                default: 20,
                hint: __d('access', 'Failed sign-ins from one address, whatever the account, before more are refused.'),
            ),
            'login_block_minutes' => NumberType::ofInt(
                default: 15,
                hint: __d('access', 'How long failed sign-ins are remembered and refusals last.'),
            ),
        ],
    ],
];
