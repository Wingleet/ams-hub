<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\AmsApiService;
use App\Service\AuthService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthServiceTest extends TestCase
{
    private UserRepository $userRepository;
    private UserPasswordHasherInterface $passwordHasher;
    private EntityManagerInterface $entityManager;
    private AmsApiService $amsApiService;
    private AuthService $authService;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->amsApiService = $this->createMock(AmsApiService::class);
        $this->authService = new AuthService(
            $this->userRepository,
            $this->passwordHasher,
            $this->entityManager,
            $this->amsApiService
        );
    }

    public function testRegisterCreatesNewUser(): void
    {
        $email = 'test@example.com';
        $password = 'password123';
        $firstName = 'John';
        $lastName = 'Doe';
        $hashedPassword = 'hashed_password';

        $this->userRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => $email])
            ->willReturn(null);

        $this->passwordHasher->expects($this->once())
            ->method('hashPassword')
            ->willReturn($hashedPassword);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with(
                $this->callback(function (User $user) use ($email, $firstName, $lastName, $hashedPassword) {
                    return $user->getEmail() === $email
                        && $user->getFirstname() === $firstName
                        && $user->getLastname() === $lastName
                        && $user->getPassword() === $hashedPassword;
                })
            );

        $this->entityManager->expects($this->once())
            ->method('flush');

        $user = $this->authService->register($email, $password, $firstName, $lastName);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($email, $user->getEmail());
        $this->assertEquals($firstName, $user->getFirstname());
        $this->assertEquals($lastName, $user->getLastname());
    }

    public function testRegisterThrowsExceptionWhenUserExists(): void
    {
        $email = 'existing@example.com';
        $existingUser = new User();
        $existingUser->setEmail($email);

        $this->userRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => $email])
            ->willReturn($existingUser);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('User with this email already exists');

        $this->authService->register($email, 'password', 'John', 'Doe');
    }

    public function testUpdateLastLogin(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($user);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->authService->updateLastLogin($user);

        $this->assertInstanceOf(\DateTimeInterface::class, $user->getLastLoginAt());
    }

    public function testVerifyCredentialsReturnsUserWhenValid(): void
    {
        $identifier = 'test@example.com';
        $password = 'password123';
        $user = new User();
        $user->setEmail($identifier);
        $user->setIsAmsUser(false); // Local user

        $this->userRepository->expects($this->once())
            ->method('findByIdentifier')
            ->with($identifier)
            ->willReturn($user);

        // Should NOT call AMS API
        $this->amsApiService->expects($this->never())
            ->method('validateUserCredentials');

        // Should call local password validation
        $this->passwordHasher->expects($this->once())
            ->method('isPasswordValid')
            ->with($user, $password)
            ->willReturn(true);

        $result = $this->authService->verifyCredentials($identifier, $password);

        $this->assertSame($user, $result);
    }

    public function testVerifyCredentialsReturnsNullWhenUserNotFound(): void
    {
        $identifier = 'nonexistent@example.com';
        $password = 'password123';

        $this->userRepository->expects($this->once())
            ->method('findByIdentifier')
            ->with($identifier)
            ->willReturn(null);

        $this->passwordHasher->expects($this->never())
            ->method('isPasswordValid');

        $result = $this->authService->verifyCredentials($identifier, $password);

        $this->assertNull($result);
    }

    public function testVerifyCredentialsReturnsNullWhenPasswordInvalid(): void
    {
        $identifier = 'test@example.com';
        $password = 'wrongpassword';
        $user = new User();
        $user->setEmail($identifier);

        $this->userRepository->expects($this->once())
            ->method('findByIdentifier')
            ->with($identifier)
            ->willReturn($user);

        $this->passwordHasher->expects($this->once())
            ->method('isPasswordValid')
            ->with($user, $password)
            ->willReturn(false);

        $result = $this->authService->verifyCredentials($identifier, $password);

        $this->assertNull($result);
    }

    public function testVerifyCredentialsUsesAmsApiForAmsUsers(): void
    {
        $identifier = 'amsuser';
        $password = 'amspassword';
        $user = new User();
        $user->setEmail('ams.user@example.com');
        $user->setUsername('amsuser');
        $user->setIsAmsUser(true); // AMS user

        $this->userRepository->expects($this->once())
            ->method('findByIdentifier')
            ->with($identifier)
            ->willReturn($user);

        // Should call AMS API validation with the username
        $this->amsApiService->expects($this->once())
            ->method('validateUserCredentials')
            ->with('amsuser', $password)
            ->willReturn(true);

        // Should NOT call local password validation
        $this->passwordHasher->expects($this->never())
            ->method('isPasswordValid');

        $result = $this->authService->verifyCredentials($identifier, $password);

        $this->assertSame($user, $result);
    }

    public function testVerifyCredentialsReturnsNullWhenAmsApiRejectsCredentials(): void
    {
        $identifier = 'amsuser';
        $password = 'wrongpassword';
        $user = new User();
        $user->setEmail('ams.user@example.com');
        $user->setUsername('amsuser');
        $user->setIsAmsUser(true); // AMS user

        $this->userRepository->expects($this->once())
            ->method('findByIdentifier')
            ->with($identifier)
            ->willReturn($user);

        // AMS API rejects credentials
        $this->amsApiService->expects($this->once())
            ->method('validateUserCredentials')
            ->with('amsuser', $password)
            ->willReturn(false);

        // Should NOT call local password validation
        $this->passwordHasher->expects($this->never())
            ->method('isPasswordValid');

        $result = $this->authService->verifyCredentials($identifier, $password);

        $this->assertNull($result);
    }

    public function testVerifyCredentialsUsesLocalPasswordForNonAmsUsers(): void
    {
        $identifier = 'local.user@example.com';
        $password = 'localpassword';
        $user = new User();
        $user->setEmail($identifier);
        $user->setIsAmsUser(false); // Explicitly local user

        $this->userRepository->expects($this->once())
            ->method('findByIdentifier')
            ->with($identifier)
            ->willReturn($user);

        // Should NOT call AMS API validation
        $this->amsApiService->expects($this->never())
            ->method('validateUserCredentials');

        // Should call local password validation
        $this->passwordHasher->expects($this->once())
            ->method('isPasswordValid')
            ->with($user, $password)
            ->willReturn(true);

        $result = $this->authService->verifyCredentials($identifier, $password);

        $this->assertSame($user, $result);
    }
}
