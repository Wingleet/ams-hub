/**
 * Organization types
 */

export interface OrganizationMember {
  id: number;
  email: string;
  firstName: string;
  lastName: string;
  roles?: string[];
  createdAt: string;
}

export interface CreateOrganizationUserData {
  email: string;
  firstName: string;
  lastName: string;
  password: string;
}
