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
use Symfony\Component\Validator\ConstraintValidator;

class NameValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (
            !is_string($value)
            || '' === $value
            || !$constraint instanceof Name
        ) {
            return;
        }

        $matches = [];
        preg_match_all(Name::PATTERN, $value, $matches);
        if (!empty($matches[0])) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value)
                ->addViolation();
        }
    }
}
