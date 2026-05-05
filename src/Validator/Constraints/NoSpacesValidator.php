<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class NoSpacesValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof NoSpaces) {
            throw new UnexpectedTypeException($constraint, NoSpaces::class);
        }

        // Leere Werte werden von NotBlank/NotNull behandelt
        if (null === $value || '' === $value) {
            return;
        }

        if (str_contains((string) $value, ' ')) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value)
                ->addViolation();
        }
    }
}