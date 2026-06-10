import type { Application } from "./application.types";

export interface Subscription {
  id: number;
  isActive: boolean;
  startDate: string | null;
  endDate: string | null;
  application: Application;
}
