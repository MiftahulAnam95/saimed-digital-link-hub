import type { NextRequest } from "next/server";
import { getAnalytics } from "@/lib/analytics";
import { isAdminApiRequest } from "@/lib/auth";

export const dynamic = "force-dynamic";

export async function GET(request: NextRequest) {
  if (!isAdminApiRequest(request)) {
    return Response.json({ error: "Akses ditolak" }, { status: 401 });
  }

  const days = Number(request.nextUrl.searchParams.get("days") ?? "7");
  const summary = await getAnalytics(Number.isFinite(days) ? days : 7);
  return Response.json(summary, {
    headers: { "Cache-Control": "no-store" },
  });
}
