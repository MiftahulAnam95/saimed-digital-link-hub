import { db } from "@/db";
import {
  analyticsEvents,
  type AnalyticsEventRow,
  type NewAnalyticsEvent,
} from "@/db/schema";
import { desc, eq, gte } from "drizzle-orm";
import { ensureLinksSeeded } from "@/lib/data";
import type {
  AnalyticsSummary,
  BreakdownItem,
  DailyPoint,
  LinkStat,
  RecentEvent,
} from "@/lib/types";

export type TrackEventInput = {
  eventType: "view" | "link_click" | "social_click" | "link_action";
  visitorId: string;
  linkKey?: string | null;
  targetLabel?: string | null;
  path?: string;
  referrer?: string | null;
  userAgent?: string | null;
  device?: string;
  browser?: string;
  os?: string;
};

export function parseUserAgent(userAgent: string | null) {
  const ua = (userAgent ?? "").toLowerCase();
  const device = /ipad|tablet/.test(ua)
    ? "Tablet"
    : /iphone|android|mobile|windows phone/.test(ua)
      ? "Mobile"
      : "Desktop";
  const browser = /edg\//.test(ua)
    ? "Edge"
    : /opr\/|opera/.test(ua)
      ? "Opera"
      : /firefox\//.test(ua)
        ? "Firefox"
        : /chrome\/|crios\//.test(ua)
          ? "Chrome"
          : /safari\//.test(ua)
            ? "Safari"
            : "Lainnya";
  const os = /android/.test(ua)
    ? "Android"
    : /iphone|ipad|ipod/.test(ua)
      ? "iOS"
      : /windows/.test(ua)
        ? "Windows"
        : /mac os|macintosh/.test(ua)
          ? "macOS"
          : /linux/.test(ua)
            ? "Linux"
            : "Lainnya";
  return { device, browser, os };
}

export async function persistEvent(input: TrackEventInput): Promise<boolean> {
  try {
    let linkId: number | null = null;
    if (input.linkKey) {
      const allLinks = await ensureLinksSeeded();
      linkId = allLinks.find((link) => link.key === input.linkKey)?.id ?? null;
    }

    const values: NewAnalyticsEvent = {
      eventType: input.eventType,
      linkId,
      linkKey: input.linkKey?.slice(0, 120) ?? null,
      targetLabel: input.targetLabel?.slice(0, 180) ?? null,
      visitorId: input.visitorId.slice(0, 120) || "anonymous",
      path: input.path?.slice(0, 240) ?? "/",
      referrer: input.referrer?.slice(0, 500) ?? null,
      userAgent: input.userAgent?.slice(0, 700) ?? null,
      device: input.device ?? "Desktop",
      browser: input.browser ?? "Lainnya",
      os: input.os ?? "Lainnya",
      isDemo: false,
    };

    await db.insert(analyticsEvents).values(values);
    return true;
  } catch {
    return false;
  }
}

function dateKey(date: Date): string {
  return date.toISOString().slice(0, 10);
}

function buildBreakdown(
  events: AnalyticsEventRow[],
  key: "device" | "browser",
): BreakdownItem[] {
  const counter = new Map<string, number>();
  for (const event of events) {
    const name = key === "device" ? event.device : event.browser;
    counter.set(name || "Lainnya", (counter.get(name || "Lainnya") ?? 0) + 1);
  }
  const total = events.length;
  return [...counter.entries()]
    .map(([name, count]) => ({
      name,
      count,
      percentage: total ? Math.round((count / total) * 100) : 0,
    }))
    .sort((a, b) => b.count - a.count)
    .slice(0, 5);
}

function emptyDaily(days: number, start: Date): DailyPoint[] {
  return Array.from({ length: days }, (_, index) => {
    const date = new Date(start);
    date.setUTCDate(start.getUTCDate() + index);
    return {
      date: dateKey(date),
      label: new Intl.DateTimeFormat("id-ID", {
        weekday: "short",
        timeZone: "UTC",
      }).format(date),
      views: 0,
      clicks: 0,
    };
  });
}

export async function getAnalytics(requestedDays = 7): Promise<AnalyticsSummary> {
  const days = [7, 14, 30].includes(requestedDays) ? requestedDays : 7;
  const start = new Date();
  start.setUTCHours(0, 0, 0, 0);
  start.setUTCDate(start.getUTCDate() - (days - 1));

  let events: AnalyticsEventRow[] = [];
  let allLinks: Awaited<ReturnType<typeof ensureLinksSeeded>> = [];
  try {
    allLinks = await ensureLinksSeeded();
    events = await db
      .select()
      .from(analyticsEvents)
      .where(gte(analyticsEvents.createdAt, start))
      .orderBy(desc(analyticsEvents.createdAt));
  } catch {
    events = [];
    allLinks = [];
  }

  const daily = emptyDaily(days, start);
  const dailyMap = new Map(daily.map((point) => [point.date, point]));
  const views = events.filter((event) => event.eventType === "view");
  const clicks = events.filter(
    (event) =>
      event.eventType === "link_click" || event.eventType === "social_click",
  );

  for (const event of events) {
    const point = dailyMap.get(dateKey(new Date(event.createdAt)));
    if (!point) continue;
    if (event.eventType === "view") point.views += 1;
    if (event.eventType === "link_click" || event.eventType === "social_click") {
      point.clicks += 1;
    }
  }

  const allActivity = [...views, ...clicks];
  const uniqueVisitors = new Set(allActivity.map((event) => event.visitorId)).size;
  const totalClicks = clicks.length;

  const linkStats: LinkStat[] = allLinks.map((link) => {
    const matching = clicks.filter(
      (event) =>
        event.eventType === "link_click" &&
        (event.linkKey === link.key || event.linkId === link.id),
    );
    return {
      id: link.id,
      key: link.key,
      title: link.title,
      icon: link.icon,
      featured: link.featured,
      isActive: link.isActive,
      clicks: matching.length,
      share: totalClicks ? Math.round((matching.length / totalClicks) * 100) : 0,
      lastClicked: matching[0]?.createdAt
        ? new Date(matching[0].createdAt).toISOString()
        : null,
    };
  });

  const recent: RecentEvent[] = events.slice(0, 50).map((event) => ({
    id: event.id,
    eventType: event.eventType,
    label:
      event.targetLabel ??
      event.linkKey ??
      (event.eventType === "view" ? "Halaman publik" : "Tautan"),
    device: event.device,
    visitorId: event.visitorId,
    createdAt: new Date(event.createdAt).toISOString(),
    isDemo: event.isDemo,
  }));

  return {
    days,
    totalViews: views.length,
    totalClicks,
    uniqueVisitors,
    ctr: views.length ? Math.round((totalClicks / views.length) * 1000) / 10 : 0,
    avgDailyViews: Math.round((views.length / days) * 10) / 10,
    demoCount: events.filter((event) => event.isDemo).length,
    daily,
    devices: buildBreakdown(allActivity, "device"),
    browsers: buildBreakdown(allActivity, "browser"),
    linkStats,
    recent,
    generatedAt: new Date().toISOString(),
  };
}

export async function refreshDemoEvents(): Promise<number> {
  const allLinks = await ensureLinksSeeded();
  await db
    .delete(analyticsEvents)
    .where(eq(analyticsEvents.isDemo, true));

  if (allLinks.length === 0) return 0;

  const rows: NewAnalyticsEvent[] = [];
  const today = new Date();
  today.setUTCHours(0, 0, 0, 0);
  const devices = ["Mobile", "Mobile", "Mobile", "Desktop", "Tablet", "Desktop"];
  const browsers = ["Chrome", "Safari", "Chrome", "Edge", "Firefox", "Chrome"];
  const operatingSystems = ["Android", "iOS", "Android", "Windows", "iOS", "macOS"];

  for (let dayOffset = 29; dayOffset >= 0; dayOffset -= 1) {
    const day = new Date(today);
    day.setUTCDate(today.getUTCDate() - dayOffset);
    const viewCount = 14 + ((dayOffset * 7) % 21);

    for (let index = 0; index < viewCount; index += 1) {
      const createdAt = new Date(day);
      createdAt.setUTCHours(8 + ((index * 3) % 12), (index * 17) % 60, 0, 0);
      const visitorId = `demo-${dayOffset}-${index % Math.max(5, viewCount - 6)}`;
      const metaIndex = (dayOffset + index) % devices.length;
      const shared = {
        visitorId,
        path: "/",
        referrer: index % 5 === 0 ? "https://www.instagram.com/" : null,
        userAgent: "SAIMED demo data",
        device: devices[metaIndex],
        browser: browsers[metaIndex],
        os: operatingSystems[metaIndex],
        isDemo: true,
        createdAt,
      };

      rows.push({ ...shared, eventType: "view", linkId: null, linkKey: null, targetLabel: "Halaman publik" });

      if (index % 3 === 0) {
        const link = allLinks[(dayOffset + index) % allLinks.length];
        const clickTime = new Date(createdAt.getTime() + 45_000);
        rows.push({
          ...shared,
          eventType: "link_click",
          linkId: link.id,
          linkKey: link.key,
          targetLabel: link.title,
          createdAt: clickTime,
        });
      }
    }
  }

  await db.insert(analyticsEvents).values(rows);
  return rows.length;
}
