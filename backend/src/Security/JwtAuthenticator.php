<?php

namespace App\Security;

use App\Repository\UserRepository;
use App\Service\JwtService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class JwtAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private JwtService $jwtService,
        private UserRepository $userRepository
    ) {
    }

    public function supports(Request $request): ?bool
    {
        // Support authentication for API routes (except auth routes) and SSO routes
        $isApiRoute = str_starts_with($request->getPathInfo(), '/api') 
            && !str_starts_with($request->getPathInfo(), '/api/auth')
            && !str_starts_with($request->getPathInfo(), '/api/admin/auth');
        
        $isSsoRoute = str_starts_with($request->getPathInfo(), '/sso');
        
        return $isApiRoute || $isSsoRoute;
    }

    public function authenticate(Request $request): Passport
    {
        // Try to get token from cookie
        $token = $request->cookies->get('access_token');

        if (!$token) {
            throw new CustomUserMessageAuthenticationException('No access token provided');
        }

        // Validate token
        $payload = $this->jwtService->validateToken($token);

        if (!$payload) {
            throw new CustomUserMessageAuthenticationException('Invalid or expired token');
        }

        if (!isset($payload['user_id'])) {
            throw new CustomUserMessageAuthenticationException('Invalid token payload');
        }

        return new SelfValidatingPassport(
            new UserBadge($payload['user_id'], function ($userId) {
                $user = $this->userRepository->find($userId);
                
                if (!$user) {
                    throw new CustomUserMessageAuthenticationException('User not found');
                }

                if (!$user->isActive()) {
                    throw new CustomUserMessageAuthenticationException('User account is inactive');
                }

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Let the request continue
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        // For SSO routes, redirect to login instead of returning JSON
        if (str_starts_with($request->getPathInfo(), '/sso')) {
            // Redirect to frontend login page
            return new RedirectResponse(
                'http://localhost:3000/login?error=authentication_required&redirect=' . urlencode($request->getUri())
            );
        }
        
        return new JsonResponse([
            'success' => false,
            'message' => 'Full authentication is required to access this resource.',
            'detail' => $exception->getMessage()
        ], Response::HTTP_UNAUTHORIZED);
    }
}
