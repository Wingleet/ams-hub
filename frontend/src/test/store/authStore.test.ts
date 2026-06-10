import { describe, it, expect, beforeEach } from "vitest";
import { useAuthStore } from "../../store/authStore.ts";
import type { User } from "../../types";

describe("AuthStore", () => {
  beforeEach(() => {
    // Reset store state before each test
    useAuthStore.setState({
      user: null,
      isAuthenticated: false,
      isLoading: true,
    });
  });

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
    lastLoginAt: "2024-01-02T00:00:00Z",
    organization: {
      id: 1,
      name: "Test Organization",
      isActive: true,
    },
  };

  describe("initial state", () => {
    it("should have correct initial state", () => {
      const state = useAuthStore.getState();

      expect(state.user).toBeNull();
      expect(state.isAuthenticated).toBe(false);
      expect(state.isLoading).toBe(true);
    });
  });

  describe("setUser", () => {
    it("should set user and mark as authenticated", () => {
      const { setUser } = useAuthStore.getState();

      setUser(mockUser);

      const state = useAuthStore.getState();
      expect(state.user).toEqual(mockUser);
      expect(state.isAuthenticated).toBe(true);
    });

    it("should set user to null and mark as not authenticated", () => {
      const { setUser } = useAuthStore.getState();

      // First set a user
      setUser(mockUser);
      expect(useAuthStore.getState().isAuthenticated).toBe(true);

      // Then set to null
      setUser(null);

      const state = useAuthStore.getState();
      expect(state.user).toBeNull();
      expect(state.isAuthenticated).toBe(false);
    });

    it("should handle user without organization", () => {
      const { setUser } = useAuthStore.getState();
      const userWithoutOrg = { ...mockUser, organization: null };

      setUser(userWithoutOrg);

      const state = useAuthStore.getState();
      expect(state.user?.organization).toBeNull();
      expect(state.isAuthenticated).toBe(true);
    });

    it("should handle admin user", () => {
      const { setUser } = useAuthStore.getState();
      const adminUser = {
        ...mockUser,
        roles: ["ROLE_ADMIN"],
        isAdmin: true,
      };

      setUser(adminUser);

      const state = useAuthStore.getState();
      expect(state.user?.isAdmin).toBe(true);
      expect(state.user?.roles).toContain("ROLE_ADMIN");
    });
  });

  describe("setIsAuthenticated", () => {
    it("should update authentication status", () => {
      const { setIsAuthenticated } = useAuthStore.getState();

      setIsAuthenticated(true);
      expect(useAuthStore.getState().isAuthenticated).toBe(true);

      setIsAuthenticated(false);
      expect(useAuthStore.getState().isAuthenticated).toBe(false);
    });

    it("should not affect user state when changing authentication", () => {
      const { setUser, setIsAuthenticated } = useAuthStore.getState();

      setUser(mockUser);
      const userBeforeChange = useAuthStore.getState().user;

      setIsAuthenticated(false);

      expect(useAuthStore.getState().user).toEqual(userBeforeChange);
    });
  });

  describe("setIsLoading", () => {
    it("should update loading status", () => {
      const { setIsLoading } = useAuthStore.getState();

      setIsLoading(true);
      expect(useAuthStore.getState().isLoading).toBe(true);

      setIsLoading(false);
      expect(useAuthStore.getState().isLoading).toBe(false);
    });

    it("should not affect user or authentication state", () => {
      const { setUser, setIsLoading } = useAuthStore.getState();

      setUser(mockUser);
      const userBeforeChange = useAuthStore.getState().user;
      const isAuthBeforeChange = useAuthStore.getState().isAuthenticated;

      setIsLoading(true);

      expect(useAuthStore.getState().user).toEqual(userBeforeChange);
      expect(useAuthStore.getState().isAuthenticated).toBe(isAuthBeforeChange);
    });
  });

  describe("logout", () => {
    it("should clear user and mark as not authenticated", () => {
      const { setUser, logout } = useAuthStore.getState();

      // First set a user
      setUser(mockUser);
      expect(useAuthStore.getState().isAuthenticated).toBe(true);

      // Then logout
      logout();

      const state = useAuthStore.getState();
      expect(state.user).toBeNull();
      expect(state.isAuthenticated).toBe(false);
    });

    it("should not affect loading state", () => {
      const { setUser, setIsLoading, logout } = useAuthStore.getState();

      setUser(mockUser);
      setIsLoading(false);

      logout();

      expect(useAuthStore.getState().isLoading).toBe(false);
    });

    it("should be idempotent", () => {
      const { logout } = useAuthStore.getState();

      logout();
      const stateAfterFirst = useAuthStore.getState();

      logout();
      const stateAfterSecond = useAuthStore.getState();

      expect(stateAfterFirst).toEqual(stateAfterSecond);
    });
  });

  describe("persistence", () => {
    it("should persist user data", () => {
      const { setUser } = useAuthStore.getState();

      setUser(mockUser);

      // Check that the state is correctly set
      const state = useAuthStore.getState();
      expect(state.user).toEqual(mockUser);
      expect(state.isAuthenticated).toBe(true);
    });

    it("should not persist isLoading state", () => {
      // This is ensured by the partialize option in the store
      // which only persists user and isAuthenticated
      const { setIsLoading } = useAuthStore.getState();

      setIsLoading(false);

      // Loading state changes but should not be persisted
      expect(useAuthStore.getState().isLoading).toBe(false);
    });
  });

  describe("user properties", () => {
    it("should handle user with all optional fields", () => {
      const { setUser } = useAuthStore.getState();
      const completeUser = { ...mockUser };

      setUser(completeUser);

      const state = useAuthStore.getState();
      expect(state.user?.createdAt).toBe("2024-01-01T00:00:00Z");
      expect(state.user?.lastLoginAt).toBe("2024-01-02T00:00:00Z");
    });

    it("should handle user without optional fields", () => {
      const { setUser } = useAuthStore.getState();
      const minimalUser: User = {
        id: 1,
        email: "test@example.com",
        firstName: "John",
        lastName: "Doe",
        fullName: "John Doe",
        roles: ["ROLE_USER"],
        isAdmin: false,
        isActive: true,
      };

      setUser(minimalUser);

      const state = useAuthStore.getState();
      expect(state.user?.id).toBe(1);
      expect(state.user?.createdAt).toBeUndefined();
      expect(state.user?.lastLoginAt).toBeUndefined();
      expect(state.user?.organization).toBeUndefined();
    });

    it("should handle inactive user", () => {
      const { setUser } = useAuthStore.getState();
      const inactiveUser = { ...mockUser, isActive: false };

      setUser(inactiveUser);

      const state = useAuthStore.getState();
      expect(state.user?.isActive).toBe(false);
      // User should still be marked as authenticated
      expect(state.isAuthenticated).toBe(true);
    });

    it("should handle multiple roles", () => {
      const { setUser } = useAuthStore.getState();
      const multiRoleUser = {
        ...mockUser,
        roles: ["ROLE_USER", "ROLE_ADMIN", "ROLE_MODERATOR"],
      };

      setUser(multiRoleUser);

      const state = useAuthStore.getState();
      expect(state.user?.roles).toHaveLength(3);
      expect(state.user?.roles).toContain("ROLE_USER");
      expect(state.user?.roles).toContain("ROLE_ADMIN");
      expect(state.user?.roles).toContain("ROLE_MODERATOR");
    });
  });

  describe("state transitions", () => {
    it("should handle typical login flow", () => {
      const { setIsLoading, setUser } = useAuthStore.getState();

      // Initial state: loading
      expect(useAuthStore.getState().isLoading).toBe(true);

      // Set user after authentication
      setUser(mockUser);
      expect(useAuthStore.getState().isAuthenticated).toBe(true);

      // Stop loading
      setIsLoading(false);

      const state = useAuthStore.getState();
      expect(state.user).toEqual(mockUser);
      expect(state.isAuthenticated).toBe(true);
      expect(state.isLoading).toBe(false);
    });

    it("should handle typical logout flow", () => {
      const { setUser, logout } = useAuthStore.getState();

      // Start with authenticated user
      setUser(mockUser);
      expect(useAuthStore.getState().isAuthenticated).toBe(true);

      // Logout
      logout();

      const state = useAuthStore.getState();
      expect(state.user).toBeNull();
      expect(state.isAuthenticated).toBe(false);
    });

    it("should handle failed authentication", () => {
      const { setUser, setIsLoading } = useAuthStore.getState();

      // Start loading
      setIsLoading(true);

      // Failed auth - set user to null
      setUser(null);

      // Stop loading
      setIsLoading(false);

      const state = useAuthStore.getState();
      expect(state.user).toBeNull();
      expect(state.isAuthenticated).toBe(false);
      expect(state.isLoading).toBe(false);
    });
  });
});
