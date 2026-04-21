<?php
// src/Dto/UserEmailDto.php

namespace App\Dto;

use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;

class UsernameDto
{
    #[NotBlank(message: 'Bitte gib einen Benutzernamen ein.')]
    #[Length(
        min: 3,
        minMessage: 'Der Benutzername muss mindestens {{ limit }} Zeichen lang sein.',
        max: 50,
        maxMessage: 'Der Benutzername darf maximal {{ limit }} Zeichen lang sein.',
    )]
    public string $username = '';
}