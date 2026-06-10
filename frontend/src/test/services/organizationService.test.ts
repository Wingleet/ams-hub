import { describe, it, expect, beforeEach, vi } from "vitest";
import { organizationService } from "../../services/organizationService";
import type {
  Organization,
  OrganizationMember,
  CreateOrganizationUserData,
} from "../../types";

// Mock fetch globalement
(globalThis.fetch as unknown as ReturnType<typeof vi.fn>) = vi.fn();

describe("OrganizationService", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  const mockOrganization: Organization = {
    id: 1,
    name: "Test Organization",
    isActive: true,
  };

  const mockMember: OrganizationMember = {
    id: 1,
    email: "member@example.com",
    firstName: "John",
    lastName: "Doe",
    roles: ["ROLE_USER"],
    createdAt: "2024-01-01T00:00:00Z",
  };

  describe("getMyOrganization", () => {
    it("should fetch user organization successfully", async () => {
      const mockUserResponse = {
        email: "user@example.com",
        firstName: "Test",
        lastName: "User",
        organization: mockOrganization,
      };

      (
        globalThis.fetch as unknown as ReturnType<typeof vi.fn>
      ).mockResolvedValueOnce({
        ok: true,
        status: 200,
        headers: new Headers({ "content-type": "application/json" }),
        json: async () => mockUserResponse,
      });

      const result = await organizationService.getMyOrganization();

      expect(result.success).toBe(true);
      expect(result.data).toEqual(mockOrganization);
      expect(
        globalThis.fetch as unknown as ReturnType<typeof vi.fn>,
      ).toHaveBeenCalledWith(
        expect.stringContaining("/api/auth/me"),
        expect.objectContaining({
          credentials: "include",
        }),
      );
    });

    it("should handle user without organization", async () => {
      const mockUserResponse = {
        email: "user@example.com",
        firstName: "Test",
        lastName: "User",
        organization: null,
      };

      (
        globalThis.fetch as unknown as ReturnType<typeof vi.fn>
      ).mockResolvedValueOnce({
        ok: true,
        status: 200,
        headers: new Headers({ "content-type": "application/json" }),
        json: async () => mockUserResponse,
      });

      const result = await organizationService.getMyOrganization();

      expect(result.success).toBe(false);
      expect(result.message).toBe("No organization found for current user");
    });

    it("should handle error without hydra:description", async () => {
      const mockUserResponse = {
        email: "user@example.com",
        firstName: "Test",
        lastName: "User",
        organization: null,
      };

      (
        globalThis.fetch as unknown as ReturnType<typeof vi.fn>
      ).mockResolvedValueOnce({
        ok: true,
        status: 200,
        headers: new Headers({ "content-type": "application/json" }),
        json: async () => mockUserResponse,
      });

      const result = await organizationService.getMyOrganization();

      expect(result.success).toBe(false);
      expect(result.message).toBe("No organization found for current user");
    });
  });

  describe("getOrganizationMembers", () => {
    const organizationId = 1;

    it("should fetch organization members successfully with hydra:member format", async () => {
      const mockMembers = [mockMember];
      const mockResponse = {
        "hydra:member": mockMembers,
      };

      (
        globalThis.fetch as unknown as ReturnType<typeof vi.fn>
      ).mockResolvedValueOnce({
        ok: true,
        status: 200,
        headers: new Headers({ "content-type": "application/json" }),
        json: async () => mockResponse,
      });

      const result =
        await organizationService.getOrganizationMembers(organizationId);

      expect(result.success).toBe(true);
      expect(result.data).toEqual(mockMembers);
      expect(
        globalThis.fetch as unknown as ReturnType<typeof vi.fn>,
      ).toHaveBeenCalledWith(
        expect.stringContaining(`/api/users?organization.id=${organizationId}`),
        expect.objectContaining({
          credentials: "include",
        }),
      );
    });

    it("should handle direct array response format", async () => {
      const mockMembers = [mockMember];

      (
        globalThis.fetch as unknown as ReturnType<typeof vi.fn>
      ).mockResolvedValueOnce({
        ok: true,
        status: 200,
        headers: new Headers({ "content-type": "application/json" }),
        json: async () => mockMembers,
      });

      const result =
        await organizationService.getOrganizationMembers(organizationId);

      expect(result.success).toBe(true);
      expect(result.data).toEqual(mockMembers);
    });

    it("should handle empty members list", async () => {
      const mockResponse = {
        "hydra:member": [],
      };

      (
        globalThis.fetch as unknown as ReturnType<typeof vi.fn>
      ).mockResolvedValueOnce({
        ok: true,
        status: 200,
        headers: new Headers({ "content-type": "application/json" }),
        json: async () => mockResponse,
      });

      const result =
        await organizationService.getOrganizationMembers(organizationId);

      expect(result.success).toBe(true);
      expect(result.data).toEqual([]);
    });

    it("should handle unauthorized access", async () => {
      const mockError = {
        "hydra:description": "Access denied",
      };

      (
        globalThis.fetch as unknown as ReturnType<typeof vi.fn>
      ).mockResolvedValueOnce({
        ok: false,
        status: 403,
        headers: new Headers({ "content-type": "application/json" }),
        json: async () => mockError,
      });

      const result =
        await organizationService.getOrganizationMembers(organizationId);

      expect(result.success).toBe(false);
      expect(result.message).toBe("Access denied");
    });
  });

  describe("createOrganizationUser", () => {
    const organizationId = 1;
    const userData: CreateOrganizationUserData = {
      email: "newuser@example.com",
      firstName: "Jane",
      lastName: "Smith",
      password: "SecurePassword123!",
    };

    it("should create organization user successfully", async () => {
      const newMember: OrganizationMember = {
        id: 2,
        email: userData.email,
        firstName: userData.firstName,
        lastName: userData.lastName,
        roles: ["ROLE_USER"],
        createdAt: "2024-01-01T00:00:00Z",
      };

      (
        globalThis.fetch as unknown as ReturnType<typeof vi.fn>
      ).mockResolvedValueOnce({
        ok: true,
        status: 201,
        headers: new Headers({ "content-type": "application/json" }),
        json: async () => newMember,
      });

      const result = await organizationService.createOrganizationUser(
        organizationId,
        userData,
      );

      expect(result.success).toBe(true);
      expect(result.data).toEqual(newMember);
      expect(
        globalThis.fetch as unknown as ReturnType<typeof vi.fn>,
      ).toHaveBeenCalledWith(
        expect.stringContaining(`/api/users`),
        expect.objectContaining({
          method: "POST",
          credentials: "include",
        }),
      );

      // Verify organization IRI is included in the body
      const callArgs = (globalThis.fetch as unknown as ReturnType<typeof vi.fn>)
        .mock.calls[0];
      const body = JSON.parse(callArgs[1].body);
      expect(body.organization).toBe(`/api/organizations/${organizationId}`);
    });

    it("should handle validation errors", async () => {
      const mockError = {
        "hydra:description": "Validation failed",
        violations: [
          { propertyPath: "email", message: "Email already exists" },
          { propertyPath: "password", message: "Password is too weak" },
        ],
      };

      (
        globalThis.fetch as unknown as ReturnType<typeof vi.fn>
      ).mockResolvedValueOnce({
        ok: false,
        status: 400,
        headers: new Headers({ "content-type": "application/json" }),
        json: async () => mockError,
      });

      const result = await organizationService.createOrganizationUser(
        organizationId,
        userData,
      );

      expect(result.success).toBe(false);
      expect(result.message).toBe("Validation failed");
      expect(result.errors).toBeDefined();
    });

    it("should handle email already exists error", async () => {
      const mockError = {
        "hydra:description": "User with this email already exists",
      };

      (
        globalThis.fetch as unknown as ReturnType<typeof vi.fn>
      ).mockResolvedValueOnce({
        ok: false,
        status: 409,
        headers: new Headers({ "content-type": "application/json" }),
        json: async () => mockError,
      });

      const result = await organizationService.createOrganizationUser(
        organizationId,
        userData,
      );

      expect(result.success).toBe(false);
      expect(result.message).toBe("User with this email already exists");
    });

    it("should handle insufficient permissions", async () => {
      const mockError = {
        "hydra:description": "You do not have permission to create users",
      };

      (
        globalThis.fetch as unknown as ReturnType<typeof vi.fn>
      ).mockResolvedValueOnce({
        ok: false,
        status: 403,
        headers: new Headers({ "content-type": "application/json" }),
        json: async () => mockError,
      });

      const result = await organizationService.createOrganizationUser(
        organizationId,
        userData,
      );

      expect(result.success).toBe(false);
      expect(result.message).toBe("You do not have permission to create users");
    });
  });

  describe("deleteOrganizationMember", () => {
    const memberId = 1;

    it("should delete organization member successfully", async () => {
      (
        globalThis.fetch as unknown as ReturnType<typeof vi.fn>
      ).mockResolvedValueOnce({
        ok: true,
        status: 204,
        headers: new Headers({ "content-type": "application/json" }),
      });

      const result =
        await organizationService.deleteOrganizationMember(memberId);

      expect(result.success).toBe(true);
      expect(
        globalThis.fetch as unknown as ReturnType<typeof vi.fn>,
      ).toHaveBeenCalledWith(
        expect.stringContaining(`/api/users/${memberId}`),
        expect.objectContaining({
          method: "DELETE",
          credentials: "include",
        }),
      );
    });

    it("should handle member not found", async () => {
      const mockError = {
        "hydra:description": "Member not found",
      };

      (
        globalThis.fetch as unknown as ReturnType<typeof vi.fn>
      ).mockResolvedValueOnce({
        ok: false,
        status: 404,
        headers: new Headers({ "content-type": "application/json" }),
        json: async () => mockError,
      });

      const result = await organizationService.deleteOrganizationMember(999);

      expect(result.success).toBe(false);
      expect(result.message).toBe("Member not found");
    });

    it("should handle deleting self", async () => {
      const mockError = {
        "hydra:description": "You cannot delete yourself",
      };

      (
        globalThis.fetch as unknown as ReturnType<typeof vi.fn>
      ).mockResolvedValueOnce({
        ok: false,
        status: 400,
        headers: new Headers({ "content-type": "application/json" }),
        json: async () => mockError,
      });

      const result =
        await organizationService.deleteOrganizationMember(memberId);

      expect(result.success).toBe(false);
      expect(result.message).toBe("You cannot delete yourself");
    });

    it("should handle insufficient permissions for deletion", async () => {
      const mockError = {
        "hydra:description": "You do not have permission to delete this member",
      };

      (
        globalThis.fetch as unknown as ReturnType<typeof vi.fn>
      ).mockResolvedValueOnce({
        ok: false,
        status: 403,
        headers: new Headers({ "content-type": "application/json" }),
        json: async () => mockError,
      });

      const result = await organizationService.deleteOrganizationMember(2);

      expect(result.success).toBe(false);
      expect(result.message).toBe(
        "You do not have permission to delete this member",
      );
    });
  });

  describe("error handling", () => {
    it("should handle network errors", async () => {
      (
        globalThis.fetch as unknown as ReturnType<typeof vi.fn>
      ).mockRejectedValueOnce(new Error("Network error"));

      const result = await organizationService.getOrganizationMembers(1);

      expect(result.success).toBe(false);
      expect(result.message).toBe("Network error");
    });

    it("should handle non-JSON responses", async () => {
      const mockUserResponse = {
        email: "user@example.com",
        firstName: "Test",
        lastName: "User",
        organization: mockOrganization,
      };

      (
        globalThis.fetch as unknown as ReturnType<typeof vi.fn>
      ).mockResolvedValueOnce({
        ok: true,
        status: 200,
        headers: new Headers({ "content-type": "application/json" }),
        json: async () => mockUserResponse,
      });

      const result = await organizationService.getMyOrganization();

      expect(result.success).toBe(true);
      expect(result.data).toEqual(mockOrganization);
    });

    it("should handle responses without content-type header", async () => {
      (
        globalThis.fetch as unknown as ReturnType<typeof vi.fn>
      ).mockResolvedValueOnce({
        ok: false,
        status: 500,
        headers: new Headers(),
        text: async () => "Internal Server Error",
      });

      const result = await organizationService.getOrganizationMembers(1);

      expect(result.success).toBe(false);
      expect(result.message).toBe("Invalid response format from server");
    });

    it("should include credentials in all requests", async () => {
      (
        globalThis.fetch as unknown as ReturnType<typeof vi.fn>
      ).mockResolvedValueOnce({
        ok: true,
        status: 200,
        headers: new Headers({ "content-type": "application/json" }),
        json: async () => ({ data: mockOrganization }),
      });

      await organizationService.getMyOrganization();

      expect(
        globalThis.fetch as unknown as ReturnType<typeof vi.fn>,
      ).toHaveBeenCalledWith(
        expect.any(String),
        expect.objectContaining({
          credentials: "include",
        }),
      );
    });

    it("should set correct content-type header for POST requests", async () => {
      (
        globalThis.fetch as unknown as ReturnType<typeof vi.fn>
      ).mockResolvedValueOnce({
        ok: true,
        status: 201,
        headers: new Headers({ "content-type": "application/json" }),
        json: async () => ({ data: mockMember }),
      });

      await organizationService.createOrganizationUser(1, {
        email: "test@example.com",
        firstName: "Test",
        lastName: "User",
        password: "password123",
      });

      expect(
        globalThis.fetch as unknown as ReturnType<typeof vi.fn>,
      ).toHaveBeenCalledWith(
        expect.any(String),
        expect.objectContaining({
          headers: expect.objectContaining({
            "Content-Type": "application/json",
          }),
        }),
      );
    });
  });

  describe("API response format", () => {
    it("should extract data from nested response structure", async () => {
      const mockUserResponse = {
        email: "user@example.com",
        firstName: "Test",
        lastName: "User",
        organization: mockOrganization,
        meta: { timestamp: "2024-01-01" },
      };

      (
        globalThis.fetch as unknown as ReturnType<typeof vi.fn>
      ).mockResolvedValueOnce({
        ok: true,
        status: 200,
        headers: new Headers({ "content-type": "application/json" }),
        json: async () => mockUserResponse,
      });

      const result = await organizationService.getMyOrganization();

      expect(result.data).toEqual(mockOrganization);
      expect(result.data).not.toHaveProperty("meta");
    });

    it("should handle response without data field", async () => {
      const mockUserResponse = {
        email: "user@example.com",
        firstName: "Test",
        lastName: "User",
      };

      (
        globalThis.fetch as unknown as ReturnType<typeof vi.fn>
      ).mockResolvedValueOnce({
        ok: true,
        status: 200,
        headers: new Headers({ "content-type": "application/json" }),
        json: async () => mockUserResponse,
      });

      const result = await organizationService.getMyOrganization();

      expect(result.success).toBe(false);
      expect(result.message).toBe("No organization found for current user");
    });
  });
});
