<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthService
{
    public function __construct(
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private EntityManagerInterface $entityManager,
        private AmsApiService $amsApiService,
    ) {
    }

    public function register(
        string $email,
        string $password,
        string $firstName,
        string $lastName
    ): User {
        // Check if user already exists
        if ($this->userRepository->findOneBy(['email' => $email])) {
            throw new \InvalidArgumentException('User with this email already exists');
        }

        $user = new User();
        $user->setEmail($email);
        $user->setFirstName($firstName);
        $user->setLastName($lastName);

        // Hash the password
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        // Save the user
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    public function updateLastLogin(User $user): void
    {
        $user->setLastLoginAt(new \DateTime());
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    public function verifyCredentials(string $identifier, string $password): ?User
    {
        $user = $this->userRepository->findByIdentifier($identifier);

        if (!$user) {
            return null;
        }

        // If user is from AMS API, validate credentials against AMS using username
        if ($user->isAmsUser()) {
            $amsIdentifier = $user->getUsername() ?? $user->getEmail();
            $isValid = $this->amsApiService->validateUserCredentials($amsIdentifier, $password);
            return $isValid ? $user : null;
        }

        // Otherwise, use local password validation
        if (!$this->passwordHasher->isPasswordValid($user, $password)) {
            return null;
        }

        return $user;
    }
}
