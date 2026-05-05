<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
class NoSpaces extends Constraint
{
    public string $message = 'Der Wert darf keine Leerzeichen enthalten.';
}