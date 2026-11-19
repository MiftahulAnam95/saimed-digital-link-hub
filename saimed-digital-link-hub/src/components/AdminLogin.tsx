"use client";

import { ArrowLeft, LockKeyhole, ShieldCheck } from "lucide-react";
import Link from "next/link";
import { useState, type FormEvent } from "react";
import { SaimedAvatar } from "@/components/BrandVisuals";

export default function AdminLogin() {
  const [password, setPassword] = useState("");
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setLoading(true);
    setError("");
    try {
      const response = await fetch("/api/admin/login", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ password }),
      });
      if (!response.ok) {
        setError("Kata sandi administrator tidak sesuai.");
        return;
      }
      window.location.reload();
    } catch {
      setError("Tidak dapat terhubung ke server.");
    } finally {
      setLoading(false);
    }
  }

  return (
    <main className="flex min-h-screen items-center justify-center bg-[#e9e9dd] px-5 py-12 text-[#102a22]">
      <div className="w-full max-w-[430px] rounded-[30px] border-[3px] border-[#102a22] bg-white p-8 shadow-[8px_8px_0_#102a22] sm:p-10">
        <div className="mb-8 flex items-center gap-3">
          <SaimedAvatar compact />
          <div>
            <p className="text-xs font-bold uppercase tracking-[0.2em] text-[#4aa9a4]">
              SAIMED Digital Link Hub
            </p>
            <h1 className="mt-1 text-2xl font-extrabold tracking-tight">Admin Analytics</h1>
          </div>
        </div>

        <div className="mb-6 inline-flex items-center gap-2 rounded-full bg-[#eaf7f1] px-3 py-1.5 text-xs font-semibold text-[#0b6149]">
          <ShieldCheck size={15} /> Area khusus administrator
        </div>

        <form onSubmit={handleSubmit} className="space-y-5">
          <label className="block">
            <span className="mb-2 block text-sm font-bold">Kata sandi</span>
            <div className="flex items-center gap-2 rounded-2xl border-2 border-[#d9e3dc] bg-[#fbfcf9] px-4 focus-within:border-[#0b7c60]">
              <LockKeyhole size={18} className="text-[#6c8b80]" />
              <input
                type="password"
                value={password}
                onChange={(event) => setPassword(event.target.value)}
                placeholder="Masukkan kata sandi admin"
                autoFocus
                className="w-full bg-transparent py-3.5 text-sm outline-none"
              />
            </div>
          </label>

          {error && (
            <p className="rounded-xl bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
              {error}
            </p>
          )}

          <button
            type="submit"
            disabled={loading || !password}
            className="w-full rounded-2xl bg-[#0b6149] py-3.5 text-sm font-bold text-white transition hover:bg-[#084d3a] disabled:cursor-not-allowed disabled:opacity-50"
          >
            {loading ? "Memverifikasi..." : "Masuk ke Dashboard"}
          </button>
        </form>

        <Link
          href="/"
          className="mt-7 inline-flex items-center gap-2 text-sm font-semibold text-[#417a6b] hover:text-[#0b6149]"
        >
          <ArrowLeft size={16} /> Kembali ke halaman publik
        </Link>
      </div>
    </main>
  );
}
