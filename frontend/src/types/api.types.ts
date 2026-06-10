/**
 * API Response and Error types
 */

import type { User } from "./user.types";

export interface ApiResponse<T = any> {
  success: boolean;
  message?: string;
  errors?: Record<string, string>;
  data?: T;
}

/**
 * Auth-specific response — backend returns user directly at root level
 * (not nested in data), e.g. { success, user, message }
 */
export interface AuthApiResponse extends ApiResponse {
  user?: User;
}

export interface ApiErrorResponse {
  success: false;
  message?: string;
  errors?: Record<string, string>;
}
