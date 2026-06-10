<?php

namespace App\Tests\Service;

use App\Service\AmsApiService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class AmsApiServiceTest extends TestCase
{
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;
    private AmsApiService $amsApiService;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->amsApiService = new AmsApiService(
            $this->httpClient,
            $this->logger,
            'https://ams-api.example.com',
            'test_db',
            'api_user',
            'api_password'
        );
    }

    public function testAuthenticateReturnsTrue(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
            ->method('toArray')
            ->willReturn(['token' => 'test-token-123']);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                'https://ams-api.example.com/Login',
                [
                    'auth_basic' => ['api_user', 'api_password'],
                    'headers' => ['serverDB' => 'test_db'],
                ]
            )
            ->willReturn($response);

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Successfully authenticated with AMS API');

        $result = $this->amsApiService->authenticate();

        $this->assertTrue($result);
        $this->assertTrue($this->amsApiService->hasToken());
    }

    public function testAuthenticateReturnsFalseWhenNoToken(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
            ->method('toArray')
            ->willReturn([]); // No token in response

        $this->httpClient->expects($this->once())
            ->method('request')
            ->willReturn($response);

        $this->logger->expects($this->once())
            ->method('error')
            ->with('No token received from AMS API', $this->anything());

        $result = $this->amsApiService->authenticate();

        $this->assertFalse($result);
        $this->assertFalse($this->amsApiService->hasToken());
    }

    public function testValidateUserCredentialsReturnsTrue(): void
    {
        $username = 'testuser';
        $password = 'user-password';

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(200);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                'https://ams-api.example.com/Login',
                [
                    'auth_basic' => [$username, $password],
                    'headers' => ['serverDB' => 'test_db'],
                ]
            )
            ->willReturn($response);

        $this->logger->expects($this->once())
            ->method('info')
            ->with('AMS user authentication successful', ['username' => $username]);

        $result = $this->amsApiService->validateUserCredentials($username, $password);

        $this->assertTrue($result);
    }

    public function testValidateUserCredentialsReturnsFalseOnNon200Response(): void
    {
        $username = 'testuser';
        $password = 'wrong-password';

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(401);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->willReturn($response);

        $this->logger->expects($this->once())
            ->method('warning')
            ->with(
                'AMS user authentication failed',
                ['username' => $username, 'status_code' => 401]
            );

        $result = $this->amsApiService->validateUserCredentials($username, $password);

        $this->assertFalse($result);
    }

    public function testValidateUserCredentialsReturnsFalseOnException(): void
    {
        $username = 'testuser';
        $password = 'user-password';

        $this->httpClient->expects($this->once())
            ->method('request')
            ->willThrowException(new \Exception('Network error'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'AMS user authentication error',
                [
                    'username' => $username,
                    'exception' => 'Network error',
                ]
            );

        $result = $this->amsApiService->validateUserCredentials($username, $password);

        $this->assertFalse($result);
    }

    public function testGetCompaniesReturnsEmptyArrayWhenNoToken(): void
    {
        $this->logger->expects($this->once())
            ->method('warning')
            ->with('No token available for AMS API request');

        $result = $this->amsApiService->getCompanies();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testGetUsersReturnsEmptyArrayWhenNoToken(): void
    {
        $this->logger->expects($this->once())
            ->method('warning')
            ->with('No token available for AMS API request');

        $result = $this->amsApiService->getUsers();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testClearToken(): void
    {
        // First authenticate to get a token
        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn(['token' => 'test-token']);
        $this->httpClient->method('request')->willReturn($response);
        
        $this->amsApiService->authenticate();
        $this->assertTrue($this->amsApiService->hasToken());

        // Clear the token
        $this->amsApiService->clearToken();
        $this->assertFalse($this->amsApiService->hasToken());
    }
}
