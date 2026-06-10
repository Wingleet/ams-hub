<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * ApiResponseService - Standardized API response formatter
 * 
 * Ensures all API endpoints return consistent response structures,
 * reducing duplication and improving frontend reliability.
 */
class ApiResponseService
{
    /**
     * Success response with data
     */
    public static function success(
        mixed $data = null,
        string $message = 'Operation successful',
        int $statusCode = Response::HTTP_OK
    ): JsonResponse {
        return new JsonResponse([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }

    /**
     * Error response
     */
    public static function error(
        string $message = 'An error occurred',
        mixed $data = null,
        int $statusCode = Response::HTTP_BAD_REQUEST
    ): JsonResponse {
        return new JsonResponse([
            'success' => false,
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }

    /**
     * Validation error response
     */
    public static function validationError(
        array $violations,
        string $message = 'Validation failed'
    ): JsonResponse {
        return new JsonResponse([
            'success' => false,
            'message' => $message,
            'violations' => $violations,
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * Not found response
     */
    public static function notFound(
        string $message = 'Resource not found'
    ): JsonResponse {
        return new JsonResponse([
            'success' => false,
            'message' => $message,
        ], Response::HTTP_NOT_FOUND);
    }

    /**
     * Unauthorized response
     */
    public static function unauthorized(
        string $message = 'Unauthorized'
    ): JsonResponse {
        return new JsonResponse([
            'success' => false,
            'message' => $message,
        ], Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Forbidden response
     */
    public static function forbidden(
        string $message = 'Forbidden'
    ): JsonResponse {
        return new JsonResponse([
            'success' => false,
            'message' => $message,
        ], Response::HTTP_FORBIDDEN);
    }

    /**
     * Server error response
     */
    public static function serverError(
        string $message = 'An internal server error occurred'
    ): JsonResponse {
        return new JsonResponse([
            'success' => false,
            'message' => $message,
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * Created response (201)
     */
    public static function created(
        mixed $data = null,
        string $message = 'Resource created successfully'
    ): JsonResponse {
        return new JsonResponse([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], Response::HTTP_CREATED);
    }

    /**
     * No content response (204)
     */
    public static function noContent(): JsonResponse
    {
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
