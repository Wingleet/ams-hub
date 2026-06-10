<?php

namespace App\Tests\Service;

use App\Service\ApiResponseService;
use Symfony\Component\HttpFoundation\Response;
use PHPUnit\Framework\TestCase;

class ApiResponseServiceTest extends TestCase
{
    public function testSuccessResponseWithData(): void
    {
        $data = ['id' => 1, 'name' => 'Test'];
        $response = ApiResponseService::success($data, 'Success message', Response::HTTP_OK);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        
        $this->assertTrue($content['success']);
        $this->assertEquals('Success message', $content['message']);
        $this->assertEquals($data, $content['data']);
    }

    public function testSuccessResponseWithoutData(): void
    {
        $response = ApiResponseService::success();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        
        $this->assertTrue($content['success']);
        $this->assertEquals('Operation successful', $content['message']);
        $this->assertNull($content['data']);
    }

    public function testSuccessResponseWithCustomStatusCode(): void
    {
        $response = ApiResponseService::success(['id' => 1], 'Created', Response::HTTP_CREATED);

        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertTrue($content['success']);
    }

    public function testErrorResponse(): void
    {
        $response = ApiResponseService::error('Something went wrong');

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        
        $this->assertFalse($content['success']);
        $this->assertEquals('Something went wrong', $content['message']);
        $this->assertNull($content['data']);
    }

    public function testErrorResponseWithData(): void
    {
        $data = ['reason' => 'Invalid input'];
        $response = ApiResponseService::error('Validation failed', $data, Response::HTTP_UNPROCESSABLE_ENTITY);

        $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        
        $this->assertFalse($content['success']);
        $this->assertEquals('Validation failed', $content['message']);
        $this->assertEquals($data, $content['data']);
    }

    public function testValidationErrorResponse(): void
    {
        $violations = [
            'email' => 'Invalid email address',
            'password' => 'Password must be at least 8 characters'
        ];
        $response = ApiResponseService::validationError($violations, 'Form validation failed');

        $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        
        $this->assertFalse($content['success']);
        $this->assertEquals('Form validation failed', $content['message']);
        $this->assertEquals($violations, $content['violations']);
    }

    public function testValidationErrorResponseWithDefaultMessage(): void
    {
        $violations = ['field' => 'error'];
        $response = ApiResponseService::validationError($violations);

        $content = json_decode($response->getContent(), true);
        $this->assertEquals('Validation failed', $content['message']);
    }

    public function testNotFoundResponse(): void
    {
        $response = ApiResponseService::notFound('User not found');

        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        
        $this->assertFalse($content['success']);
        $this->assertEquals('User not found', $content['message']);
    }

    public function testNotFoundResponseWithDefaultMessage(): void
    {
        $response = ApiResponseService::notFound();

        $content = json_decode($response->getContent(), true);
        $this->assertEquals('Resource not found', $content['message']);
    }

    public function testUnauthorizedResponse(): void
    {
        $response = ApiResponseService::unauthorized('Invalid credentials');

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        
        $this->assertFalse($content['success']);
        $this->assertEquals('Invalid credentials', $content['message']);
    }

    public function testUnauthorizedResponseWithDefaultMessage(): void
    {
        $response = ApiResponseService::unauthorized();

        $content = json_decode($response->getContent(), true);
        $this->assertEquals('Unauthorized', $content['message']);
    }

    public function testForbiddenResponse(): void
    {
        $response = ApiResponseService::forbidden('Access denied');

        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        
        $this->assertFalse($content['success']);
        $this->assertEquals('Access denied', $content['message']);
    }

    public function testForbiddenResponseWithDefaultMessage(): void
    {
        $response = ApiResponseService::forbidden();

        $content = json_decode($response->getContent(), true);
        $this->assertEquals('Forbidden', $content['message']);
    }

    public function testServerErrorResponse(): void
    {
        $response = ApiResponseService::serverError('Database connection failed');

        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        
        $this->assertFalse($content['success']);
        $this->assertEquals('Database connection failed', $content['message']);
    }

    public function testServerErrorResponseWithDefaultMessage(): void
    {
        $response = ApiResponseService::serverError();

        $content = json_decode($response->getContent(), true);
        $this->assertEquals('An internal server error occurred', $content['message']);
    }

    public function testCreatedResponse(): void
    {
        $data = ['id' => 1, 'name' => 'New User'];
        $response = ApiResponseService::created($data, 'User created successfully');

        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        
        $this->assertTrue($content['success']);
        $this->assertEquals('User created successfully', $content['message']);
        $this->assertEquals($data, $content['data']);
    }

    public function testCreatedResponseWithoutData(): void
    {
        $response = ApiResponseService::created();

        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        
        $this->assertTrue($content['success']);
        $this->assertEquals('Resource created successfully', $content['message']);
        $this->assertNull($content['data']);
    }

    public function testNoContentResponse(): void
    {
        $response = ApiResponseService::noContent();

        $this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        // 204 responses should be empty or have no content
        $content = $response->getContent();
        $this->assertIsString($content);
    }

    public function testResponseHeadersAndContentType(): void
    {
        $response = ApiResponseService::success(['id' => 1]);

        // Verify content type
        $this->assertStringContainsString('application/json', $response->headers->get('Content-Type'));
    }

    public function testSuccessResponseWithArrayData(): void
    {
        $data = [
            ['id' => 1, 'name' => 'Item 1'],
            ['id' => 2, 'name' => 'Item 2'],
            ['id' => 3, 'name' => 'Item 3']
        ];
        $response = ApiResponseService::success($data, 'Items retrieved');

        $content = json_decode($response->getContent(), true);
        $this->assertEquals($data, $content['data']);
    }
}
