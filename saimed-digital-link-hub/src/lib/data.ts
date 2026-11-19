import { db } from "@/db";
import { links, type LinkRow } from "@/db/schema";
import { asc, eq } from "drizzle-orm";
import {
  DEFAULT_LINKS,
  safeExternalUrl,
  type LinkIcon,
  type PublicLink,
} from "@/lib/constants";

function toPublicLink(row: LinkRow): PublicLink {
  return {
    id: row.id,
    key: row.key,
    title: row.title,
    url: safeExternalUrl(row.url) ?? "#",
    icon: row.icon as LinkIcon,
    sortOrder: row.sortOrder,
    featured: row.featured,
    isActive: row.isActive,
  };
}

function fallbackLinks(): PublicLink[] {
  return DEFAULT_LINKS.map((link, index) => ({ id: -(index + 1), ...link }));
}

export async function ensureLinksSeeded(): Promise<LinkRow[]> {
  try {
    const existing = await db
      .select()
      .from(links)
      .orderBy(asc(links.sortOrder), asc(links.id));
    if (existing.length > 0) return existing;

    await db
      .insert(links)
      .values(
        DEFAULT_LINKS.map((link) => ({
          key: link.key,
          title: link.title,
          url: link.url,
          icon: link.icon,
          sortOrder: link.sortOrder,
          featured: link.featured,
          isActive: link.isActive,
        })),
      )
      .onConflictDoNothing();

    return await db
      .select()
      .from(links)
      .orderBy(asc(links.sortOrder), asc(links.id));
  } catch {
    return [];
  }
}

export async function getPublicLinks(): Promise<PublicLink[]> {
  const rows = await ensureLinksSeeded();
  if (rows.length === 0) return fallbackLinks();
  return rows.filter((row) => row.isActive).map(toPublicLink);
}

export async function getAllLinks(): Promise<PublicLink[]> {
  const rows = await ensureLinksSeeded();
  if (rows.length === 0) return fallbackLinks();
  return rows.map(toPublicLink);
}

export async function findLinkById(id: number): Promise<LinkRow | null> {
  try {
    const found = await db.select().from(links).where(eq(links.id, id)).limit(1);
    return found[0] ?? null;
  } catch {
    return null;
  }
}
