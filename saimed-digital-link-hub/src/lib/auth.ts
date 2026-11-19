import { createHash, createHmac, timingSafeEqual } from "node:crypto";
import { cookies } from "next/headers";
import type { NextRequest } from "next/server";

export const ADMIN_COOKIE_NAME = "saimed_admin_session";
const SESSION_MS = 7 * 24 * 60 * 60 * 1000;

function sessionSecret(): string {
  const secret = process.env.ADMIN_SESSION_SECRET;
  if (!secret) throw new Error("ADMIN_SESSION_SECRET belum dikonfigurasi");
  return secret;
}

function digest(value: string): Buffer {
  return createHash("sha256").update(value).digest();
}

export function createAdminToken(): string {
  const expires = Date.now() + SESSION_MS;
  const signature = createHmac("sha256", sessionSecret())
    .update(String(expires))
    .digest("hex");
  return `${expires}.${signature}`;
}

export function isValidAdminToken(token?: string | null): boolean {
  if (!token) return false;
  const [expiresRaw, signature] = token.split(".");
  if (!/^\d+$/.test(expiresRaw ?? "") || !signature) return false;
  if (Number(expiresRaw) < Date.now()) return false;

  let expected: string;
  try {
    expected = createHmac("sha256", sessionSecret())
      .update(expiresRaw)
      .digest("hex");
  } catch {
    return false;
  }

  const actualBuffer = Buffer.from(signature, "hex");
  const expectedBuffer = Buffer.from(expected, "hex");
  return (
    actualBuffer.length === expectedBuffer.length &&
    timingSafeEqual(actualBuffer, expectedBuffer)
  );
}

export function passwordMatches(password: string): boolean {
  const configured = process.env.ADMIN_PASSWORD;
  if (!configured) return false;
  return timingSafeEqual(digest(password), digest(configured));
}

export async function isAdminPageRequest(): Promise<boolean> {
  const jar = await cookies();
  return isValidAdminToken(jar.get(ADMIN_COOKIE_NAME)?.value);
}

export function isAdminApiRequest(request: NextRequest): boolean {
  return isValidAdminToken(request.cookies.get(ADMIN_COOKIE_NAME)?.value);
}

export const SESSION_MAX_AGE = SESSION_MS / 1000;
