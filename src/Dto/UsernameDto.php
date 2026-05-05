<?php
// src/Dto/UsernameDto.php

namespace App\Dto;

use Symfony\Component\Validator\Constraints\NotBlank;
use App\Validator\Constraints\NoSpaces;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Length;

class UsernameDto
{
    #[NotBlank(message: 'Bitte gib einen Benutzernamen ein.')]
    #[NoSpaces]
    #[Regex(
        pattern: '/^[a-zA-Z0-9_.-]+$/',
        message: 'Der Benutzername darf nur Buchstaben, Zahlen, _, . und - enthalten.'
    )]
    #[Length(
        min: 3,
        minMessage: 'Der Benutzername muss mindestens {{ limit }} Zeichen lang sein.',
        max: 50,
        maxMessage: 'Der Benutzername darf maximal {{ limit }} Zeichen lang sein.'
    )]
    public ?string $username = null;
}