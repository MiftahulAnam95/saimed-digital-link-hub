import { randomUUID } from "node:crypto";
import { db } from "@/db";
import { links } from "@/db/schema";
import { eq } from "drizzle-orm";
import type { NextRequest } from "next/server";
import { isAdminApiRequest } from "@/lib/auth";
import { getAllLinks } from "@/lib/data";
import { safeExternalUrl, type LinkIcon } from "@/lib/constants";

export const dynamic = "force-dynamic";

const ICONS = new Set<LinkIcon>(["website", "shopee", "tokopedia", "whatsapp", "link"]);

function unauthorized() {
  return Response.json({ error: "Akses ditolak" }, { status: 401 });
}

async function readBody(request: NextRequest): Promise<Record<string, unknown>> {
  try {
    return (await request.json()) as Record<string, unknown>;
  } catch {
    return {};
  }
}

function makeKey(title: string) {
  const base = title
    .toLowerCase()
    .normalize("NFD")
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/^-|-$/g, "")
    .slice(0, 38);
  return `${base || "tautan"}-${randomUUID().slice(0, 6)}`;
}

export async function GET(request: NextRequest) {
  if (!isAdminApiRequest(request)) return unauthorized();
  return Response.json({ links: await getAllLinks() });
}

export async function POST(request: NextRequest) {
  if (!isAdminApiRequest(request)) return unauthorized();
  const body = await readBody(request);
  const title = typeof body.title === "string" ? body.title.trim().slice(0, 80) : "";
  const url = typeof body.url === "string" ? safeExternalUrl(body.url) : null;
  const icon = ICONS.has(body.icon as LinkIcon) ? (body.icon as LinkIcon) : "link";

  if (!title || !url) {
    return Response.json(
      { ok: false, error: "Nama tautan dan URL https yang valid wajib diisi." },
      { status: 400 },
    );
  }

  try {
    const existing = await getAllLinks();
    const sortOrder = Math.max(0, ...existing.map((link) => link.sortOrder)) + 1;
    const [created] = await db
      .insert(links)
      .values({ key: makeKey(title), title, url, icon, sortOrder })
      .returning();
    return Response.json({ ok: true, link: created }, { status: 201 });
  } catch {
    return Response.json({ ok: false, error: "Gagal menambah tautan." }, { status: 500 });
  }
}

export async function PATCH(request: NextRequest) {
  if (!isAdminApiRequest(request)) return unauthorized();
  const body = await readBody(request);
  const id = Number(body.id);
  if (!Number.isInteger(id) || id <= 0) {
    return Response.json({ ok: false, error: "ID tidak valid." }, { status: 400 });
  }

  const updates: Partial<typeof links.$inferInsert> = { updatedAt: new Date() };

  if (typeof body.title === "string" && body.title.trim()) {
    updates.title = body.title.trim().slice(0, 80);
  }
  if (typeof body.url === "string") {
    const url = safeExternalUrl(body.url);
    if (!url) {
      return Response.json({ ok: false, error: "URL harus memakai http atau https." }, { status: 400 });
    }
    updates.url = url;
  }
  if (ICONS.has(body.icon as LinkIcon)) updates.icon = body.icon as LinkIcon;
  if (typeof body.isActive === "boolean") updates.isActive = body.isActive;
  if (typeof body.featured === "boolean") updates.featured = body.featured;
  if (Number.isFinite(Number(body.sortOrder))) {
    updates.sortOrder = Math.trunc(Number(body.sortOrder));
  }

  try {
    const [updated] = await db
      .update(links)
      .set(updates)
      .where(eq(links.id, id))
      .returning();
    if (!updated) {
      return Response.json({ ok: false, error: "Tautan tidak ditemukan." }, { status: 404 });
    }
    return Response.json({ ok: true, link: updated });
  } catch {
    return Response.json({ ok: false, error: "Gagal menyimpan tautan." }, { status: 500 });
  }
}

export async function DELETE(request: NextRequest) {
  if (!isAdminApiRequest(request)) return unauthorized();
  const body = await readBody(request);
  const id = Number(request.nextUrl.searchParams.get("id") ?? body.id);
  if (!Number.isInteger(id) || id <= 0) {
    return Response.json({ ok: false, error: "ID tidak valid." }, { status: 400 });
  }

  try {
    await db.delete(links).where(eq(links.id, id));
    return Response.json({ ok: true });
  } catch {
    return Response.json({ ok: false, error: "Gagal menghapus tautan." }, { status: 500 });
  }
}
