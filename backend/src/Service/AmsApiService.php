<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Psr\Log\LoggerInterface;

class AmsApiService
{
    private ?string $token = null;
    private string $baseUrl;
    private string $apiDb;
    private string $apiUser;
    private string $apiPassword;

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        string $amsApiUrl,
        string $amsApiDb,
        string $amsApiUser,
        string $amsApiPassword,
    ) {
        $this->baseUrl = rtrim($amsApiUrl, '/');
        $this->apiDb = $amsApiDb;
        $this->apiUser = $amsApiUser;
        $this->apiPassword = $amsApiPassword;
    }

    /**
     * Make a request to the Login endpoint
     *
     * @param string $username Username or email
     * @param string $password Password
     * @return array Response data with statusCode and data
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws RedirectExceptionInterface
     * @throws ServerExceptionInterface
     */
    private function loginRequest(string $username, string $password): array
    {
        $response = $this->httpClient->request(
            'GET',
            $this->baseUrl . '/Login',
            [
                'auth_basic' => [$username, $password],
                'headers' => [
                    'serverDB' => $this->apiDb,
                ],
            ]
        );

        return [
            'statusCode' => $response->getStatusCode(),
            'data' => $response->toArray(),
        ];
    }

    /**
     * Authenticate with AMS API and retrieve token
     *
     * @return bool True if authentication was successful
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws RedirectExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function authenticate(): bool
    {
        try {
            $response = $this->loginRequest($this->apiUser, $this->apiPassword);
            $data = $response['data'];
            
            if (!isset($data['token'])) {
                $this->logger->error('No token received from AMS API', ['response' => $data]);
                return false;
            }

            $this->token = $data['token'];
            $this->logger->info('Successfully authenticated with AMS API');
            return true;
        } catch (\Exception $e) {
            $this->logger->error('AMS API authentication failed', [
                'exception' => $e->getMessage(),
                'url' => $this->baseUrl . '/Login',
            ]);
            return false;
        }
    }

    /**
     * Parse API response handling both array and paginated responses
     *
     * @param array $data Response data from API
     * @return array Parsed data
     */
    private function parseApiResponse(array $data): array
    {
        // Handle both array and paginated responses
        if (isset($data['data'])) {
            $companies = $data['data'];
            // Handle nested paginated response: {data: {data: [...], meta: {...}}}
            if (is_array($companies) && isset($companies['data'])) {
                return is_array($companies['data']) ? $companies['data'] : [];
            }
            return is_array($companies) ? $companies : [];
        }

        return is_array($data) ? $data : [];
    }

    /**
     * Get list of companies from AMS API
     *
     * @return array<int, array{compid: string, compfullname: string}> List of companies
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws RedirectExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function getCompanies(): array
    {
        if (!$this->token) {
            $this->logger->warning('No token available for AMS API request');
            return [];
        }

        try {
            $response = $this->httpClient->request(
                'GET',
                $this->baseUrl . '/v1/ttiercomp',
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->token,
                        'serverDB' => $this->apiDb,
                    ],
                ]
            );

            $data = $response->toArray();
            return $this->parseApiResponse($data);
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch companies from AMS API', [
                'exception' => $e->getMessage(),
                'url' => $this->baseUrl . '/v1/ttiercomp',
            ]);
            return [];
        }
    }

    /**
     * Get list of users from AMS API
     *
     * @return array<int, array{uemail: string, name: string, namefull: string, compid: string}> List of users
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws RedirectExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function getUsers(): array
    {
        if (!$this->token) {
            $this->logger->warning('No token available for AMS API request');
            return [];
        }

        try {
            $response = $this->httpClient->request(
                'GET',
                $this->baseUrl . '/v1/user',
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->token,
                        'serverDB' => $this->apiDb,
                    ],
                ]
            );

            $data = $response->toArray();
            return $this->parseApiResponse($data);
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch users from AMS API', [
                'exception' => $e->getMessage(),
                'url' => $this->baseUrl . '/v1/user',
            ]);
            return [];
        }
    }

    /**
     * Check if token is available
     */
    public function hasToken(): bool
    {
        return $this->token !== null;
    }

    /**
     * Reset token
     */
    public function clearToken(): void
    {
        $this->token = null;
    }

    /**
     * Validate user credentials against AMS API
     * Uses the /Login endpoint (without /v1/ prefix) to verify user credentials
     *
     * @param string $username User username (or email as fallback)
     * @param string $password User password
     * @return bool True if credentials are valid
     */
    public function validateUserCredentials(string $username, string $password): bool
    {
        try {
            $response = $this->loginRequest($username, $password);
            
            // If the response is HTTP 200 OK, credentials are valid
            if ($response['statusCode'] === 200) {
                $this->logger->info('AMS user authentication successful', ['username' => $username]);
                return true;
            }

            $this->logger->warning('AMS user authentication failed', [
                'username' => $username,
                'status_code' => $response['statusCode'],
            ]);
            return false;
        } catch (\Exception $e) {
            $this->logger->error('AMS user authentication error', [
                'username' => $username,
                'exception' => $e->getMessage(),
            ]);
            return false;
        }
    }
}