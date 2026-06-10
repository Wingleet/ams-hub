import { describe, it, expect, beforeEach, vi } from "vitest";
import { applicationService } from "../../services/applicationService";
import type { Application } from "../../types";

// Mock fetch globalement
(globalThis.fetch as unknown as ReturnType<typeof vi.fn>) = vi.fn();

describe("ApplicationService", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  const mockApplication: Application = {
    id: 1,
    name: "Test App",
    description: "A test application",
    url: "https://test.app",
    iconUrl: "test-icon.png",
    isActive: true,
    isSubscribed: false,
  };

  describe("getApplications", () => {
    it("should fetch all applications successfully with API Platform format", async () => {
      const mockApplications = [mockApplication];
      const apiPlatformResponse = {
        member: mockApplications,
      };

      (
        globalThis.fetch as unknown as ReturnType<typeof vi.fn>
      ).mockResolvedValueOnce({
        ok: true,
        status: 200,
        headers: new Headers({ "content-type": "application/json" }),
        json: async () => apiPlatformResponse,
      });

      const result = await applicationService.getApplications();

      expect(result.success).toBe(true);
      expect(result.data).toEqual(mockApplications);
      expect(
        globalThis.fetch as unknown as ReturnType<typeof vi.fn>,
      ).toHaveBeenCalledWith(
        expect.stringContaining("/api/applications"),
        expect.objectContaining({
          credentials: "include",
        }),
      );
    });

    it("should handle direct array response format", async () => {
      const mockApplications = [mockApplication];

      (
        globalThis.fetch as unknown as ReturnType<typeof vi.fn>
      ).mockResolvedValueOnce({
        ok: true,
        status: 200,
        headers: new Headers({ "content-type": "application/json" }),
        json: async () => mockApplications,
      });

      const result = await applicationService.getApplications();

      expect(result.success).toBe(true);
      expect(result.data).toEqual(mockApplications);
    });

    it("should handle empty applications list", async () => {
      const apiPlatformResponse = {
        member: [],
      };

      (
        globalThis.fetch as unknown as ReturnType<typeof vi.fn>
      ).mockResolvedValueOnce({
        ok: true,
        status: 200,
        headers: new Headers({ "content-type": "application/json" }),
        json: async () => apiPlatformResponse,
      });

      const result = await applicationService.getApplications();

      expect(result.success).toBe(true);
      expect(result.data).toEqual([]);
    });

    it("should handle API errors", async () => {
      const mockError = {
        "hydra:description": "Failed to fetch applications",
        detail: "Server error",
      };

      (
        globalThis.fetch as unknown as ReturnType<typeof vi.fn>
      ).mockResolvedValueOnce({
        ok: false,
        status: 500,
        headers: new Headers({ "content-type": "application/json" }),
        json: async () => mockError,
      });

      const result = await applicationService.getApplications();

      expect(result.success).toBe(false);
      expect(result.message).toBe("Failed to fetch applications");
    });
  });

  describe("getMyApplications", () => {
    it("should fetch user subscribed applications with organizationId", async () => {
      const mockSubscribedApp = { ...mockApplication, isSubscribed: true };
      const mockUnsubscribedApp = {
        ...mockApplication,
        id: 2,
        isSubscribed: false,
      };
      const mockApplications = [mockSubscribedApp, mockUnsubscribedApp];

      // Mock getApplications response
      (
        globalThis.fetch as unknown as ReturnType<typeof vi.fn>
      ).mockResolvedValueOnce({
        ok: true,
        status: 200,
        headers: new Headers({ "content-type": "application/json" }),
        json: async () => ({ member: mockApplications }),
      });

      // Mock subscriptions response
      const mockSubscriptions = [{ application: { id: 1 } }];

      (
        globalThis.fetch as unknown as ReturnType<typeof vi.fn>
      ).mockResolvedValueOnce({
        ok: true,
        status: 200,
        headers: new Headers({ "content-type": "application/json" }),
        json: async () => ({ member: mockSubscriptions }),
      });

      const result = await applicationService.getMyApplications(1);

      expect(result.success).toBe(true);
      expect(result.data).toBeDefined();
      expect(result.data?.length).toBe(2);
      expect(result.data?.[0].isSubscribed).toBe(true);
      expect(result.data?.[1].isSubscribed).toBe(false);
    });

    it("should return apps without subscription info when no organizationId provided", async () => {
      const mockApplications = [mockApplication];

      // Mock getApplications response
      (
        globalThis.fetch as unknown as ReturnType<typeof vi.fn>
      ).mockResolvedValueOnce({
        ok: true,
        status: 200,
        headers: new Headers({ "content-type": "application/json" }),
        json: async () => ({ member: mockApplications }),
      });

      const result = await applicationService.getMyApplications();

      expect(result.success).toBe(true);
      expect(result.data).toBeDefined();
      expect(result.data?.[0].isSubscribed).toBe(false);
      expect(
        globalThis.fetch as unknown as ReturnType<typeof vi.fn>,
      ).toHaveBeenCalledTimes(1); // Only getApplications, no subscriptions call
    });
  });

  describe("error handling", () => {
    it("should handle network errors", async () => {
      (
        globalThis.fetch as unknown as ReturnType<typeof vi.fn>
      ).mockRejectedValueOnce(new Error("Network error"));

      const result = await applicationService.getApplications();

      expect(result.success).toBe(false);
      expect(result.message).toBe("Network error");
    });

    it("should handle 204 No Content responses", async () => {
      (
        globalThis.fetch as unknown as ReturnType<typeof vi.fn>
      ).mockResolvedValueOnce({
        ok: true,
        status: 204,
        headers: new Headers(),
      });

      const result = await applicationService.getApplications();

      // 204 returns success:true but no data, getApplications checks for data
      expect(result.success).toBe(false);
      expect(result.message).toBe("Failed to fetch applications");
    });

    it("should handle response with no member property", async () => {
      const emptyResponse = {};

      (
        globalThis.fetch as unknown as ReturnType<typeof vi.fn>
      ).mockResolvedValueOnce({
        ok: true,
        status: 200,
        headers: new Headers({ "content-type": "application/json" }),
        json: async () => emptyResponse,
      });

      const result = await applicationService.getApplications();

      expect(result.success).toBe(true);
      expect(result.data).toEqual([]);
    });

    it("should handle non-JSON responses for successful requests", async () => {
      (
        globalThis.fetch as unknown as ReturnType<typeof vi.fn>
      ).mockResolvedValueOnce({
        ok: true,
        status: 200,
        headers: new Headers({ "content-type": "text/plain" }),
        text: async () => "Success",
      });

      const result = await applicationService.getApplications();

      // Non-JSON response returns success:true but no data, getApplications checks for data
      expect(result.success).toBe(false);
      expect(result.message).toBe("Failed to fetch applications");
    });

    it("should handle non-JSON error responses", async () => {
      (
        globalThis.fetch as unknown as ReturnType<typeof vi.fn>
      ).mockResolvedValueOnce({
        ok: false,
        status: 500,
        headers: new Headers({ "content-type": "text/html" }),
        text: async () => "<html>Error page</html>",
      });

      const result = await applicationService.getApplications();

      expect(result.success).toBe(false);
      expect(result.message).toBe("Invalid response format from server");
    });

    it("should include credentials in all requests", async () => {
      const apiPlatformResponse = { member: [] };

      (
        globalThis.fetch as unknown as ReturnType<typeof vi.fn>
      ).mockResolvedValueOnce({
        ok: true,
        status: 200,
        headers: new Headers({ "content-type": "application/json" }),
        json: async () => apiPlatformResponse,
      });

      await applicationService.getApplications();

      expect(
        globalThis.fetch as unknown as ReturnType<typeof vi.fn>,
      ).toHaveBeenCalledWith(
        expect.any(String),
        expect.objectContaining({
          credentials: "include",
        }),
      );
    });
  });

  describe("error message priority", () => {
    it("should prioritize hydra:description over detail", async () => {
      const mockError = {
        "hydra:description": "Hydra error",
        detail: "Detail error",
        message: "Generic message",
      };

      (
        globalThis.fetch as unknown as ReturnType<typeof vi.fn>
      ).mockResolvedValueOnce({
        ok: false,
        status: 400,
        headers: new Headers({ "content-type": "application/json" }),
        json: async () => mockError,
      });

      const result = await applicationService.getApplications();

      expect(result.message).toBe("Hydra error");
    });

    it("should use detail if hydra:description is not present", async () => {
      const mockError = {
        detail: "Detail error",
        message: "Generic message",
      };

      (
        globalThis.fetch as unknown as ReturnType<typeof vi.fn>
      ).mockResolvedValueOnce({
        ok: false,
        status: 400,
        headers: new Headers({ "content-type": "application/json" }),
        json: async () => mockError,
      });

      const result = await applicationService.getApplications();

      expect(result.message).toBe("Detail error");
    });

    it("should use message if both hydra:description and detail are not present", async () => {
      const mockError = {
        message: "Generic message",
      };

      (
        globalThis.fetch as unknown as ReturnType<typeof vi.fn>
      ).mockResolvedValueOnce({
        ok: false,
        status: 400,
        headers: new Headers({ "content-type": "application/json" }),
        json: async () => mockError,
      });

      const result = await applicationService.getApplications();

      expect(result.message).toBe("Generic message");
    });

    it("should use default error message if no message fields are present", async () => {
      const mockError = {};

      (
        globalThis.fetch as unknown as ReturnType<typeof vi.fn>
      ).mockResolvedValueOnce({
        ok: false,
        status: 400,
        headers: new Headers({ "content-type": "application/json" }),
        json: async () => mockError,
      });

      const result = await applicationService.getApplications();

      expect(result.message).toBe("An error occurred");
    });
  });
});
