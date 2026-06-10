import { apiFetch } from "./apiClient";
import type { ApiResponse } from "../types";

/**
 * Generic HTTP request handler for API calls
 * Handles common response formats and error cases
 */
export async function makeApiRequest<T = any>(
  endpoint: string,
  options?: RequestInit,
): Promise<ApiResponse<T>> {
  try {
    const response = await apiFetch(endpoint, options);

    // Handle 204 No Content (successful deletion, no body)
    if (response.status === 204) {
      return {
        success: true,
      };
    }

    const contentType = response.headers.get("content-type");

    if (contentType && contentType.includes("json")) {
      const data = await response.json();

      if (!response.ok) {
        // Handle both API Platform error format and custom auth format
        const errors =
          data.errors ||
          data.violations?.reduce((acc: Record<string, string>, v: any) => {
            acc[v.propertyPath] = v.message;
            return acc;
          }, {});

        return {
          success: false,
          message:
            data["hydra:description"] ||
            data.detail ||
            data.message ||
            "An error occurred",
          errors,
        };
      }

      // Check if response is already in our API format {success, data, message}
      if (data.success !== undefined) {
        return data;
      }

      // API Platform returns data directly, not wrapped in {success, data}
      return {
        success: true,
        data,
      };
    } else if (response.ok) {
      // Success but no JSON content (empty response)
      return {
        success: true,
      };
    } else {
      const text = await response.text();
      console.error("Expected JSON but got:", text.substring(0, 200));
      return {
        success: false,
        message: "Invalid response format from server",
      };
    }
  } catch (error) {
    console.error("API request failed:", error);
    return {
      success: false,
      message: error instanceof Error ? error.message : "Unknown error",
    };
  }
}
