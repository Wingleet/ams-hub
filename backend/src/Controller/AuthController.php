<?php

namespace App\Controller;

use App\Attribute\RateLimit;
use App\Enum\UserRole;
use App\Repository\UserRepository;
use App\Service\AuthResponseBuilderService;
use App\Service\AuthService;
use App\Trait\SecureCookieTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * AuthController - Unified authentication controller for both users and admins
 * 
 * Public routes: /api/auth/*
 * Admin routes: /api/admin/auth/* (with @IsGranted)
 */
#[Route('/api/auth')]
class AuthController extends AbstractController
{
    use SecureCookieTrait;

    public function __construct(
        private AuthService $authService,
        private AuthResponseBuilderService $authResponseBuilder,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Determine if the current request is for an admin route
     */
    private function isAdminRoute(Request $request): bool
    {
        return str_contains($request->getPathInfo(), '/api/admin/auth/');
    }

    /**
     * Unified registration - POST /api/auth/register and /api/admin/auth/register
     */
    #[Route('/register', name: 'api_auth_register', methods: ['POST'])]
    #[Route('/admin/auth/register', name: 'api_admin_auth_register', methods: ['POST'])]
    #[RateLimit(limiter: 'login_limiter')]
    public function register(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            if (!$data) {
                return $this->json([
                    'success' => false,
                    'message' => 'Invalid JSON'
                ], Response::HTTP_BAD_REQUEST);
            }

            $registerRequest = $this->authResponseBuilder->validateRegisterRequest($data);
            if ($registerRequest instanceof JsonResponse) {
                return $registerRequest;
            }

            $isAdminRoute = $this->isAdminRoute($request);

            $user = $this->authService->register(
                $registerRequest->email,
                $registerRequest->password,
                $registerRequest->firstName,
                $registerRequest->lastName
            );

            // Add ADMIN role if admin registration route
            if ($isAdminRoute) {
                $user->addRole(UserRole::ADMIN);
                $this->entityManager->persist($user);
                $this->entityManager->flush();
            }

            $successMessage = $isAdminRoute ? 'Admin registration successful' : 'Registration successful';
            $responseData = $this->authResponseBuilder->buildRegistrationResponse($user, $successMessage);

            $response = $this->json([
                'success' => true,
                'message' => $responseData['message'],
                'user' => $responseData['userData']
            ], Response::HTTP_CREATED);

            $response->headers->setCookie(
                $this->createSecureCookie('access_token', $responseData['accessToken'], $responseData['accessTokenExpiry'])
            );
            $response->headers->setCookie(
                $this->createSecureCookie('refresh_token', $responseData['refreshToken'], $responseData['refreshTokenExpiry'])
            );

            return $response;
        } catch (\InvalidArgumentException $e) {
            return $this->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_CONFLICT);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'An error occurred during registration'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Unified login - POST /api/auth/login and /api/admin/auth/login
     */
    #[Route('/login', name: 'api_auth_login', methods: ['POST'])]
    #[Route('/admin/auth/login', name: 'api_admin_auth_login', methods: ['POST'])]
    #[RateLimit(limiter: 'login_limiter')]
    public function login(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            if (!$data) {
                return $this->json([
                    'success' => false,
                    'message' => 'Invalid JSON'
                ], Response::HTTP_BAD_REQUEST);
            }

            $loginRequest = $this->authResponseBuilder->validateLoginRequest($data);
            if ($loginRequest instanceof JsonResponse) {
                return $loginRequest;
            }

            $user = $this->authService->verifyCredentials($loginRequest->username, $loginRequest->password);

            if (!$user) {
                return $this->json([
                    'success' => false,
                    'message' => 'Invalid credentials'
                ], Response::HTTP_UNAUTHORIZED);
            }

            $isAdminRoute = $this->isAdminRoute($request);

            if ($isAdminRoute && !$user->isAdmin()) {
                return $this->json([
                    'success' => false,
                    'message' => 'Access denied. Admin privileges are required to access the admin portal.'
                ], Response::HTTP_FORBIDDEN);
            }

            if (!$isAdminRoute && $user->isAdmin()) {
                return $this->json([
                    'success' => false,
                    'message' => 'Admin users must log in via the admin portal.'
                ], Response::HTTP_FORBIDDEN);
            }

            $responseData = $this->authResponseBuilder->buildLoginResponse(
                $user,
                $loginRequest->rememberMe
            );

            $response = $this->json([
                'success' => true,
                'message' => $responseData['message'],
                'user' => $responseData['userData']
            ]);

            $response->headers->setCookie(
                $this->createSecureCookie('access_token', $responseData['accessToken'], $responseData['accessTokenExpiry'])
            );
            $response->headers->setCookie(
                $this->createSecureCookie('refresh_token', $responseData['refreshToken'], $responseData['refreshTokenExpiry'])
            );

            return $response;
        } catch (\Exception $e) {
            // Log the full error for debugging
            error_log('Login error: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            
            return $this->json([
                'success' => false,
                'message' => 'An error occurred during login'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Logout - POST /api/auth/logout and /api/admin/auth/logout
     */
    #[Route('/logout', name: 'api_auth_logout', methods: ['POST'])]
    #[Route('/admin/auth/logout', name: 'api_admin_auth_logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
        $response = $this->json([
            'success' => true,
            'message' => 'Logout successful'
        ]);

        $response->headers->setCookie($this->createSecureCookie('access_token', '', -1));
        $response->headers->setCookie($this->createSecureCookie('refresh_token', '', -1));

        return $response;
    }

    /**
     * Get current user - GET /api/auth/me and /api/admin/auth/me
     */
    #[Route('/me', name: 'api_auth_me', methods: ['GET'])]
    #[Route('/admin/auth/me', name: 'api_admin_auth_me', methods: ['GET'])]
    public function me(Request $request): JsonResponse
    {
        try {
            $accessToken = $request->cookies->get('access_token');

            if (!$accessToken) {
                return $this->json([
                    'success' => false,
                    'message' => 'Not authenticated'
                ], Response::HTTP_UNAUTHORIZED);
            }

            $user = $this->authResponseBuilder->getUserFromToken($accessToken);
            
            if (!$user) {
                return $this->json([
                    'success' => false,
                    'message' => 'User not found'
                ], Response::HTTP_NOT_FOUND);
            }

            // For admin route, check admin role
            if ($this->isAdminRoute($request) && !$user->isAdmin()) {
                return $this->json([
                    'success' => false,
                    'message' => 'Access denied. Admin privileges required.'
                ], Response::HTTP_FORBIDDEN);
            }

            return $this->json([
                'success' => true,
                'user' => $this->authResponseBuilder->buildLoginResponse($user)['userData']
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'An error occurred'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Refresh token - POST /api/auth/refresh and /api/admin/auth/refresh
     */
    #[Route('/refresh', name: 'api_auth_refresh', methods: ['POST'])]
    #[Route('/admin/auth/refresh', name: 'api_admin_auth_refresh', methods: ['POST'])]
    #[RateLimit(limiter: 'api_limiter')]
    public function refresh(Request $request): JsonResponse
    {
        try {
            $refreshToken = $request->cookies->get('refresh_token');

            if (!$refreshToken) {
                return $this->json([
                    'success' => false,
                    'message' => 'No refresh token found'
                ], Response::HTTP_UNAUTHORIZED);
            }

            $user = $this->authResponseBuilder->getUserFromToken($refreshToken);
            
            if (!$user) {
                return $this->json([
                    'success' => false,
                    'message' => 'Invalid refresh token'
                ], Response::HTTP_UNAUTHORIZED);
            }

            // For admin route, verify admin role and user is active
            if ($this->isAdminRoute($request)) {
                if (!$user->isAdmin() || !$user->isActive()) {
                    return $this->json([
                        'success' => false,
                        'message' => 'User not found or not authorized'
                    ], Response::HTTP_UNAUTHORIZED);
                }
            }

            $responseData = $this->authResponseBuilder->buildLoginResponse($user);

            $response = $this->json([
                'success' => true,
                'message' => 'Token refreshed'
            ]);

            $response->headers->setCookie(
                $this->createSecureCookie('access_token', $responseData['accessToken'], $responseData['accessTokenExpiry'])
            );

            return $response;
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'An error occurred'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}