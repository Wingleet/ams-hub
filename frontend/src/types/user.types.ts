/**
 * User and Authentication related types
 */

export interface User {
  id: number;
  email: string;
  username?: string | null;
  firstName: string;
  lastName: string;
  fullName: string;
  roles: string[];
  isAdmin: boolean;
  isActive: boolean;
  createdAt?: string;
  lastLoginAt?: string;
  organization?: Organization | null;
}

export interface Organization {
  id: number;
  name: string;
  isActive: boolean;
}

export interface LoginFormData {
  username: string;
  password: string;
  rememberMe: boolean;
}

export interface RegisterFormData {
  email: string;
  password: string;
  firstName: string;
  lastName: string;
}

export interface LoginData {
  username: string;
  password: string;
  rememberMe?: boolean;
}

export interface FormErrors {
  [key: string]: string;
}
