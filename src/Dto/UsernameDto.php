<?php
// src/Dto/UsernameDto.php

namespace App\Dto;

use Symfony\Component\Validator\Constraints\NotBlank;

class UsernameDto
{
    #[NotBlank(message: 'Bitte gib einen Benutzernamen ein.')]
    public ?string $username = null;
}