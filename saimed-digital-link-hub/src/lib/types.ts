import type { PublicLink } from "@/lib/constants";

export type AdminLink = PublicLink;

export type DailyPoint = {
  date: string;
  label: string;
  views: number;
  clicks: number;
};

export type BreakdownItem = {
  name: string;
  count: number;
  percentage: number;
};

export type LinkStat = {
  id: number;
  key: string;
  title: string;
  icon: string;
  featured: boolean;
  isActive: boolean;
  clicks: number;
  share: number;
  lastClicked: string | null;
};

export type RecentEvent = {
  id: number;
  eventType: string;
  label: string;
  device: string;
  visitorId: string;
  createdAt: string;
  isDemo: boolean;
};

export type AnalyticsSummary = {
  days: number;
  totalViews: number;
  totalClicks: number;
  uniqueVisitors: number;
  ctr: number;
  avgDailyViews: number;
  demoCount: number;
  daily: DailyPoint[];
  devices: BreakdownItem[];
  browsers: BreakdownItem[];
  linkStats: LinkStat[];
  recent: RecentEvent[];
  generatedAt: string;
};
