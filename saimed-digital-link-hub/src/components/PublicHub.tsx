"use client";

import { ArrowUpRight, Copy, MoreVertical, Share2 } from "lucide-react";
import Link from "next/link";
import { useCallback, useEffect, useRef, useState } from "react";
import { PlatformIcon, SaimedAvatar } from "@/components/BrandVisuals";
import {
  INSTAGRAM_URL,
  WHATSAPP_URL,
  type LinkIcon,
  type PublicLink,
} from "@/lib/constants";

type EventType = "view" | "link_click" | "social_click" | "link_action";

const VISITOR_KEY = "saimed_visitor_id";
const VIEW_KEY = "saimed_last_view";

function getVisitorId(): string {
  try {
    const existing = window.localStorage.getItem(VISITOR_KEY);
    if (existing) return existing;
    const id =
      typeof crypto !== "undefined" && "randomUUID" in crypto
        ? crypto.randomUUID()
        : `visitor-${Date.now()}-${Math.random().toString(36).slice(2)}`;
    window.localStorage.setItem(VISITOR_KEY, id);
    return id;
  } catch {
    return "anonymous";
  }
}

export default function PublicHub({ links }: { links: PublicLink[] }) {
  const [openMenu, setOpenMenu] = useState<string | null>(null);
  const [toast, setToast] = useState("");
  const linksRef = useRef<HTMLDivElement>(null);
  const toastTimer = useRef<number | null>(null);

  const showToast = useCallback((message: string) => {
    setToast(message);
    if (toastTimer.current) window.clearTimeout(toastTimer.current);
    toastTimer.current = window.setTimeout(() => setToast(""), 2400);
  }, []);

  const sendEvent = useCallback(
    (eventType: EventType, extra: Record<string, string> = {}) => {
      const payload = {
        eventType,
        visitorId: getVisitorId(),
        path: window.location.pathname,
        referrer: document.referrer || null,
        ...extra,
      };
      const body = JSON.stringify(payload);
      const beaconUsed =
        typeof navigator.sendBeacon === "function" &&
        navigator.sendBeacon(
          "/api/track",
          new Blob([body], { type: "application/json" }),
        );

      if (!beaconUsed) {
        void fetch("/api/track", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body,
          keepalive: true,
        }).catch(() => undefined);
      }
    },
    [],
  );

  useEffect(() => {
    const now = Date.now();
    let lastView = 0;
    try {
      lastView = Number(window.sessionStorage.getItem(VIEW_KEY) ?? 0);
    } catch {
      lastView = 0;
    }
    if (!lastView || now - lastView > 30 * 60 * 1000) {
      sendEvent("view");
      try {
        window.sessionStorage.setItem(VIEW_KEY, String(now));
      } catch {
        // Tracking tetap berjalan meski penyimpanan browser dibatasi.
      }
    }

    const onPointerDown = (event: PointerEvent) => {
      if (!linksRef.current?.contains(event.target as Node)) setOpenMenu(null);
    };
    document.addEventListener("pointerdown", onPointerDown);
    return () => document.removeEventListener("pointerdown", onPointerDown);
  }, [sendEvent]);

  const trackLink = (link: PublicLink) => {
    sendEvent("link_click", { linkKey: link.key, targetLabel: link.title });
    setOpenMenu(null);
  };

  const openExternal = (link: PublicLink) => {
    trackLink(link);
    window.open(link.url, "_blank", "noopener,noreferrer");
  };

  const copyLink = async (link: PublicLink) => {
    try {
      await navigator.clipboard.writeText(link.url);
      showToast("Tautan berhasil disalin");
      sendEvent("link_action", {
        linkKey: link.key,
        targetLabel: `Salin: ${link.title}`,
      });
    } catch {
      showToast("Tidak dapat menyalin tautan");
    }
    setOpenMenu(null);
  };

  const shareLink = async (link: PublicLink) => {
    try {
      if (navigator.share) {
        await navigator.share({ title: link.title, url: link.url });
        showToast("Tautan siap dibagikan");
      } else {
        await navigator.clipboard.writeText(link.url);
        showToast("Tautan disalin untuk dibagikan");
      }
      sendEvent("link_action", {
        linkKey: link.key,
        targetLabel: `Bagikan: ${link.title}`,
      });
    } catch {
      showToast("Berbagi dibatalkan");
    }
    setOpenMenu(null);
  };

  return (
    <main className="public-shell">
      <div className="public-content">
        <header className="profile">
          <div className="avatar-shell">
            <SaimedAvatar />
          </div>

          <h1 className="profile-username">sentraalkesindonesia</h1>
          <p className="profile-eyebrow">SENTRA ALKES INDONESIA</p>
          <p className="profile-description">
            SAIMED – Sentra Alkes Indonesia, hadir sebagai mitra terpercaya dalam
            memenuhi kebutuhan alat kesehatan dengan mengutamakan kualitas,
            keandalan, dan pelayanan terbaik.
          </p>

          <div className="social-row" aria-label="Media sosial SAIMED">
            <a
              className="social-button"
              href={INSTAGRAM_URL}
              target="_blank"
              rel="noopener noreferrer"
              aria-label="Buka Instagram sentraalkesindonesia"
              onClick={() =>
                sendEvent("social_click", {
                  linkKey: "instagram",
                  targetLabel: "Instagram",
                })
              }
            >
              <PlatformIcon name="instagram" size={27} />
            </a>
            <a
              className="social-button"
              href={WHATSAPP_URL}
              target="_blank"
              rel="noopener noreferrer"
              aria-label="Buka WhatsApp SAIMED"
              onClick={() =>
                sendEvent("social_click", {
                  linkKey: "whatsapp-social",
                  targetLabel: "WhatsApp Profil",
                })
              }
            >
              <PlatformIcon name="whatsapp" size={27} />
            </a>
          </div>
        </header>

        <section className="links" ref={linksRef} aria-label="Tautan resmi SAIMED">
          {links.map((link) => (
            <article
              key={link.key}
              className={`link-card${link.featured ? " link-card--featured" : ""}${
                openMenu === link.key ? " link-card--menu-open" : ""
              }`}
            >
              <a
                className="card-link"
                href={link.url}
                target="_blank"
                rel="noopener noreferrer"
                aria-label={`Buka ${link.title} di tab baru`}
                onClick={() => trackLink(link)}
              >
                <span className="card-icon">
                  <PlatformIcon name={link.icon as LinkIcon} size={30} />
                </span>
                <span className="card-label">{link.title}</span>
                <ArrowUpRight className="card-arrow" size={20} strokeWidth={2.1} />
              </a>

              <button
                type="button"
                className="card-menu"
                aria-label={`Opsi untuk ${link.title}`}
                aria-expanded={openMenu === link.key}
                onClick={(event) => {
                  event.stopPropagation();
                  setOpenMenu(openMenu === link.key ? null : link.key);
                }}
              >
                <MoreVertical size={21} strokeWidth={2.5} />
              </button>

              {openMenu === link.key && (
                <div className="card-dropdown" role="menu">
                  <button type="button" role="menuitem" onClick={() => openExternal(link)}>
                    <ArrowUpRight size={16} /> Buka di tab baru
                  </button>
                  <button type="button" role="menuitem" onClick={() => void copyLink(link)}>
                    <Copy size={15} /> Salin tautan
                  </button>
                  <button type="button" role="menuitem" onClick={() => void shareLink(link)}>
                    <Share2 size={15} /> Bagikan tautan
                  </button>
                </div>
              )}
            </article>
          ))}
        </section>
      </div>

      <footer className="public-footer">
        <span>© SAIMED 2026</span>
        <Link href="/admin" className="footer-admin" aria-label="Halaman admin analytics">
          Analytics
        </Link>
        <span>All Rights Reserved</span>
      </footer>

      <div className={`public-toast${toast ? " public-toast--visible" : ""}`} role="status">
        {toast}
      </div>
    </main>
  );
}
