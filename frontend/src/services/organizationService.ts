import { makeApiRequest } from "./apiService";
import type { ApiResponse } from "../types";
import type {
  OrganizationMember,
  CreateOrganizationUserData,
} from "../types/organization.types";
import type { Organization } from "../types";

class OrganizationService {
  /**
   * Get current user's organization
   * First get the user, then get their organization
   */
  async getMyOrganization(): Promise<ApiResponse<Organization>> {
    // Get current user to know their organization ID
    const userResult = await makeApiRequest<{ organization?: Organization }>(
      "/api/auth/me",
    );

    if (!userResult.success || !userResult.data?.organization) {
      return {
        success: false,
        message: "No organization found for current user",
      };
    }

    return {
      success: true,
      data: userResult.data.organization,
    };
  }

  /**
   * Get organization members
   * Filter users by organization using API Platform
   */
  async getOrganizationMembers(
    organizationId: number,
  ): Promise<ApiResponse<OrganizationMember[]>> {
    const result = await makeApiRequest<
      Record<string, unknown> | OrganizationMember[]
    >(`/api/users?organization.id=${organizationId}`);

    if (result.success && result.data) {
      // Handle API Platform collection format (hydra:member)
      const data = result.data as Record<string, unknown>;
      const members =
        (data["hydra:member"] as OrganizationMember[]) || result.data;
      return {
        success: true,
        data: Array.isArray(members)
          ? members
          : [members as unknown as OrganizationMember],
      };
    }

    return result as ApiResponse<OrganizationMember[]>;
  }

  /**
   * Create organization user
   * POST /api/users with organization IRI
   */
  async createOrganizationUser(
    organizationId: number,
    data: CreateOrganizationUserData,
  ): Promise<ApiResponse<OrganizationMember>> {
    const userData = {
      ...data,
      organization: `/api/organizations/${organizationId}`, // IRI format
    };

    return makeApiRequest<OrganizationMember>(`/api/users`, {
      method: "POST",
      body: JSON.stringify(userData),
    });
  }

  /**
   * Delete organization member
   * DELETE /api/users/{id}
   */
  async deleteOrganizationMember(memberId: number): Promise<ApiResponse<void>> {
    return makeApiRequest(`/api/users/${memberId}`, {
      method: "DELETE",
    });
  }
}

export const organizationService = new OrganizationService();
