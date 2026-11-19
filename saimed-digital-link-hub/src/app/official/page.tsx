import { ArrowLeft, ArrowUpRight, ShieldCheck } from "lucide-react";
import Link from "next/link";
import { PlatformIcon, SaimedAvatar } from "@/components/BrandVisuals";
import {
  DEFAULT_LINKS,
  INSTAGRAM_URL,
  WHATSAPP_URL,
  type LinkIcon,
} from "@/lib/constants";

export const metadata = {
  title: "Website Resmi SAIMED | Sentra Alkes Indonesia",
  description: "Profil resmi dan kanal digital SAIMED – Sentra Alkes Indonesia.",
};

export default function OfficialPage() {
  const marketplaceLinks = DEFAULT_LINKS.filter((link) => !link.featured);

  return (
    <main className="min-h-screen bg-[#e9e9dd] px-5 py-10 text-[#102a22]">
      <div className="mx-auto flex w-full max-w-[620px] flex-col items-center text-center">
        <Link
          href="/"
          className="mb-10 inline-flex items-center gap-2 self-start text-sm font-bold text-[#417a6b] hover:text-[#0b6149]"
        >
          <ArrowLeft size={16} /> Kembali ke Link Hub
        </Link>

        <SaimedAvatar />
        <p className="mt-8 text-[11px] font-extrabold uppercase tracking-[0.24em] text-[#4eb6ae]">
          Sentra Alkes Indonesia
        </p>
        <h1 className="mt-3 text-3xl font-extrabold tracking-tight sm:text-4xl">
          Website Resmi SAIMED
        </h1>
        <p className="mt-5 max-w-[520px] text-[15px] leading-relaxed text-[#31594b]">
          SAIMED hadir sebagai mitra terpercaya dalam memenuhi kebutuhan alat
          kesehatan dengan mengutamakan kualitas, keandalan, dan pelayanan
          terbaik untuk rumah sakit, klinik, laboratorium, serta kebutuhan
          perawatan di rumah.
        </p>

        <div className="mt-7 inline-flex items-center gap-2 rounded-full border border-[#b9ddcd] bg-white px-4 py-2 text-xs font-bold text-[#0b704f]">
          <ShieldCheck size={15} /> Kanal digital resmi SAIMED
        </div>

        <section className="mt-10 w-full rounded-[28px] border-[3px] border-[#10231e] bg-white p-6 text-left shadow-[7px_7px_0_#10231e] sm:p-8">
          <h2 className="text-lg font-extrabold">Temukan kami di</h2>
          <p className="mt-1 text-xs text-[#6d857b]">Pilih kanal resmi untuk katalog dan konsultasi.</p>
          <div className="mt-6 space-y-3">
            {marketplaceLinks.map((link) => (
              <a
                key={link.key}
                href={link.url}
                target="_blank"
                rel="noopener noreferrer"
                className="flex items-center gap-3 rounded-2xl border border-[#dce5dc] bg-[#fbfcf9] px-4 py-3 transition hover:border-[#0b7c60] hover:bg-[#f1f8f4]"
              >
                <span className="grid h-10 w-10 place-items-center rounded-xl bg-[#e8f7f0]">
                  <PlatformIcon name={link.icon as LinkIcon} size={24} />
                </span>
                <span className="font-bold">{link.title}</span>
                <ArrowUpRight size={17} className="ml-auto text-[#417a6b]" />
              </a>
            ))}
            <a
              href={INSTAGRAM_URL}
              target="_blank"
              rel="noopener noreferrer"
              className="flex items-center gap-3 rounded-2xl border border-[#dce5dc] bg-[#fbfcf9] px-4 py-3 transition hover:border-[#0b7c60] hover:bg-[#f1f8f4]"
            >
              <span className="grid h-10 w-10 place-items-center rounded-xl bg-[#e8f7f0]">
                <PlatformIcon name="instagram" size={24} />
              </span>
              <span className="font-bold">Instagram</span>
              <ArrowUpRight size={17} className="ml-auto text-[#417a6b]" />
            </a>
            <a
              href={WHATSAPP_URL}
              target="_blank"
              rel="noopener noreferrer"
              className="flex items-center gap-3 rounded-2xl border border-[#dce5dc] bg-[#fbfcf9] px-4 py-3 transition hover:border-[#0b7c60] hover:bg-[#f1f8f4]"
            >
              <span className="grid h-10 w-10 place-items-center rounded-xl bg-[#e8f7f0]">
                <PlatformIcon name="whatsapp" size={24} />
              </span>
              <span className="font-bold">Konsultasi WhatsApp</span>
              <ArrowUpRight size={17} className="ml-auto text-[#417a6b]" />
            </a>
          </div>
        </section>

        <p className="mt-9 text-xs font-semibold text-[#79a49a]">© SAIMED 2026 · All Rights Reserved</p>
      </div>
    </main>
  );
}
