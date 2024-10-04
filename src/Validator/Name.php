<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/symfony-keycloak-bundle.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\Bundle\Keycloak\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class Name extends Constraint
{
    public const PATTERN = '/[<>&"[\]%!#?§;*~=|()^{}\f\n\r\t\v\x00-\x1F\x7F]/';
    public string $message = 'The value {{ value }} is not a valid name.';
}
