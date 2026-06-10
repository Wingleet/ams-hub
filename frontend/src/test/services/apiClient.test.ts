import { describe, it, expect, beforeEach, vi } from "vitest";
import { apiFetch } from "../../services/apiClient";

describe("apiClient - Intercepteur de refresh token", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    // Mock fetch global
    (globalThis.fetch as unknown as ReturnType<typeof vi.fn>) = vi.fn();
  });

  it("should make a successful request without refresh", async () => {
    const mockResponse = { success: true, data: "test" };

    (
      globalThis.fetch as unknown as ReturnType<typeof vi.fn>
    ).mockResolvedValueOnce({
      ok: true,
      status: 200,
      json: async () => mockResponse,
    });

    const response = await apiFetch("/api/applications", {
      method: "GET",
    });

    expect(response.ok).toBe(true);
    expect(
      globalThis.fetch as unknown as ReturnType<typeof vi.fn>,
    ).toHaveBeenCalledTimes(1);
  });

  it("should automatically refresh token on 401 and retry request", async () => {
    const mockData = { success: true, data: "test" };

    // Première requête retourne 401
    (
      globalThis.fetch as unknown as ReturnType<typeof vi.fn>
    ).mockResolvedValueOnce({
      ok: false,
      status: 401,
      json: async () => ({ message: "Unauthorized" }),
    });

    // Refresh token succeeds
    (
      globalThis.fetch as unknown as ReturnType<typeof vi.fn>
    ).mockResolvedValueOnce({
      ok: true,
      status: 200,
      json: async () => ({ success: true, message: "Token refreshed" }),
    });

    // Retry of original request succeeds
    (
      globalThis.fetch as unknown as ReturnType<typeof vi.fn>
    ).mockResolvedValueOnce({
      ok: true,
      status: 200,
      json: async () => mockData,
    });

    const response = await apiFetch("/api/applications", {
      method: "GET",
    });

    expect(response.ok).toBe(true);
    expect(
      globalThis.fetch as unknown as ReturnType<typeof vi.fn>,
    ).toHaveBeenCalledTimes(3); // Original + refresh + retry
  });

  it("should not refresh token for auth endpoints", async () => {
    (
      globalThis.fetch as unknown as ReturnType<typeof vi.fn>
    ).mockResolvedValueOnce({
      ok: false,
      status: 401,
      json: async () => ({ message: "Invalid credentials" }),
    });

    const response = await apiFetch("/api/auth/login", {
      method: "POST",
    });

    expect(response.status).toBe(401);
    expect(
      globalThis.fetch as unknown as ReturnType<typeof vi.fn>,
    ).toHaveBeenCalledTimes(1); // Pas de retry
  });

  it("should redirect to login if refresh fails", async () => {
    const originalLocation = window.location;
    // @ts-expect-error - window.location is read-only, we need to mock it for testing
    delete window.location;
    // @ts-expect-error - window.location is read-only, we need to mock it for testing
    window.location = { href: "" };

    // Première requête retourne 401
    (
      globalThis.fetch as unknown as ReturnType<typeof vi.fn>
    ).mockResolvedValueOnce({
      ok: false,
      status: 401,
      json: async () => ({ message: "Unauthorized" }),
    });

    // Refresh token fails
    (
      globalThis.fetch as unknown as ReturnType<typeof vi.fn>
    ).mockResolvedValueOnce({
      ok: false,
      status: 401,
      json: async () => ({ message: "Invalid refresh token" }),
    });

    await apiFetch("/api/applications", {
      method: "GET",
    });

    expect(window.location.href).toBe("/login");

    // Restore
    // @ts-expect-error - window.location is read-only, we need to restore it after the test
    window.location = originalLocation;
  });
});
