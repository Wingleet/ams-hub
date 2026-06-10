<?php

namespace App\Tests\Service;

use App\DTO\LoginRequest;
use App\DTO\RegisterRequest;
use App\Entity\User;
use App\Entity\Organization;
use App\Service\AuthResponseBuilderService;
use App\Service\AuthService;
use App\Service\JwtService;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use PHPUnit\Framework\TestCase;

class AuthResponseBuilderServiceTest extends TestCase
{
    private AuthResponseBuilderService $service;
    private AuthService $authService;
    private JwtService $jwtService;
    private ValidatorInterface $validator;
    private UserRepository $userRepository;

    protected function setUp(): void
    {
        $this->authService = $this->createMock(AuthService::class);
        $this->jwtService = $this->createMock(JwtService::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->userRepository = $this->createMock(UserRepository::class);

        $this->service = new AuthResponseBuilderService(
            $this->authService,
            $this->jwtService,
            $this->validator,
            $this->userRepository
        );
    }

    public function testValidateRegisterRequestWithValidData(): void
    {
        $data = [
            'email' => 'user@example.com',
            'password' => 'SecurePassword123!',
            'firstName' => 'John',
            'lastName' => 'Doe'
        ];

        $this->validator->method('validate')
            ->willReturn(new ConstraintViolationList());

        $result = $this->service->validateRegisterRequest($data);

        $this->assertInstanceOf(RegisterRequest::class, $result);
        $this->assertEquals('user@example.com', $result->email);
        $this->assertEquals('SecurePassword123!', $result->password);
        $this->assertEquals('John', $result->firstName);
        $this->assertEquals('Doe', $result->lastName);
    }

    public function testValidateRegisterRequestWithMissingFields(): void
    {
        $data = [
            'email' => 'user@example.com'
            // Missing password, firstName, lastName
        ];

        $this->validator->method('validate')
            ->willReturn(new ConstraintViolationList());

        $result = $this->service->validateRegisterRequest($data);

        $this->assertInstanceOf(RegisterRequest::class, $result);
        $this->assertEquals('user@example.com', $result->email);
        $this->assertEquals('', $result->password);
        $this->assertEquals('', $result->firstName);
        $this->assertEquals('', $result->lastName);
    }

    public function testValidateRegisterRequestWithValidationErrors(): void
    {
        $data = [
            'email' => 'invalid-email',
            'password' => '123',
            'firstName' => 'John',
            'lastName' => 'Doe'
        ];

        $violationList = $this->createMock(ConstraintViolationList::class);
        $violation = $this->createMock(\Symfony\Component\Validator\ConstraintViolation::class);
        
        $violation->method('getPropertyPath')->willReturn('email');
        $violation->method('getMessage')->willReturn('This value is not a valid email address.');
        
        $violationList->method('count')->willReturn(1);
        $violationList->method('getIterator')->willReturn(new \ArrayIterator([$violation]));

        $this->validator->method('validate')
            ->willReturn($violationList);

        $result = $this->service->validateRegisterRequest($data);

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $result->getStatusCode());
        
        $content = json_decode($result->getContent(), true);
        $this->assertFalse($content['success']);
        $this->assertArrayHasKey('errors', $content);
        $this->assertEquals('This value is not a valid email address.', $content['errors']['email']);
    }

    public function testValidateLoginRequestWithValidData(): void
    {
        $data = [
            'username' => 'user@example.com',
            'password' => 'SecurePassword123!',
            'rememberMe' => true
        ];

        $this->validator->method('validate')
            ->willReturn(new ConstraintViolationList());

        $result = $this->service->validateLoginRequest($data);

        $this->assertInstanceOf(LoginRequest::class, $result);
        $this->assertEquals('user@example.com', $result->username);
        $this->assertEquals('SecurePassword123!', $result->password);
        $this->assertTrue($result->rememberMe);
    }

    public function testValidateLoginRequestWithDefaultRememberMe(): void
    {
        $data = [
            'username' => 'user@example.com',
            'password' => 'SecurePassword123!'
        ];

        $this->validator->method('validate')
            ->willReturn(new ConstraintViolationList());

        $result = $this->service->validateLoginRequest($data);

        $this->assertInstanceOf(LoginRequest::class, $result);
        $this->assertFalse($result->rememberMe);
    }

    public function testBuildRegistrationResponse(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(1);
        $user->method('getEmail')->willReturn('user@example.com');
        $user->method('getUsername')->willReturn(null);
        $user->method('getFirstname')->willReturn('John');
        $user->method('getLastname')->willReturn('Doe');
        $user->method('getFullName')->willReturn('John Doe');
        $user->method('getRoles')->willReturn(['ROLE_USER']);
        $user->method('isAdmin')->willReturn(false);
        $user->method('isActive')->willReturn(true);
        $user->method('getCreatedAt')->willReturn(new \DateTime('2024-01-01 10:00:00'));
        $user->method('getLastLoginAt')->willReturn(null);
        $user->method('getOrganization')->willReturn(null);

        $this->jwtService->method('generateAccessToken')->willReturn('access_token_123');
        $this->jwtService->method('generateRefreshToken')->willReturn('refresh_token_456');
        $this->jwtService->method('getAccessTokenExpiry')->willReturn(3600);
        $this->jwtService->method('getRefreshTokenExpiry')->willReturn(604800);

        $this->authService->expects($this->once())->method('updateLastLogin')->with($user);

        $response = $this->service->buildRegistrationResponse($user, 'Admin registration successful');

        $this->assertEquals('access_token_123', $response['accessToken']);
        $this->assertEquals('refresh_token_456', $response['refreshToken']);
        $this->assertEquals(3600, $response['accessTokenExpiry']);
        $this->assertEquals(604800, $response['refreshTokenExpiry']);
        $this->assertEquals('Admin registration successful', $response['message']);
        
        $userData = $response['userData'];
        $this->assertEquals(1, $userData['id']);
        $this->assertEquals('user@example.com', $userData['email']);
        $this->assertEquals('John', $userData['firstName']);
        $this->assertEquals('Doe', $userData['lastName']);
        $this->assertEquals('John Doe', $userData['fullName']);
    }

    public function testBuildRegistrationResponseWithOrganization(): void
    {
        $organization = $this->createMock(Organization::class);
        $organization->method('getId')->willReturn(10);
        $organization->method('getName')->willReturn('Test Organization');
        $organization->method('isActive')->willReturn(true);

        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(1);
        $user->method('getEmail')->willReturn('user@example.com');
        $user->method('getUsername')->willReturn(null);
        $user->method('getFirstname')->willReturn('John');
        $user->method('getLastname')->willReturn('Doe');
        $user->method('getFullName')->willReturn('John Doe');
        $user->method('getRoles')->willReturn(['ROLE_USER']);
        $user->method('isAdmin')->willReturn(false);
        $user->method('isActive')->willReturn(true);
        $user->method('getCreatedAt')->willReturn(new \DateTime('2024-01-01 10:00:00'));
        $user->method('getLastLoginAt')->willReturn(new \DateTime('2024-01-15 15:30:00'));
        $user->method('getOrganization')->willReturn($organization);

        $this->jwtService->method('generateAccessToken')->willReturn('access_token_123');
        $this->jwtService->method('generateRefreshToken')->willReturn('refresh_token_456');
        $this->jwtService->method('getAccessTokenExpiry')->willReturn(3600);
        $this->jwtService->method('getRefreshTokenExpiry')->willReturn(604800);

        $response = $this->service->buildRegistrationResponse($user);

        $userData = $response['userData'];
        $this->assertNotNull($userData['organization']);
        $this->assertEquals(10, $userData['organization']['id']);
        $this->assertEquals('Test Organization', $userData['organization']['name']);
        $this->assertTrue($userData['organization']['isActive']);
    }

    public function testBuildLoginResponseWithRememberMe(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(1);
        $user->method('getEmail')->willReturn('user@example.com');
        $user->method('getUsername')->willReturn(null);
        $user->method('getFirstname')->willReturn('John');
        $user->method('getLastname')->willReturn('Doe');
        $user->method('getFullName')->willReturn('John Doe');
        $user->method('getRoles')->willReturn(['ROLE_USER']);
        $user->method('isAdmin')->willReturn(false);
        $user->method('isActive')->willReturn(true);
        $user->method('getCreatedAt')->willReturn(new \DateTime('2024-01-01 10:00:00'));
        $user->method('getLastLoginAt')->willReturn(new \DateTime('2024-01-15 15:30:00'));
        $user->method('getOrganization')->willReturn(null);

        $this->jwtService->method('generateAccessToken')->willReturn('access_token_789');
        $this->jwtService->method('generateRefreshToken')->willReturn('refresh_token_101');
        $this->jwtService->method('getAccessTokenExpiry')->willReturn(3600);
        $this->jwtService->method('getRefreshTokenExpiry')->with(true)->willReturn(2592000);

        $this->authService->expects($this->once())->method('updateLastLogin')->with($user);

        $response = $this->service->buildLoginResponse($user, true);

        $this->assertEquals(2592000, $response['refreshTokenExpiry']); // 30 days
    }

    public function testGetUserFromTokenWithValidToken(): void
    {
        $user = $this->createMock(User::class);

        $this->jwtService->method('validateToken')
            ->with('valid_token')
            ->willReturn(['email' => 'user@example.com', 'sub' => 1]);

        $this->userRepository->method('findOneBy')
            ->with(['email' => 'user@example.com'])
            ->willReturn($user);

        $result = $this->service->getUserFromToken('valid_token');

        $this->assertSame($user, $result);
    }

    public function testGetUserFromTokenWithInvalidToken(): void
    {
        $this->jwtService->method('validateToken')
            ->with('invalid_token')
            ->willReturn(null);

        $result = $this->service->getUserFromToken('invalid_token');

        $this->assertNull($result);
        $this->userRepository->expects($this->never())->method('findOneBy');
    }

    public function testGetUserFromTokenWithNonExistentUser(): void
    {
        $this->jwtService->method('validateToken')
            ->with('valid_token')
            ->willReturn(['email' => 'nonexistent@example.com', 'sub' => 999]);

        $this->userRepository->method('findOneBy')
            ->with(['email' => 'nonexistent@example.com'])
            ->willReturn(null);

        $result = $this->service->getUserFromToken('valid_token');

        $this->assertNull($result);
    }
}
