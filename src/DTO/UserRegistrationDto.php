<?php

// src/DTO/UserRegistrationDto.php
namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class UserRegistrationDto
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public string $userEmail;

    #[Assert\NotBlank]
    #[Assert\Length(min: 6)]
    public string $password;

    #[Assert\NotBlank]
    public string $username;

}