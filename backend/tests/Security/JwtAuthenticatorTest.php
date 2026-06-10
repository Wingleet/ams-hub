<?php

namespace App\Tests\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\JwtAuthenticator;
use App\Service\JwtService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

class JwtAuthenticatorTest extends TestCase
{
    private JwtService $jwtService;
    private UserRepository $userRepository;
    private JwtAuthenticator $jwtAuthenticator;

    protected function setUp(): void
    {
        $this->jwtService = $this->createMock(JwtService::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->jwtAuthenticator = new JwtAuthenticator($this->jwtService, $this->userRepository);
    }

    public function testSupportsApiRoutes(): void
    {
        $request = new Request();
        $request->server->set('REQUEST_URI', '/api/users');

        $this->assertTrue($this->jwtAuthenticator->supports($request));
    }

    public function testDoesNotSupportAuthRoutes(): void
    {
        $request = new Request();
        $request->server->set('REQUEST_URI', '/api/auth/login');

        $this->assertFalse($this->jwtAuthenticator->supports($request));
    }

    public function testDoesNotSupportAdminAuthRoutes(): void
    {
        $request = new Request();
        $request->server->set('REQUEST_URI', '/api/admin/auth/login');

        $this->assertFalse($this->jwtAuthenticator->supports($request));
    }

    public function testDoesNotSupportNonApiRoutes(): void
    {
        $request = new Request();
        $request->server->set('REQUEST_URI', '/home');

        $this->assertFalse($this->jwtAuthenticator->supports($request));
    }

    public function testAuthenticateThrowsExceptionWhenNoToken(): void
    {
        $request = new Request();

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('No access token provided');

        $this->jwtAuthenticator->authenticate($request);
    }

    public function testAuthenticateThrowsExceptionWhenTokenInvalid(): void
    {
        $request = new Request();
        $request->cookies->set('access_token', 'invalid_token');

        $this->jwtService->expects($this->once())
            ->method('validateToken')
            ->with('invalid_token')
            ->willReturn(null);

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Invalid or expired token');

        $this->jwtAuthenticator->authenticate($request);
    }

    public function testAuthenticateThrowsExceptionWhenTokenMissingUserId(): void
    {
        $request = new Request();
        $request->cookies->set('access_token', 'valid_token');

        $this->jwtService->expects($this->once())
            ->method('validateToken')
            ->with('valid_token')
            ->willReturn(['email' => 'test@example.com']); // Missing user_id

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Invalid token payload');

        $this->jwtAuthenticator->authenticate($request);
    }

    public function testAuthenticateSuccessfully(): void
    {
        $request = new Request();
        $request->cookies->set('access_token', 'valid_token');

        $user = new User();
        $user->setEmail('test@example.com');
        $user->setIsActive(true);

        $this->jwtService->expects($this->once())
            ->method('validateToken')
            ->with('valid_token')
            ->willReturn(['user_id' => 1, 'email' => 'test@example.com']);

        $passport = $this->jwtAuthenticator->authenticate($request);

        $this->assertNotNull($passport);
        $this->assertInstanceOf(\Symfony\Component\Security\Http\Authenticator\Passport\Passport::class, $passport);
    }

    public function testOnAuthenticationSuccessReturnsNull(): void
    {
        $request = new Request();
        $token = $this->createMock(\Symfony\Component\Security\Core\Authentication\Token\TokenInterface::class);

        $result = $this->jwtAuthenticator->onAuthenticationSuccess($request, $token, 'main');

        $this->assertNull($result);
    }

    public function testOnAuthenticationFailureReturnsJsonResponse(): void
    {
        $request = new Request();
        $exception = new CustomUserMessageAuthenticationException('Test error');

        $response = $this->jwtAuthenticator->onAuthenticationFailure($request, $exception);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertFalse($content['success']);
        $this->assertArrayHasKey('message', $content);
        $this->assertArrayHasKey('detail', $content);
    }
}
