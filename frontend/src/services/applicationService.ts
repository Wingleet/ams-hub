import { makeApiRequest } from "./apiService";
import type { Application, ApiResponse } from "../types";

class ApplicationService {
  /**
   * Get all applications using ApiPlatform route
   * GET /api/applications
   */
  async getApplications(): Promise<ApiResponse<Application[]>> {
    const result = await makeApiRequest<
      Record<string, unknown> | Application[]
    >("/api/applications");

    if (result.success && result.data) {
      // Handle API Platform collection format (member)
      const data = result.data as Record<string, unknown>;

      // Check if data has member property (JSON-LD format)
      if (data["member"] || (data as any).member) {
        const applications = (data["member"] ||
          (data as any).member) as Application[];
        return {
          success: true,
          data: Array.isArray(applications) ? applications : [],
        };
      }

      // If data is already an array
      if (Array.isArray(result.data)) {
        return {
          success: true,
          data: result.data as Application[],
        };
      }

      // Fallback: return empty array
      return {
        success: true,
        data: [],
      };
    }

    return {
      success: false,
      message: result.message || "Failed to fetch applications",
    };
  }

  /**
   * Get user's applications with subscription status
   * Fetches all applications and user's subscriptions, then merges the data
   */
  async getMyApplications(
    organizationId?: number,
  ): Promise<ApiResponse<Application[]>> {
    try {
      // Get all applications
      const appsResult = await this.getApplications();

      if (!appsResult.success || !appsResult.data) {
        return appsResult;
      }

      // If no organization ID provided, return all apps without subscription info
      if (!organizationId) {
        return {
          success: true,
          data: appsResult.data.map((app) => ({ ...app, isSubscribed: false })),
        };
      }

      // Get organization's active subscriptions
      const subscriptionsUrl = `/api/subscriptions?organization=${organizationId}&isActive=true`;

      const subscriptionsResult = await makeApiRequest<
        Record<string, unknown> | unknown[]
      >(subscriptionsUrl);

      if (subscriptionsResult.success && subscriptionsResult.data) {
        // Handle both array and API Platform collection format
        let subscriptions: Array<{ application: { id: number } }> = [];

        if (Array.isArray(subscriptionsResult.data)) {
          // Direct array response
          subscriptions = subscriptionsResult.data as Array<{
            application: { id: number };
          }>;
        } else {
          // API Platform collection format with member property
          const data = subscriptionsResult.data as Record<string, unknown>;
          subscriptions =
            (data["member"] as Array<{
              application: { id: number };
            }>) || [];
        }

        // Create a Set of subscribed application IDs for fast lookup
        const subscribedAppIds = new Set(
          subscriptions.map((sub) => sub.application.id),
        );

        // Merge subscription status with applications
        const appsWithStatus = appsResult.data.map((app) => ({
          ...app,
          isSubscribed: subscribedAppIds.has(app.id),
        }));

        return {
          success: true,
          data: appsWithStatus,
        };
      }

      // If subscriptions fetch failed, return apps without subscription info
      return {
        success: true,
        data: appsResult.data.map((app) => ({ ...app, isSubscribed: false })),
      };
    } catch (error) {
      return {
        success: false,
        message: "Failed to fetch applications",
      };
    }
  }
}

export const applicationService = new ApplicationService();
