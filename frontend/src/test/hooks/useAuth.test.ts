import { describe, it, expect, beforeEach, vi, afterEach } from "vitest";
import { renderHook, waitFor } from "@testing-library/react";
import { useAuth } from "../../hooks/useAuth";
import { useAuthStore } from "../../store/authStore";
import { authService } from "../../services/authService";
import type { User } from "../../types";

// Mock authService
vi.mock("../../services/authService", () => ({
  authService: {
    getCurrentUser: vi.fn(),
    logout: vi.fn(),
  },
}));

// Mock window.location
// @ts-expect-error - window.location is read-only, we need to mock it for testing
delete window.location;
// @ts-expect-error - window.location is read-only, we need to mock it for testing
window.location = { href: "" };

describe("useAuth", () => {
  const mockUser: User = {
    id: 1,
    email: "test@example.com",
    firstName: "John",
    lastName: "Doe",
    fullName: "John Doe",
    roles: ["ROLE_USER"],
    isAdmin: false,
    isActive: true,
    createdAt: "2024-01-01T00:00:00Z",
  };

  beforeEach(() => {
    // Reset store state before each test
    useAuthStore.setState({
      user: null,
      isAuthenticated: false,
      isLoading: true,
    });

    // Clear all mocks
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.clearAllMocks();
  });

  describe("initial authentication check", () => {
    it("should check authentication on mount", async () => {
      vi.mocked(authService.getCurrentUser).mockResolvedValue({
        success: true,
        user: mockUser,
      });

      const { result } = renderHook(() => useAuth());

      // Initially loading
      expect(result.current.isLoading).toBe(true);

      // Wait for the authentication check to complete
      await waitFor(() => {
        expect(result.current.isLoading).toBe(false);
      });

      expect(authService.getCurrentUser).toHaveBeenCalledTimes(1);
      expect(result.current.user).toEqual(mockUser);
      expect(result.current.isAuthenticated).toBe(true);
    });

    it("should handle unauthenticated user on mount", async () => {
      vi.mocked(authService.getCurrentUser).mockResolvedValue({
        success: false,
        message: "Not authenticated",
      });

      const { result } = renderHook(() => useAuth());

      await waitFor(() => {
        expect(result.current.isLoading).toBe(false);
      });

      expect(result.current.user).toBeNull();
      expect(result.current.isAuthenticated).toBe(false);
    });

    it("should handle API error on mount", async () => {
      const consoleError = vi
        .spyOn(console, "error")
        .mockImplementation(() => {});
      vi.mocked(authService.getCurrentUser).mockRejectedValue(
        new Error("Network error"),
      );

      const { result } = renderHook(() => useAuth());

      await waitFor(() => {
        expect(result.current.isLoading).toBe(false);
      });

      expect(result.current.user).toBeNull();
      expect(result.current.isAuthenticated).toBe(false);
      expect(consoleError).toHaveBeenCalledWith(
        "Failed to check authentication:",
        expect.any(Error),
      );

      consoleError.mockRestore();
    });

    it("should set user to null when response has no user", async () => {
      vi.mocked(authService.getCurrentUser).mockResolvedValue({
        success: true,
      });

      const { result } = renderHook(() => useAuth());

      await waitFor(() => {
        expect(result.current.isLoading).toBe(false);
      });

      expect(result.current.user).toBeNull();
      expect(result.current.isAuthenticated).toBe(false);
    });
  });

  describe("logout functionality", () => {
    it("should logout user successfully", async () => {
      // Set up initial authenticated state
      useAuthStore.setState({
        user: mockUser,
        isAuthenticated: true,
        isLoading: false,
      });

      vi.mocked(authService.logout).mockResolvedValue({
        success: true,
      });

      vi.mocked(authService.getCurrentUser).mockResolvedValue({
        success: true,
        user: mockUser,
      });

      const { result } = renderHook(() => useAuth());

      // Wait for initial load
      await waitFor(() => {
        expect(result.current.isLoading).toBe(false);
      });

      // Call logout
      await result.current.logout();

      expect(authService.logout).toHaveBeenCalledTimes(1);
      expect(window.location.href).toBe("/login");
    });

    it("should logout locally even if API call fails", async () => {
      const consoleError = vi
        .spyOn(console, "error")
        .mockImplementation(() => {});

      useAuthStore.setState({
        user: mockUser,
        isAuthenticated: true,
        isLoading: false,
      });

      vi.mocked(authService.logout).mockRejectedValue(
        new Error("Logout API error"),
      );

      vi.mocked(authService.getCurrentUser).mockResolvedValue({
        success: true,
        user: mockUser,
      });

      const { result } = renderHook(() => useAuth());

      await waitFor(() => {
        expect(result.current.isLoading).toBe(false);
      });

      // Call logout
      await result.current.logout();

      expect(authService.logout).toHaveBeenCalledTimes(1);
      expect(consoleError).toHaveBeenCalledWith(
        "Logout API call failed:",
        expect.any(Error),
      );
      expect(window.location.href).toBe("/login");

      consoleError.mockRestore();
    });

    it("should redirect to login after logout", async () => {
      useAuthStore.setState({
        user: mockUser,
        isAuthenticated: true,
        isLoading: false,
      });

      vi.mocked(authService.logout).mockResolvedValue({
        success: true,
      });

      vi.mocked(authService.getCurrentUser).mockResolvedValue({
        success: true,
        user: mockUser,
      });

      const { result } = renderHook(() => useAuth());

      await waitFor(() => {
        expect(result.current.isLoading).toBe(false);
      });

      // Reset window.location.href
      window.location.href = "/dashboard";

      await result.current.logout();

      expect(window.location.href).toBe("/login");
    });
  });

  describe("return values", () => {
    it("should return user data", async () => {
      vi.mocked(authService.getCurrentUser).mockResolvedValue({
        success: true,
        user: mockUser,
      });

      const { result } = renderHook(() => useAuth());

      await waitFor(() => {
        expect(result.current.isLoading).toBe(false);
      });

      expect(result.current.user).toEqual(mockUser);
    });

    it("should return authentication status", async () => {
      vi.mocked(authService.getCurrentUser).mockResolvedValue({
        success: true,
        user: mockUser,
      });

      const { result } = renderHook(() => useAuth());

      await waitFor(() => {
        expect(result.current.isLoading).toBe(false);
      });

      expect(result.current.isAuthenticated).toBe(true);
    });

    it("should return loading status", async () => {
      vi.mocked(authService.getCurrentUser).mockResolvedValue({
        success: true,
        user: mockUser,
      });

      const { result } = renderHook(() => useAuth());

      // Initially should be loading
      expect(result.current.isLoading).toBe(true);

      await waitFor(() => {
        expect(result.current.isLoading).toBe(false);
      });
    });

    it("should return logout function", async () => {
      vi.mocked(authService.getCurrentUser).mockResolvedValue({
        success: true,
        user: mockUser,
      });

      const { result } = renderHook(() => useAuth());

      await waitFor(() => {
        expect(result.current.isLoading).toBe(false);
      });

      expect(typeof result.current.logout).toBe("function");
    });
  });

  describe("edge cases", () => {
    it("should handle rapid logout calls", async () => {
      useAuthStore.setState({
        user: mockUser,
        isAuthenticated: true,
        isLoading: false,
      });

      vi.mocked(authService.logout).mockResolvedValue({
        success: true,
      });

      vi.mocked(authService.getCurrentUser).mockResolvedValue({
        success: true,
        user: mockUser,
      });

      const { result } = renderHook(() => useAuth());

      await waitFor(() => {
        expect(result.current.isLoading).toBe(false);
      });

      // Call logout multiple times rapidly
      await Promise.all([
        result.current.logout(),
        result.current.logout(),
        result.current.logout(),
      ]);

      // Should still only redirect once and maintain state
      expect(window.location.href).toBe("/login");
    });

    it("should handle user with admin role", async () => {
      const adminUser: User = {
        ...mockUser,
        roles: ["ROLE_ADMIN"],
        isAdmin: true,
      };

      vi.mocked(authService.getCurrentUser).mockResolvedValue({
        success: true,
        user: adminUser,
      });

      const { result } = renderHook(() => useAuth());

      await waitFor(() => {
        expect(result.current.isLoading).toBe(false);
      });

      expect(result.current.user?.isAdmin).toBe(true);
      expect(result.current.isAuthenticated).toBe(true);
    });

    it("should handle user with organization", async () => {
      const userWithOrg: User = {
        ...mockUser,
        organization: {
          id: 1,
          name: "Test Org",
          isActive: true,
        },
      };

      vi.mocked(authService.getCurrentUser).mockResolvedValue({
        success: true,
        user: userWithOrg,
      });

      const { result } = renderHook(() => useAuth());

      await waitFor(() => {
        expect(result.current.isLoading).toBe(false);
      });

      expect(result.current.user?.organization).toEqual({
        id: 1,
        name: "Test Org",
        isActive: true,
      });
    });
  });

  describe("store integration", () => {
    it("should sync with auth store", async () => {
      vi.mocked(authService.getCurrentUser).mockResolvedValue({
        success: true,
        user: mockUser,
      });

      const { result } = renderHook(() => useAuth());

      await waitFor(() => {
        expect(result.current.isLoading).toBe(false);
      });

      const storeState = useAuthStore.getState();
      expect(storeState.user).toEqual(mockUser);
      expect(storeState.isAuthenticated).toBe(true);
      expect(storeState.isLoading).toBe(false);
    });

    it("should update when store changes", async () => {
      vi.mocked(authService.getCurrentUser).mockResolvedValue({
        success: true,
        user: mockUser,
      });

      const { result } = renderHook(() => useAuth());

      await waitFor(() => {
        expect(result.current.isLoading).toBe(false);
      });

      // Manually update store
      useAuthStore.getState().setUser(null);

      await waitFor(() => {
        expect(result.current.user).toBeNull();
        expect(result.current.isAuthenticated).toBe(false);
      });
    });
  });
});
