/**
 * Application types
 */

export interface Application {
  id: number;
  name: string;
  description?: string | null;
  url?: string | null;
  iconUrl?: string | null;
  databaseName?: string | null;
  isActive: boolean;
  isSubscribed?: boolean;
  createdAt?: string;
  updatedAt?: string;
}
