<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class LoginRequest
{
    #[Assert\NotBlank(message: 'Username is required')]
    public string $username;

    #[Assert\NotBlank(message: 'Password is required')]
    public string $password;

    public bool $rememberMe = false;
}
