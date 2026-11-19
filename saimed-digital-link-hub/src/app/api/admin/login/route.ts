import { cookies } from "next/headers";
import type { NextRequest } from "next/server";
import {
  ADMIN_COOKIE_NAME,
  createAdminToken,
  passwordMatches,
  SESSION_MAX_AGE,
} from "@/lib/auth";

export const dynamic = "force-dynamic";

export async function POST(request: NextRequest) {
  let password = "";
  try {
    const body = (await request.json()) as { password?: unknown };
    password = typeof body.password === "string" ? body.password : "";
  } catch {
    return Response.json({ ok: false }, { status: 400 });
  }

  if (!passwordMatches(password)) {
    return Response.json(
      { ok: false, error: "Kata sandi administrator tidak sesuai." },
      { status: 401 },
    );
  }

  const jar = await cookies();
  jar.set(ADMIN_COOKIE_NAME, createAdminToken(), {
    httpOnly: true,
    sameSite: "lax",
    secure: request.nextUrl.protocol === "https:",
    path: "/",
    maxAge: SESSION_MAX_AGE,
  });

  return Response.json({ ok: true });
}
