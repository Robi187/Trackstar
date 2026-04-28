<?php
// src/Dto/UserEmailDto.php

namespace App\Dto;

use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;

class UserEmailDto
{
    #[NotBlank(message: 'Bitte gib eine E-Mail-Adresse ein.')]
    #[Email(message: 'Bitte gib eine gültige E-Mail-Adresse ein.')]
    public ?string $email = null;
}