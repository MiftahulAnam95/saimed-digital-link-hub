import { createHash } from "node:crypto";
import type { NextRequest } from "next/server";
import { parseUserAgent, persistEvent, type TrackEventInput } from "@/lib/analytics";

export const dynamic = "force-dynamic";

const EVENT_TYPES = new Set<TrackEventInput["eventType"]>([
  "view",
  "link_click",
  "social_click",
  "link_action",
]);

function cleanString(value: unknown, max = 300): string | null {
  return typeof value === "string" ? value.trim().slice(0, max) || null : null;
}

export async function POST(request: NextRequest) {
  let body: Record<string, unknown>;
  try {
    body = (await request.json()) as Record<string, unknown>;
  } catch {
    return Response.json({ ok: false, error: "Body tidak valid" }, { status: 400 });
  }

  const eventType = cleanString(body.eventType, 40) as TrackEventInput["eventType"];
  if (!EVENT_TYPES.has(eventType)) {
    return Response.json({ ok: false, error: "Jenis event tidak valid" }, { status: 400 });
  }

  const userAgent = request.headers.get("user-agent");
  const parsed = parseUserAgent(userAgent);
  const ip = request.headers.get("x-forwarded-for") ?? "local";
  const fallbackVisitor = `anon-${createHash("sha256")
    .update(`${ip}:${userAgent ?? ""}:${new Date().getUTCDate()}`)
    .digest("hex")
    .slice(0, 24)}`;

  const tracked = await persistEvent({
    eventType,
    visitorId: cleanString(body.visitorId, 120) ?? fallbackVisitor,
    linkKey: cleanString(body.linkKey, 120),
    targetLabel: cleanString(body.targetLabel, 180),
    path: cleanString(body.path, 240) ?? "/",
    referrer: cleanString(body.referrer, 500) ?? request.headers.get("referer"),
    userAgent,
    device: cleanString(body.device, 30) ?? parsed.device,
    browser: cleanString(body.browser, 30) ?? parsed.browser,
    os: cleanString(body.os, 30) ?? parsed.os,
  });

  return Response.json({ ok: true, tracked }, { status: tracked ? 200 : 202 });
}
