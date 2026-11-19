import {
  boolean,
  integer,
  pgTable,
  serial,
  text,
  timestamp,
} from "drizzle-orm/pg-core";

export const links = pgTable("links", {
  id: serial("id").primaryKey(),
  key: text("key").notNull().unique(),
  title: text("title").notNull(),
  url: text("url").notNull(),
  icon: text("icon").notNull().default("link"),
  sortOrder: integer("sort_order").notNull().default(0),
  isActive: boolean("is_active").notNull().default(true),
  featured: boolean("featured").notNull().default(false),
  createdAt: timestamp("created_at", { withTimezone: true }).notNull().defaultNow(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).notNull().defaultNow(),
});

export const analyticsEvents = pgTable("analytics_events", {
  id: serial("id").primaryKey(),
  eventType: text("event_type").notNull(),
  linkId: integer("link_id").references(() => links.id, { onDelete: "set null" }),
  linkKey: text("link_key"),
  targetLabel: text("target_label"),
  visitorId: text("visitor_id").notNull(),
  path: text("path").notNull().default("/"),
  referrer: text("referrer"),
  userAgent: text("user_agent"),
  device: text("device").notNull().default("Desktop"),
  browser: text("browser").notNull().default("Lainnya"),
  os: text("os").notNull().default("Lainnya"),
  isDemo: boolean("is_demo").notNull().default(false),
  createdAt: timestamp("created_at", { withTimezone: true }).notNull().defaultNow(),
});

export type LinkRow = typeof links.$inferSelect;
export type AnalyticsEventRow = typeof analyticsEvents.$inferSelect;
export type NewAnalyticsEvent = typeof analyticsEvents.$inferInsert;
