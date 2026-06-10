<?php

namespace App\Service;

use App\DTO\LoginRequest;
use App\DTO\RegisterRequest;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * AuthResponseBuilderService - Builds authentication responses
 * Centralizes response building logic for both AuthController and AdminAuthController
 * Follows Single Responsibility and DRY principles
 */
class AuthResponseBuilderService
{
    public function __construct(
        private AuthService $authService,
        private JwtService $jwtService,
        private ValidatorInterface $validator,
        private UserRepository $userRepository,
    ) {
    }

    /**
     * Validate and parse JSON registration request
     */
    public function validateRegisterRequest(array $data): RegisterRequest|JsonResponse
    {
        $registerRequest = new RegisterRequest();
        $registerRequest->email = $data['email'] ?? '';
        $registerRequest->password = $data['password'] ?? '';
        $registerRequest->firstName = $data['firstName'] ?? '';
        $registerRequest->lastName = $data['lastName'] ?? '';

        $errors = $this->validator->validate($registerRequest);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return new JsonResponse([
                'success' => false,
                'errors' => $errorMessages
            ], Response::HTTP_BAD_REQUEST);
        }

        return $registerRequest;
    }

    /**
     * Validate and parse JSON login request
     */
    public function validateLoginRequest(array $data): LoginRequest|JsonResponse
    {
        $loginRequest = new LoginRequest();
        $loginRequest->username = $data['username'] ?? $data['identifier'] ?? $data['email'] ?? '';
        $loginRequest->password = $data['password'] ?? '';
        $loginRequest->rememberMe = $data['rememberMe'] ?? false;

        $errors = $this->validator->validate($loginRequest);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return new JsonResponse([
                'success' => false,
                'errors' => $errorMessages
            ], Response::HTTP_BAD_REQUEST);
        }

        return $loginRequest;
    }

    /**
     * Build successful registration response
     */
    public function buildRegistrationResponse(
        \App\Entity\User $user,
        string $message = 'Registration successful'
    ): array {
        $accessToken = $this->jwtService->generateAccessToken($user->getId(), $user->getEmail());
        $refreshToken = $this->jwtService->generateRefreshToken($user->getId(), $user->getEmail());

        $this->authService->updateLastLogin($user);

        return [
            'accessToken' => $accessToken,
            'refreshToken' => $refreshToken,
            'accessTokenExpiry' => $this->jwtService->getAccessTokenExpiry(),
            'refreshTokenExpiry' => $this->jwtService->getRefreshTokenExpiry(),
            'userData' => $this->formatUserData($user),
            'message' => $message,
        ];
    }

    /**
     * Build successful login response
     */
    public function buildLoginResponse(
        \App\Entity\User $user,
        bool $rememberMe = false,
        string $message = 'Login successful'
    ): array {
        $accessToken = $this->jwtService->generateAccessToken($user->getId(), $user->getEmail());
        $refreshToken = $this->jwtService->generateRefreshToken($user->getId(), $user->getEmail(), $rememberMe);

        $this->authService->updateLastLogin($user);

        return [
            'accessToken' => $accessToken,
            'refreshToken' => $refreshToken,
            'accessTokenExpiry' => $this->jwtService->getAccessTokenExpiry(),
            'refreshTokenExpiry' => $this->jwtService->getRefreshTokenExpiry($rememberMe),
            'userData' => $this->formatUserData($user),
            'message' => $message,
        ];
    }

    /**
     * Format user data for API response
     */
    private function formatUserData(\App\Entity\User $user): array
    {
        $userData = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'username' => $user->getUsername(),
            'firstName' => $user->getFirstname(),
            'lastName' => $user->getLastname(),
            'fullName' => $user->getFullName(),
            'roles' => $user->getRoles(),
            'isAdmin' => $user->isAdmin(),
            'isActive' => $user->isActive(),
            'createdAt' => $user->getCreatedAt()?->format('Y-m-d\TH:i:s\Z'),
            'lastLoginAt' => $user->getLastLoginAt()?->format('Y-m-d\TH:i:s\Z'),
        ];

        // Load organization data carefully to avoid lazy loading issues
        $organization = $user->getOrganization();
        if ($organization) {
            try {
                // Initialize the proxy if needed
                $organization->getId();
                $userData['organization'] = [
                    'id' => $organization->getId(),
                    'name' => $organization->getName(),
                    'isActive' => $organization->isActive(),
                ];
            } catch (\Exception $e) {
                $userData['organization'] = null;
            }
        }

        return $userData;
    }

    /**
     * Get user from validated JWT token
     */
    public function getUserFromToken(string $token): \App\Entity\User|null
    {
        $payload = $this->jwtService->validateToken($token);

        if (!$payload) {
            return null;
        }

        return $this->userRepository->findOneBy(['email' => $payload['email']]);
    }
}
