<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use App\Validator\Constraints\NoSpaces;
use Symfony\Component\Validator\Constraints\Regex;

class UserPasswordDto
{
    #[NotBlank(message: 'Bitte gib dein aktuelles Passwort ein.')]
    public ?string $currentPassword = null;

    #[NotBlank(message: 'Bitte gib ein neues Passwort ein.')]
    #[NoSpaces]
    #[Length(
        min: 6,
        minMessage: 'Das Passwort muss mindestens {{ limit }} Zeichen lang sein.',
        max: 4096,
    )]
    #[Regex(
        pattern: '/[A-Z]/',
        message: 'Das Passwort muss mindestens einen Großbuchstaben enthalten.'
    )]
    #[Regex(
        pattern: '/[0-9]/',
        message: 'Das Passwort muss mindestens eine Zahl enthalten.'
    )]
    #[Regex(
        pattern: '/[\W_]/',
        message: 'Das Passwort muss mindestens ein Sonderzeichen enthalten.'
    )]
    public ?string $newPassword = null;

    #[NotBlank(message: 'Bitte bestätige dein neues Passwort.')]
    public ?string $confirmPassword = null;
}