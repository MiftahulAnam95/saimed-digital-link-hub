export type LinkIcon = "website" | "shopee" | "tokopedia" | "whatsapp" | "link";

export type PublicLink = {
  id: number;
  key: string;
  title: string;
  url: string;
  icon: LinkIcon;
  sortOrder: number;
  featured: boolean;
  isActive: boolean;
};

export const WHATSAPP_URL =
  "https://wa.me/6281113310040?text=Halo%20SAIMED%2C%20saya%20ingin%20bertanya%20mengenai%20produk%20alat%20kesehatan.";

export const INSTAGRAM_URL = "https://www.instagram.com/sentraalkesindonesia/";

// Urutan visual mengikuti screenshot: tiga kartu terang, lalu website sebagai kartu unggulan.
export const DEFAULT_LINKS: Omit<PublicLink, "id">[] = [
  {
    key: "shopee",
    title: "Shopee",
    url: "https://shopee.co.id/search?keyword=Sentra%20Alkes%20Indonesia",
    icon: "shopee",
    sortOrder: 1,
    featured: false,
    isActive: true,
  },
  {
    key: "tokopedia",
    title: "Tokopedia",
    url: "https://www.tokopedia.com/sentraalkes",
    icon: "tokopedia",
    sortOrder: 2,
    featured: false,
    isActive: true,
  },
  {
    key: "whatsapp",
    title: "WhatsApp",
    url: WHATSAPP_URL,
    icon: "whatsapp",
    sortOrder: 3,
    featured: false,
    isActive: true,
  },
  {
    key: "website-resmi",
    title: "Website Resmi SAIMED",
    url: "/official",
    icon: "website",
    sortOrder: 4,
    featured: true,
    isActive: true,
  },
];

export function safeExternalUrl(value: string): string | null {
  const trimmed = value.trim();
  if (trimmed.startsWith("/") && !trimmed.startsWith("//")) return trimmed;
  try {
    const parsed = new URL(trimmed);
    if (parsed.protocol !== "http:" && parsed.protocol !== "https:") return null;
    return parsed.toString();
  } catch {
    return null;
  }
}

export function displayHost(value: string): string {
  if (value.startsWith("/")) return `saimed.link${value}`;
  try {
    return new URL(value).hostname.replace(/^www\./, "");
  } catch {
    return value;
  }
}
