import { makeApiRequest } from "./apiService";
import type { AuthApiResponse, RegisterFormData, LoginData } from "../types";

class AuthService {
  async register(data: RegisterFormData): Promise<AuthApiResponse> {
    const result = await makeApiRequest("/api/auth/register", {
      method: "POST",
      body: JSON.stringify(data),
    });
    // Backend returns { success, user, message } directly in data
    return result.data || result;
  }

  async login(data: LoginData): Promise<AuthApiResponse> {
    const result = await makeApiRequest("/api/auth/login", {
      method: "POST",
      body: JSON.stringify(data),
    });
    return result.data || result;
  }

  async logout(): Promise<AuthApiResponse> {
    const result = await makeApiRequest("/api/auth/logout", {
      method: "POST",
    });
    return result.data || result;
  }

  async getCurrentUser(): Promise<AuthApiResponse> {
    const result = await makeApiRequest("/api/auth/me", {
      method: "GET",
    });
    return result.data || result;
  }

  async refreshToken(): Promise<AuthApiResponse> {
    const result = await makeApiRequest("/api/auth/refresh", {
      method: "POST",
    });
    return result.data || result;
  }
}

export const authService = new AuthService();
