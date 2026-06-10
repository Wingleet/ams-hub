<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class RegisterRequest
{
    #[Assert\NotBlank(message: 'Email is required')]
    #[Assert\Email(message: 'Please provide a valid email address')]
    public string $email;

    public ?string $username = null;

    #[Assert\NotBlank(message: 'Password is required')]
    #[Assert\Length(
        min: 8,
        minMessage: 'Password must be at least {{ limit }} characters long'
    )]
    public string $password;

    #[Assert\NotBlank(message: 'First name is required')]
    #[Assert\Length(max: 100)]
    public string $firstName;

    #[Assert\NotBlank(message: 'Last name is required')]
    #[Assert\Length(max: 100)]
    public string $lastName;
}
