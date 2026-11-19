"use client";

import {
  Activity,
  ArrowDown,
  ArrowUp,
  BarChart3,
  Clock3,
  Database,
  ExternalLink,
  Eye,
  Globe,
  Link2,
  LogOut,
  MousePointerClick,
  Plus,
  RefreshCw,
  Save,
  Settings2,
  Trash2,
  TrendingUp,
  Users,
} from "lucide-react";
import Link from "next/link";
import {
  useCallback,
  useEffect,
  useState,
  type FormEvent,
  type ReactNode,
} from "react";
import { SaimedAvatar, PlatformIcon } from "@/components/BrandVisuals";
import { displayHost, type LinkIcon } from "@/lib/constants";
import type { AdminLink, AnalyticsSummary } from "@/lib/types";

const JSON_HEADERS = { "Content-Type": "application/json" };
const numberFormat = new Intl.NumberFormat("id-ID");

const EVENT_LABELS: Record<string, string> = {
  view: "Halaman dibuka",
  link_click: "Klik tautan",
  social_click: "Klik media sosial",
  link_action: "Aksi tautan",
};

type TabId = "overview" | "links" | "activity";

async function requestJson<T>(url: string, init?: RequestInit): Promise<T> {
  const response = await fetch(url, { cache: "no-store", ...init });
  const data = (await response.json().catch(() => ({}))) as T & { error?: string };
  if (!response.ok) throw new Error(data.error ?? "Permintaan gagal");
  return data;
}

function formatDateTime(value: string) {
  return new Intl.DateTimeFormat("id-ID", {
    dateStyle: "medium",
    timeStyle: "short",
    timeZone: "Asia/Jakarta",
  }).format(new Date(value));
}

function KpiCard({
  icon,
  label,
  value,
  hint,
  accent,
}: {
  icon: ReactNode;
  label: string;
  value: string;
  hint: string;
  accent: string;
}) {
  return (
    <div className="rounded-[22px] border border-[#dce5dc] bg-white p-5 shadow-[0_8px_24px_rgba(18,50,39,0.05)]">
      <div className="flex items-start justify-between">
        <span className={`grid h-10 w-10 place-items-center rounded-xl ${accent}`}>{icon}</span>
        <TrendingUp size={17} className="text-[#9ab3a8]" />
      </div>
      <p className="mt-5 text-xs font-bold uppercase tracking-[0.14em] text-[#789087]">{label}</p>
      <p className="mt-1 text-3xl font-extrabold tracking-tight text-[#102a22]">{value}</p>
      <p className="mt-2 text-xs text-[#789087]">{hint}</p>
    </div>
  );
}

function ActivityTable({ summary }: { summary: AnalyticsSummary }) {
  return (
    <div className="overflow-x-auto">
      <table className="w-full min-w-[650px] text-left text-sm">
        <thead>
          <tr className="border-b border-[#e6ece6] text-xs uppercase tracking-wide text-[#789087]">
            <th className="px-4 py-3 font-bold">Aktivitas</th>
            <th className="px-4 py-3 font-bold">Target</th>
            <th className="px-4 py-3 font-bold">Perangkat</th>
            <th className="px-4 py-3 font-bold">Pengunjung</th>
            <th className="px-4 py-3 font-bold">Waktu</th>
          </tr>
        </thead>
        <tbody>
          {summary.recent.length === 0 ? (
            <tr>
              <td colSpan={5} className="px-4 py-10 text-center text-[#789087]">
                Belum ada aktivitas. Buka halaman publik atau muat data contoh.
              </td>
            </tr>
          ) : (
            summary.recent.map((event) => (
              <tr key={event.id} className="border-b border-[#f0f3ef] last:border-0">
                <td className="px-4 py-3">
                  <span className="inline-flex items-center gap-2 font-semibold text-[#214c3e]">
                    <span className={`h-2 w-2 rounded-full ${event.eventType === "view" ? "bg-[#41aaa2]" : "bg-[#0b8a62]"}`} />
                    {EVENT_LABELS[event.eventType] ?? event.eventType}
                  </span>
                </td>
                <td className="px-4 py-3 font-medium text-[#102a22]">{event.label}</td>
                <td className="px-4 py-3 text-[#617b70]">{event.device}</td>
                <td className="px-4 py-3 font-mono text-xs text-[#789087]">
                  {event.visitorId.slice(0, 14)}
                </td>
                <td className="px-4 py-3 whitespace-nowrap text-[#617b70]">
                  {formatDateTime(event.createdAt)}
                </td>
              </tr>
            ))
          )}
        </tbody>
      </table>
    </div>
  );
}

function LinkEditor({
  link,
  index,
  total,
  onPatch,
  onDelete,
  onMove,
  onNotice,
}: {
  link: AdminLink;
  index: number;
  total: number;
  onPatch: (id: number, updates: Record<string, unknown>) => Promise<void>;
  onDelete: (id: number) => Promise<void>;
  onMove: (id: number, direction: "up" | "down") => Promise<void>;
  onNotice: (message: string) => void;
}) {
  const [title, setTitle] = useState(link.title);
  const [url, setUrl] = useState(link.url);
  const [icon, setIcon] = useState<LinkIcon>(link.icon);
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    setTitle(link.title);
    setUrl(link.url);
    setIcon(link.icon);
  }, [link.id, link.title, link.url, link.icon]);

  async function run(action: () => Promise<void>, message: string) {
    setSaving(true);
    try {
      await action();
      onNotice(message);
    } catch (error) {
      onNotice(error instanceof Error ? error.message : "Gagal menyimpan");
    } finally {
      setSaving(false);
    }
  }

  const fieldClass =
    "w-full rounded-xl border border-[#dce5dc] bg-[#fbfcf9] px-3 py-2.5 text-sm outline-none focus:border-[#0b7c60]";

  return (
    <div className="rounded-[20px] border border-[#dce5dc] bg-white p-4">
      <div className="grid gap-3 lg:grid-cols-[1.1fr_1.6fr_0.8fr_auto]">
        <label className="block">
          <span className="mb-1.5 block text-[11px] font-bold uppercase tracking-wide text-[#789087]">Nama</span>
          <input value={title} onChange={(event) => setTitle(event.target.value)} className={fieldClass} />
        </label>
        <label className="block">
          <span className="mb-1.5 block text-[11px] font-bold uppercase tracking-wide text-[#789087]">URL tujuan</span>
          <input value={url} onChange={(event) => setUrl(event.target.value)} className={fieldClass} />
        </label>
        <label className="block">
          <span className="mb-1.5 block text-[11px] font-bold uppercase tracking-wide text-[#789087]">Ikon</span>
          <select
            value={icon}
            onChange={(event) => setIcon(event.target.value as LinkIcon)}
            className={fieldClass}
          >
            <option value="website">Website</option>
            <option value="shopee">Shopee</option>
            <option value="tokopedia">Tokopedia</option>
            <option value="whatsapp">WhatsApp</option>
            <option value="link">Umum</option>
          </select>
        </label>
        <div className="flex items-end gap-1.5">
          <button
            type="button"
            disabled={saving}
            onClick={() =>
              void run(
                () => onPatch(link.id, { title, url, icon }),
                "Tautan berhasil diperbarui",
              )
            }
            className="inline-flex h-[42px] items-center gap-1.5 rounded-xl bg-[#0b6149] px-3.5 text-sm font-bold text-white hover:bg-[#084d3a] disabled:opacity-50"
          >
            <Save size={15} /> Simpan
          </button>
        </div>
      </div>

      <div className="mt-4 flex flex-wrap items-center gap-2 border-t border-[#eef2ed] pt-4">
        <span className="mr-1 inline-flex items-center gap-2 rounded-lg bg-[#f2f7f3] px-2.5 py-1.5 text-xs font-medium text-[#577267]">
          <PlatformIcon name={icon} size={18} /> {displayHost(url)}
        </span>
        <button
          type="button"
          onClick={() =>
            void run(
              () => onPatch(link.id, { isActive: !link.isActive }),
              link.isActive ? "Tautan disembunyikan" : "Tautan diaktifkan",
            )
          }
          className={`rounded-lg px-3 py-1.5 text-xs font-bold ${link.isActive ? "bg-[#e4f5ea] text-[#0b704f]" : "bg-[#f2e9e9] text-[#a34c4c]"}`}
        >
          {link.isActive ? "Aktif" : "Nonaktif"}
        </button>
        <button
          type="button"
          onClick={() =>
            void run(
              () => onPatch(link.id, { featured: !link.featured }),
              "Tampilan unggulan diperbarui",
            )
          }
          className={`rounded-lg px-3 py-1.5 text-xs font-bold ${link.featured ? "bg-[#dceef9] text-[#0b5d92]" : "bg-[#f3f5f2] text-[#70857b]"}`}
        >
          {link.featured ? "Unggulan" : "Biasa"}
        </button>
        <div className="ml-auto flex items-center gap-1">
          <button
            type="button"
            disabled={index === 0 || saving}
            onClick={() => void run(() => onMove(link.id, "up"), "Urutan diperbarui")}
            className="grid h-8 w-8 place-items-center rounded-lg border border-[#dce5dc] text-[#577267] hover:bg-[#f2f7f3] disabled:opacity-35"
            aria-label="Naikkan urutan"
          >
            <ArrowUp size={15} />
          </button>
          <button
            type="button"
            disabled={index === total - 1 || saving}
            onClick={() => void run(() => onMove(link.id, "down"), "Urutan diperbarui")}
            className="grid h-8 w-8 place-items-center rounded-lg border border-[#dce5dc] text-[#577267] hover:bg-[#f2f7f3] disabled:opacity-35"
            aria-label="Turunkan urutan"
          >
            <ArrowDown size={15} />
          </button>
          <a
            href={link.url}
            target="_blank"
            rel="noopener noreferrer"
            className="grid h-8 w-8 place-items-center rounded-lg border border-[#dce5dc] text-[#577267] hover:bg-[#f2f7f3]"
            aria-label={`Buka ${link.title}`}
          >
            <ExternalLink size={14} />
          </a>
          <button
            type="button"
            onClick={() => {
              if (window.confirm(`Hapus tautan "${link.title}"?`)) {
                void run(() => onDelete(link.id), "Tautan dihapus");
              }
            }}
            className="grid h-8 w-8 place-items-center rounded-lg border border-[#f0d7d7] text-[#b45b5b] hover:bg-[#fff2f2]"
            aria-label={`Hapus ${link.title}`}
          >
            <Trash2 size={14} />
          </button>
        </div>
      </div>
    </div>
  );
}

export default function AdminDashboard() {
  const [tab, setTab] = useState<TabId>("overview");
  const [days, setDays] = useState(7);
  const [summary, setSummary] = useState<AnalyticsSummary | null>(null);
  const [adminLinks, setAdminLinks] = useState<AdminLink[]>([]);
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState("");
  const [notice, setNotice] = useState("");
  const [newTitle, setNewTitle] = useState("");
  const [newUrl, setNewUrl] = useState("");
  const [newIcon, setNewIcon] = useState<LinkIcon>("link");

  const loadAll = useCallback(async () => {
    setLoading(true);
    try {
      const [analyticsResponse, linksResponse] = await Promise.all([
        fetch(`/api/admin/analytics?days=${days}`, { cache: "no-store" }),
        fetch("/api/admin/links", { cache: "no-store" }),
      ]);
      if (analyticsResponse.status === 401 || linksResponse.status === 401) {
        window.location.reload();
        return;
      }
      if (!analyticsResponse.ok || !linksResponse.ok) {
        throw new Error("Gagal memuat data analytics");
      }
      const analyticsData = (await analyticsResponse.json()) as AnalyticsSummary;
      const linksData = (await linksResponse.json()) as { links: AdminLink[] };
      setSummary(analyticsData);
      setAdminLinks(linksData.links ?? []);
      setError("");
    } catch (caught) {
      setError(caught instanceof Error ? caught.message : "Gagal memuat data");
    } finally {
      setLoading(false);
    }
  }, [days]);

  useEffect(() => {
    void loadAll();
  }, [loadAll]);

  useEffect(() => {
    if (!notice) return;
    const timer = window.setTimeout(() => setNotice(""), 3200);
    return () => window.clearTimeout(timer);
  }, [notice]);

  async function patchLink(id: number, updates: Record<string, unknown>) {
    await requestJson("/api/admin/links", {
      method: "PATCH",
      headers: JSON_HEADERS,
      body: JSON.stringify({ id, ...updates }),
    });
    await loadAll();
  }

  async function deleteLink(id: number) {
    await requestJson(`/api/admin/links?id=${id}`, { method: "DELETE" });
    await loadAll();
  }

  async function moveLink(id: number, direction: "up" | "down") {
    const index = adminLinks.findIndex((link) => link.id === id);
    const target = adminLinks[index + (direction === "up" ? -1 : 1)];
    if (index < 0 || !target) return;
    await Promise.all([
      requestJson("/api/admin/links", {
        method: "PATCH",
        headers: JSON_HEADERS,
        body: JSON.stringify({ id, sortOrder: target.sortOrder }),
      }),
      requestJson("/api/admin/links", {
        method: "PATCH",
        headers: JSON_HEADERS,
        body: JSON.stringify({ id: target.id, sortOrder: adminLinks[index].sortOrder }),
      }),
    ]);
    await loadAll();
  }

  async function addLink(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setBusy(true);
    try {
      await requestJson("/api/admin/links", {
        method: "POST",
        headers: JSON_HEADERS,
        body: JSON.stringify({ title: newTitle, url: newUrl, icon: newIcon }),
      });
      setNewTitle("");
      setNewUrl("");
      setNewIcon("link");
      setNotice("Tautan baru ditambahkan");
      await loadAll();
    } catch (caught) {
      setError(caught instanceof Error ? caught.message : "Gagal menambah tautan");
    } finally {
      setBusy(false);
    }
  }

  async function loadDemo() {
    setBusy(true);
    try {
      const result = await requestJson<{ ok: boolean; count: number }>(
        "/api/admin/demo",
        { method: "POST" },
      );
      setNotice(`${numberFormat.format(result.count)} event data contoh dimuat`);
      await loadAll();
    } catch (caught) {
      setError(caught instanceof Error ? caught.message : "Gagal memuat data contoh");
    } finally {
      setBusy(false);
    }
  }

  async function logout() {
    await fetch("/api/admin/logout", { method: "POST" });
    window.location.href = "/";
  }

  const maxValue = Math.max(
    1,
    ...(summary?.daily.flatMap((point) => [point.views, point.clicks]) ?? [1]),
  );
  const sortedLinkStats = summary
    ? [...summary.linkStats].sort((a, b) => b.clicks - a.clicks)
    : [];

  const tabs: { id: TabId; label: string; icon: ReactNode }[] = [
    { id: "overview", label: "Ringkasan", icon: <BarChart3 size={16} /> },
    { id: "links", label: "Kelola Tautan", icon: <Link2 size={16} /> },
    { id: "activity", label: "Aktivitas", icon: <Activity size={16} /> },
  ];

  return (
    <div className="min-h-screen bg-[#f4f4ec] text-[#102a22]">
      <header className="sticky top-0 z-30 border-b border-[#dce5dc] bg-[#fbfcf8]/95 backdrop-blur">
        <div className="mx-auto flex max-w-[1280px] flex-wrap items-center gap-4 px-5 py-4 sm:px-8">
          <div className="flex items-center gap-3">
            <SaimedAvatar compact />
            <div>
              <p className="text-[10px] font-bold uppercase tracking-[0.2em] text-[#4aa9a4]">
                Digital Link Hub
              </p>
              <h1 className="text-lg font-extrabold leading-tight tracking-tight sm:text-xl">
                Admin Analytics
              </h1>
            </div>
          </div>
          <div className="ml-auto flex flex-wrap items-center gap-2">
            <span className="inline-flex items-center gap-2 rounded-full border border-[#cfe6d7] bg-[#eef8f1] px-3 py-1.5 text-xs font-bold text-[#0b704f]">
              <span className="h-2 w-2 animate-pulse rounded-full bg-[#1aa66c]" /> Live tracking
            </span>
            <Link
              href="/"
              target="_blank"
              className="inline-flex items-center gap-1.5 rounded-xl border border-[#dce5dc] bg-white px-3 py-2 text-xs font-bold text-[#315f4f] hover:bg-[#f1f6f1]"
            >
              <ExternalLink size={14} /> Halaman Publik
            </Link>
            <button
              type="button"
              onClick={() => void logout()}
              className="inline-flex items-center gap-1.5 rounded-xl border border-[#dce5dc] bg-white px-3 py-2 text-xs font-bold text-[#315f4f] hover:bg-[#f1f6f1]"
            >
              <LogOut size={14} /> Keluar
            </button>
          </div>
        </div>
      </header>

      <main className="mx-auto max-w-[1280px] px-5 py-7 sm:px-8">
        <div className="mb-7 flex flex-wrap items-end justify-between gap-4">
          <div>
            <p className="text-xs font-bold uppercase tracking-[0.18em] text-[#4aa9a4]">
              sentraalkesindonesia
            </p>
            <h2 className="mt-1 text-2xl font-extrabold tracking-tight sm:text-3xl">
              Performa Digital Link Hub
            </h2>
            <p className="mt-1.5 text-sm text-[#6d857b]">
              Pantau kunjungan, klik tautan, perangkat, dan aktivitas pengunjung secara real-time.
            </p>
          </div>
          <div className="flex flex-wrap items-center gap-2">
            <div className="flex rounded-xl border border-[#dce5dc] bg-white p-1">
              {[7, 14, 30].map((value) => (
                <button
                  key={value}
                  type="button"
                  onClick={() => setDays(value)}
                  className={`rounded-lg px-3 py-1.5 text-xs font-bold transition ${days === value ? "bg-[#0b6149] text-white" : "text-[#627c70] hover:bg-[#f1f6f1]"}`}
                >
                  {value} hari
                </button>
              ))}
            </div>
            <button
              type="button"
              onClick={() => void loadAll()}
              className="grid h-[38px] w-[38px] place-items-center rounded-xl border border-[#dce5dc] bg-white text-[#315f4f] hover:bg-[#f1f6f1]"
              aria-label="Muat ulang data"
            >
              <RefreshCw size={16} className={loading ? "animate-spin" : ""} />
            </button>
          </div>
        </div>

        {error && (
          <div className="mb-5 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
            {error}
          </div>
        )}
        {notice && (
          <div className="mb-5 rounded-2xl border border-[#bde5cf] bg-[#ebf9f0] px-4 py-3 text-sm font-medium text-[#0b704f]">
            {notice}
          </div>
        )}

        <div className="mb-6 flex flex-wrap gap-2">
          {tabs.map((item) => (
            <button
              key={item.id}
              type="button"
              onClick={() => setTab(item.id)}
              className={`inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-bold transition ${tab === item.id ? "bg-[#102a22] text-white shadow-[3px_3px_0_#9fd4c8]" : "border border-[#dce5dc] bg-white text-[#577267] hover:bg-[#f1f6f1]"}`}
            >
              {item.icon} {item.label}
            </button>
          ))}
        </div>

        {!summary ? (
          <div className="rounded-[24px] border border-[#dce5dc] bg-white p-12 text-center text-[#6d857b]">
            {loading ? "Memuat analytics..." : "Data belum tersedia."}
          </div>
        ) : tab === "overview" ? (
          <div className="space-y-6">
            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
              <KpiCard
                icon={<Eye size={20} className="text-[#0b704f]" />}
                label="Total Kunjungan"
                value={numberFormat.format(summary.totalViews)}
                hint={`Rata-rata ${summary.avgDailyViews} kunjungan / hari`}
                accent="bg-[#e4f5ea]"
              />
              <KpiCard
                icon={<MousePointerClick size={20} className="text-[#0b5d92]" />}
                label="Total Klik"
                value={numberFormat.format(summary.totalClicks)}
                hint="Klik tautan dan media sosial"
                accent="bg-[#dceef9]"
              />
              <KpiCard
                icon={<Users size={20} className="text-[#9a6a12]" />}
                label="Pengunjung Unik"
                value={numberFormat.format(summary.uniqueVisitors)}
                hint={`Periode ${summary.days} hari terakhir`}
                accent="bg-[#fbf0d7]"
              />
              <KpiCard
                icon={<TrendingUp size={20} className="text-[#7b3fa1]" />}
                label="Click-through Rate"
                value={`${summary.ctr}%`}
                hint="Rasio klik terhadap kunjungan"
                accent="bg-[#f2e6f8]"
              />
            </div>

            <div className="grid gap-6 xl:grid-cols-[1.8fr_1fr]">
              <section className="rounded-[24px] border border-[#dce5dc] bg-white p-6 shadow-[0_8px_24px_rgba(18,50,39,0.04)]">
                <div className="mb-6 flex flex-wrap items-center justify-between gap-3">
                  <div>
                    <h3 className="text-lg font-extrabold">Tren Kunjungan & Klik</h3>
                    <p className="mt-1 text-xs text-[#789087]">Aktivitas harian {summary.days} hari terakhir</p>
                  </div>
                  <div className="flex items-center gap-4 text-xs font-semibold text-[#617b70]">
                    <span className="inline-flex items-center gap-1.5"><span className="h-2.5 w-2.5 rounded-full bg-[#0b6149]" /> Kunjungan</span>
                    <span className="inline-flex items-center gap-1.5"><span className="h-2.5 w-2.5 rounded-full bg-[#48b8ae]" /> Klik</span>
                  </div>
                </div>
                <div className="flex h-[230px] items-end gap-1.5 border-b border-[#e6ece6] pb-1 sm:gap-2.5">
                  {summary.daily.map((point) => (
                    <div key={point.date} className="flex h-full min-w-0 flex-1 flex-col justify-end">
                      <div className="flex h-full items-end justify-center gap-[3px]">
                        <div
                          className="w-2.5 rounded-t-[5px] bg-[#0b6149] transition-all sm:w-3.5"
                          style={{ height: `${Math.max(2, (point.views / maxValue) * 100)}%` }}
                          title={`${point.views} kunjungan`}
                        />
                        <div
                          className="w-2.5 rounded-t-[5px] bg-[#48b8ae] transition-all sm:w-3.5"
                          style={{ height: `${Math.max(2, (point.clicks / maxValue) * 100)}%` }}
                          title={`${point.clicks} klik`}
                        />
                      </div>
                      <span className="mt-2 truncate text-center text-[9px] font-semibold text-[#89a096] sm:text-[10px]">
                        {point.label}
                      </span>
                    </div>
                  ))}
                </div>
              </section>

              <section className="rounded-[24px] border border-[#dce5dc] bg-white p-6 shadow-[0_8px_24px_rgba(18,50,39,0.04)]">
                <h3 className="text-lg font-extrabold">Perangkat Pengunjung</h3>
                <p className="mt-1 text-xs text-[#789087]">Distribusi berdasarkan user agent</p>
                <div className="mt-6 space-y-5">
                  {summary.devices.length === 0 ? (
                    <p className="text-sm text-[#789087]">Belum ada data perangkat.</p>
                  ) : (
                    summary.devices.map((device) => (
                      <div key={device.name}>
                        <div className="mb-2 flex items-center justify-between text-sm">
                          <span className="font-bold text-[#214c3e]">{device.name}</span>
                          <span className="text-[#789087]">{device.percentage}% · {device.count}</span>
                        </div>
                        <div className="h-2.5 overflow-hidden rounded-full bg-[#edf2ed]">
                          <div
                            className="h-full rounded-full bg-[#0b8a62]"
                            style={{ width: `${device.percentage}%` }}
                          />
                        </div>
                      </div>
                    ))
                  )}
                </div>
                <div className="mt-8 border-t border-[#edf2ed] pt-5">
                  <div className="mb-3 flex items-center gap-2 text-sm font-bold">
                    <Globe size={16} className="text-[#4aa9a4]" /> Browser teratas
                  </div>
                  <div className="flex flex-wrap gap-2">
                    {summary.browsers.length === 0 ? (
                      <span className="text-xs text-[#789087]">Belum ada data</span>
                    ) : (
                      summary.browsers.map((browser) => (
                        <span key={browser.name} className="rounded-full bg-[#f0f6f2] px-3 py-1.5 text-xs font-semibold text-[#315f4f]">
                          {browser.name} · {browser.percentage}%
                        </span>
                      ))
                    )}
                  </div>
                </div>
              </section>
            </div>

            <div className="grid gap-6 xl:grid-cols-[1.15fr_1fr]">
              <section className="overflow-hidden rounded-[24px] border border-[#dce5dc] bg-white shadow-[0_8px_24px_rgba(18,50,39,0.04)]">
                <div className="flex items-center justify-between border-b border-[#edf2ed] px-6 py-5">
                  <div>
                    <h3 className="text-lg font-extrabold">Performa Tautan</h3>
                    <p className="mt-1 text-xs text-[#789087]">Tautan paling banyak diklik</p>
                  </div>
                  <BarChart3 size={19} className="text-[#4aa9a4]" />
                </div>
                <div className="space-y-4 p-6">
                  {sortedLinkStats.length === 0 ? (
                    <p className="text-sm text-[#789087]">Belum ada tautan.</p>
                  ) : (
                    sortedLinkStats.map((link) => (
                      <div key={link.id}>
                        <div className="mb-2 flex items-center gap-2">
                          <PlatformIcon name={link.icon as LinkIcon} size={20} />
                          <span className="text-sm font-bold">{link.title}</span>
                          <span className="ml-auto text-xs font-bold text-[#315f4f]">{link.clicks} klik</span>
                        </div>
                        <div className="h-2 overflow-hidden rounded-full bg-[#edf2ed]">
                          <div className="h-full rounded-full bg-gradient-to-r from-[#0b5d92] to-[#10a67a]" style={{ width: `${Math.max(link.share, link.clicks ? 4 : 0)}%` }} />
                        </div>
                      </div>
                    ))
                  )}
                </div>
              </section>

              <section className="overflow-hidden rounded-[24px] border border-[#dce5dc] bg-white shadow-[0_8px_24px_rgba(18,50,39,0.04)]">
                <div className="flex items-center justify-between border-b border-[#edf2ed] px-6 py-5">
                  <div>
                    <h3 className="text-lg font-extrabold">Aktivitas Terbaru</h3>
                    <p className="mt-1 text-xs text-[#789087]">Event pengunjung paling baru</p>
                  </div>
                  <Clock3 size={19} className="text-[#4aa9a4]" />
                </div>
                <div className="divide-y divide-[#f0f3ef]">
                  {summary.recent.slice(0, 6).length === 0 ? (
                    <p className="p-6 text-sm text-[#789087]">Belum ada aktivitas.</p>
                  ) : (
                    summary.recent.slice(0, 6).map((event) => (
                      <div key={event.id} className="flex items-center gap-3 px-6 py-3.5">
                        <span className="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-[#edf7f2] text-[#0b704f]">
                          {event.eventType === "view" ? <Eye size={15} /> : <MousePointerClick size={15} />}
                        </span>
                        <div className="min-w-0 flex-1">
                          <p className="truncate text-sm font-bold">{event.label}</p>
                          <p className="text-xs text-[#89a096]">{EVENT_LABELS[event.eventType] ?? event.eventType} · {event.device}</p>
                        </div>
                        <span className="whitespace-nowrap text-[11px] text-[#89a096]">{formatDateTime(event.createdAt)}</span>
                      </div>
                    ))
                  )}
                </div>
              </section>
            </div>
          </div>
        ) : tab === "links" ? (
          <div className="space-y-6">
            <section className="rounded-[24px] border border-[#dce5dc] bg-white p-6">
              <div className="mb-5 flex items-center gap-2">
                <Plus size={19} className="text-[#0b704f]" />
                <h3 className="text-lg font-extrabold">Tambah Tautan Baru</h3>
              </div>
              <form onSubmit={addLink} className="grid gap-3 lg:grid-cols-[1.1fr_1.7fr_0.8fr_auto]">
                <input
                  value={newTitle}
                  onChange={(event) => setNewTitle(event.target.value)}
                  placeholder="Nama tautan"
                  required
                  className="rounded-xl border border-[#dce5dc] bg-[#fbfcf9] px-3 py-2.5 text-sm outline-none focus:border-[#0b7c60]"
                />
                <input
                  value={newUrl}
                  onChange={(event) => setNewUrl(event.target.value)}
                  placeholder="https://contoh.com"
                  required
                  className="rounded-xl border border-[#dce5dc] bg-[#fbfcf9] px-3 py-2.5 text-sm outline-none focus:border-[#0b7c60]"
                />
                <select
                  value={newIcon}
                  onChange={(event) => setNewIcon(event.target.value as LinkIcon)}
                  className="rounded-xl border border-[#dce5dc] bg-[#fbfcf9] px-3 py-2.5 text-sm outline-none focus:border-[#0b7c60]"
                >
                  <option value="link">Ikon umum</option>
                  <option value="website">Website</option>
                  <option value="shopee">Shopee</option>
                  <option value="tokopedia">Tokopedia</option>
                  <option value="whatsapp">WhatsApp</option>
                </select>
                <button
                  type="submit"
                  disabled={busy}
                  className="rounded-xl bg-[#0b6149] px-5 py-2.5 text-sm font-bold text-white hover:bg-[#084d3a] disabled:opacity-50"
                >
                  Tambah
                </button>
              </form>
            </section>

            <section className="rounded-[24px] border border-[#dce5dc] bg-white p-6">
              <div className="mb-5 flex flex-wrap items-center justify-between gap-3">
                <div className="flex items-center gap-2">
                  <Settings2 size={19} className="text-[#0b704f]" />
                  <h3 className="text-lg font-extrabold">Tautan Publik</h3>
                  <span className="rounded-full bg-[#edf7f2] px-2.5 py-1 text-xs font-bold text-[#0b704f]">{adminLinks.length}</span>
                </div>
                <Link href="/" target="_blank" className="inline-flex items-center gap-1.5 text-xs font-bold text-[#417a6b] hover:text-[#0b6149]">
                  Lihat tampilan publik <ExternalLink size={13} />
                </Link>
              </div>
              <div className="space-y-3">
                {adminLinks.map((link, index) => (
                  <LinkEditor
                    key={link.id}
                    link={link}
                    index={index}
                    total={adminLinks.length}
                    onPatch={patchLink}
                    onDelete={deleteLink}
                    onMove={moveLink}
                    onNotice={setNotice}
                  />
                ))}
              </div>
            </section>
          </div>
        ) : (
          <section className="overflow-hidden rounded-[24px] border border-[#dce5dc] bg-white shadow-[0_8px_24px_rgba(18,50,39,0.04)]">
            <div className="flex items-center justify-between border-b border-[#edf2ed] px-6 py-5">
              <div>
                <h3 className="text-lg font-extrabold">Log Aktivitas Pengunjung</h3>
                <p className="mt-1 text-xs text-[#789087]">50 event terbaru dalam {summary.days} hari terakhir</p>
              </div>
              <Activity size={19} className="text-[#4aa9a4]" />
            </div>
            <ActivityTable summary={summary} />
          </section>
        )}

        <div className="mt-7 flex flex-wrap items-center justify-between gap-3 rounded-[20px] border border-[#dce5dc] bg-[#fbfcf8] px-5 py-4">
          <div className="flex items-center gap-3">
            <span className="grid h-10 w-10 place-items-center rounded-xl bg-[#e4f5ea] text-[#0b704f]">
              <Database size={18} />
            </span>
            <div>
              <p className="text-sm font-bold">Data analytics tersimpan di PostgreSQL</p>
              <p className="text-xs text-[#789087]">
                {summary && summary.demoCount > 0
                  ? `${numberFormat.format(summary.demoCount)} event data contoh aktif pada periode ini.`
                  : "Semua angka berasal dari kunjungan dan klik nyata."}
              </p>
            </div>
          </div>
          <button
            type="button"
            onClick={() => void loadDemo()}
            disabled={busy}
            className="inline-flex items-center gap-2 rounded-xl border border-[#b9d9c8] bg-white px-4 py-2.5 text-xs font-bold text-[#0b6149] hover:bg-[#edf7f2] disabled:opacity-50"
          >
            <Database size={15} /> {summary?.demoCount ? "Perbarui" : "Muat"} Data Contoh
          </button>
        </div>
      </main>
    </div>
  );
}
