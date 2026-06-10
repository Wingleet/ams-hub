import { useEffect, useRef } from "react";
import { useAuthStore } from "../store/authStore";
import { authService } from "../services/authService";

export const useAuth = () => {
  const { user, isAuthenticated, isLoading, setUser, setIsLoading, logout } =
    useAuthStore();
  const hasCheckedAuth = useRef(false);

  useEffect(() => {
    // Check if user is authenticated on mount - only once
    // Skip check if already checked or if on login page
    if (hasCheckedAuth.current || window.location.pathname === "/login") {
      if (window.location.pathname === "/login") {
        setIsLoading(false);
      }
      return;
    }
    hasCheckedAuth.current = true;

    const checkAuth = async () => {
      try {
        const response = await authService.getCurrentUser();
        if (response.success && response.user) {
          setUser(response.user);
        } else {
          setUser(null);
        }
      } catch (error) {
        console.error("Failed to check authentication:", error);
        setUser(null);
      } finally {
        setIsLoading(false);
      }
    };

    checkAuth();
  }, []);

  const handleLogout = async () => {
    try {
      await authService.logout();
    } catch (error) {
      console.error("Logout API call failed:", error);
    } finally {
      // Local logout in all cases
      logout();
      // Redirect to login page
      window.location.href = "/login";
    }
  };

  return {
    user,
    isAuthenticated,
    isLoading,
    logout: handleLogout,
  };
};
