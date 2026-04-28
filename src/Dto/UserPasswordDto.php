<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;

class UserPasswordDto
{
    #[NotBlank(message: 'Bitte gib dein aktuelles Passwort ein.')]
    public ?string $currentPassword = null;

    #[NotBlank(message: 'Bitte gib ein neues Passwort ein.')]
    #[Length(min: 8, minMessage: 'Das Passwort muss mindestens 8 Zeichen lang sein.')]
    public ?string $newPassword = null;

    #[NotBlank(message: 'Bitte bestätige dein neues Passwort.')]
    public ?string $confirmPassword = null;
}