import type { NextRequest } from "next/server";
import { refreshDemoEvents } from "@/lib/analytics";
import { isAdminApiRequest } from "@/lib/auth";

export const dynamic = "force-dynamic";

export async function POST(request: NextRequest) {
  if (!isAdminApiRequest(request)) {
    return Response.json({ error: "Akses ditolak" }, { status: 401 });
  }

  try {
    const count = await refreshDemoEvents();
    return Response.json({ ok: true, count });
  } catch {
    return Response.json(
      { ok: false, error: "Tabel analytics belum siap. Jalankan migrasi database." },
      { status: 500 },
    );
  }
}
