import { describe, it, expect, beforeEach, vi } from "vitest";
import { authService } from "../../services/authService";
import type { User } from "../../types";

// Mock fetch globalement
(globalThis.fetch as unknown as ReturnType<typeof vi.fn>) = vi.fn();

describe("AuthService", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  describe("register", () => {
    it("should register a new user successfully", async () => {
      const mockUser: User = {
        id: 1,
        email: "test@example.com",
        firstName: "John",
        lastName: "Doe",
        fullName: "John Doe",
        roles: ["ROLE_USER"],
        isAdmin: false,
        isActive: true,
      };

      const mockResponse = {
        success: true,
        user: mockUser,
        message: "User registered successfully",
      };

      (
        globalThis.fetch as unknown as ReturnType<typeof vi.fn>
      ).mockResolvedValueOnce({
        ok: true,
        status: 200,
        headers: {
          get: (name: string) =>
            name === "content-type" ? "application/json" : null,
        },
        json: async () => mockResponse,
      });

      const result = await authService.register({
        email: "test@example.com",
        password: "password123",
        firstName: "John",
        lastName: "Doe",
      });

      expect(result.success).toBe(true);
      expect(result.user).toEqual(mockUser);
      expect(
        globalThis.fetch as unknown as ReturnType<typeof vi.fn>,
      ).toHaveBeenCalledWith(
        expect.stringContaining("/api/auth/register"),
        expect.objectContaining({
          method: "POST",
          credentials: "include",
        }),
      );
    });

    it("should handle registration error", async () => {
      const mockError = {
        success: false,
        message: "Email already exists",
        errors: { email: "This email is already registered" },
      };

      (
        globalThis.fetch as unknown as ReturnType<typeof vi.fn>
      ).mockResolvedValueOnce({
        ok: false,
        status: 400,
        headers: {
          get: (name: string) =>
            name === "content-type" ? "application/json" : null,
        },
        json: async () => mockError,
      });

      const result = await authService.register({
        email: "existing@example.com",
        password: "password123",
        firstName: "John",
        lastName: "Doe",
      });

      expect(result.success).toBe(false);
      expect(result.message).toBe("Email already exists");
      expect(result.errors).toBeDefined();
    });
  });

  describe("login", () => {
    it("should login user successfully", async () => {
      const mockUser: User = {
        id: 1,
        email: "test@example.com",
        firstName: "John",
        lastName: "Doe",
        fullName: "John Doe",
        roles: ["ROLE_USER"],
        isAdmin: false,
        isActive: true,
      };

      const mockResponse = {
        success: true,
        user: mockUser,
      };

      (
        globalThis.fetch as unknown as ReturnType<typeof vi.fn>
      ).mockResolvedValueOnce({
        ok: true,
        status: 200,
        headers: {
          get: (name: string) =>
            name === "content-type" ? "application/json" : null,
        },
        json: async () => mockResponse,
      });

      const result = await authService.login({
        username: "test@example.com",
        password: "password123",
      });

      expect(result.success).toBe(true);
      expect(result.user).toEqual(mockUser);
      expect(
        globalThis.fetch as unknown as ReturnType<typeof vi.fn>,
      ).toHaveBeenCalledWith(
        expect.stringContaining("/api/auth/login"),
        expect.objectContaining({
          method: "POST",
          credentials: "include",
        }),
      );
    });

    it("should handle invalid credentials", async () => {
      const mockError = {
        success: false,
        message: "Invalid credentials",
      };

      (
        globalThis.fetch as unknown as ReturnType<typeof vi.fn>
      ).mockResolvedValueOnce({
        ok: false,
        status: 401,
        headers: {
          get: (name: string) =>
            name === "content-type" ? "application/json" : null,
        },
        json: async () => mockError,
      });

      const result = await authService.login({
        username: "test@example.com",
        password: "wrongpassword",
      });

      expect(result.success).toBe(false);
      expect(result.message).toBe("Invalid credentials");
    });

    it("should support rememberMe option", async () => {
      (
        globalThis.fetch as unknown as ReturnType<typeof vi.fn>
      ).mockResolvedValueOnce({
        ok: true,
        status: 200,
        headers: {
          get: (name: string) =>
            name === "content-type" ? "application/json" : null,
        },
        json: async () => ({ success: true }),
      });

      await authService.login({
        username: "test@example.com",
        password: "password123",
        rememberMe: true,
      });

      const callArgs = (globalThis.fetch as unknown as ReturnType<typeof vi.fn>)
        .mock.calls[0];
      const body = JSON.parse(callArgs[1].body);
      expect(body.rememberMe).toBe(true);
    });
  });

  describe("logout", () => {
    it("should logout user successfully", async () => {
      const mockResponse = {
        success: true,
        message: "Logged out successfully",
      };

      (
        globalThis.fetch as unknown as ReturnType<typeof vi.fn>
      ).mockResolvedValueOnce({
        ok: true,
        status: 200,
        headers: {
          get: (name: string) =>
            name === "content-type" ? "application/json" : null,
        },
        json: async () => mockResponse,
      });

      const result = await authService.logout();

      expect(result.success).toBe(true);
      expect(
        globalThis.fetch as unknown as ReturnType<typeof vi.fn>,
      ).toHaveBeenCalledWith(
        expect.stringContaining("/api/auth/logout"),
        expect.objectContaining({
          method: "POST",
          credentials: "include",
        }),
      );
    });
  });

  describe("getCurrentUser", () => {
    it("should get current user successfully", async () => {
      const mockUser: User = {
        id: 1,
        email: "test@example.com",
        firstName: "John",
        lastName: "Doe",
        fullName: "John Doe",
        roles: ["ROLE_USER"],
        isAdmin: false,
        isActive: true,
      };

      const mockResponse = {
        success: true,
        user: mockUser,
      };

      (
        globalThis.fetch as unknown as ReturnType<typeof vi.fn>
      ).mockResolvedValueOnce({
        ok: true,
        status: 200,
        headers: {
          get: (name: string) =>
            name === "content-type" ? "application/json" : null,
        },
        json: async () => mockResponse,
      });

      const result = await authService.getCurrentUser();

      expect(result.success).toBe(true);
      expect(result.user).toEqual(mockUser);
      expect(
        globalThis.fetch as unknown as ReturnType<typeof vi.fn>,
      ).toHaveBeenCalledWith(
        expect.stringContaining("/api/auth/me"),
        expect.objectContaining({
          method: "GET",
          credentials: "include",
        }),
      );
    });

    it("should handle unauthenticated user", async () => {
      const mockResponse = {
        success: false,
        message: "Not authenticated",
      };

      (
        globalThis.fetch as unknown as ReturnType<typeof vi.fn>
      ).mockResolvedValueOnce({
        ok: false,
        status: 401,
        headers: {
          get: (name: string) =>
            name === "content-type" ? "application/json" : null,
        },
        json: async () => mockResponse,
      });

      const result = await authService.getCurrentUser();

      expect(result.success).toBe(false);
    });
  });

  describe("refreshToken", () => {
    it("should refresh token successfully", async () => {
      const mockResponse = {
        success: true,
        message: "Token refreshed",
      };

      (
        globalThis.fetch as unknown as ReturnType<typeof vi.fn>
      ).mockResolvedValueOnce({
        ok: true,
        status: 200,
        headers: {
          get: (name: string) =>
            name === "content-type" ? "application/json" : null,
        },
        json: async () => mockResponse,
      });

      const result = await authService.refreshToken();

      expect(result.success).toBe(true);
      expect(
        globalThis.fetch as unknown as ReturnType<typeof vi.fn>,
      ).toHaveBeenCalledWith(
        expect.stringContaining("/api/auth/refresh"),
        expect.objectContaining({
          method: "POST",
          credentials: "include",
        }),
      );
    });
  });

  describe("error handling", () => {
    it("should handle network errors", async () => {
      (
        globalThis.fetch as unknown as ReturnType<typeof vi.fn>
      ).mockRejectedValueOnce(new Error("Network error"));

      const result = await authService.login({
        username: "test@example.com",
        password: "password123",
      });

      expect(result.success).toBe(false);
      expect(result.message).toBe("Network error");
    });

    it("should include credentials in all requests", async () => {
      (
        globalThis.fetch as unknown as ReturnType<typeof vi.fn>
      ).mockResolvedValueOnce({
        ok: true,
        status: 200,
        headers: {
          get: (name: string) =>
            name === "content-type" ? "application/json" : null,
        },
        json: async () => ({ success: true }),
      });

      await authService.getCurrentUser();

      expect(
        globalThis.fetch as unknown as ReturnType<typeof vi.fn>,
      ).toHaveBeenCalledWith(
        expect.any(String),
        expect.objectContaining({
          credentials: "include",
        }),
      );
    });

    it("should set correct content-type header", async () => {
      (
        globalThis.fetch as unknown as ReturnType<typeof vi.fn>
      ).mockResolvedValueOnce({
        ok: true,
        status: 200,
        headers: {
          get: (name: string) =>
            name === "content-type" ? "application/json" : null,
        },
        json: async () => ({ success: true }),
      });

      await authService.register({
        email: "test@example.com",
        password: "password123",
        firstName: "John",
        lastName: "Doe",
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
});
