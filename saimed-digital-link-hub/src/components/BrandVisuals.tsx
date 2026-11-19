/* eslint-disable @next/next/no-img-element */
import type { LinkIcon } from "@/lib/constants";

type IconName = LinkIcon | "instagram";

export function SaimedAvatar({ compact = false }: { compact?: boolean }) {
  return (
    <div className={compact ? "brand-avatar brand-avatar--compact" : "brand-avatar"}>
      <img
        src="/images/saimed-mark.png"
        alt="Logo geometris SAIMED"
        className="brand-avatar__mark"
      />
      {!compact && (
        <span className="brand-avatar__word">
          SAIMED<sup>+</sup>
        </span>
      )}
    </div>
  );
}

export function PlatformIcon({
  name,
  size = 30,
}: {
  name: IconName;
  size?: number;
}) {
  const common = {
    width: size,
    height: size,
    viewBox: "0 0 32 32",
    "aria-hidden": true as const,
    focusable: "false" as const,
  };

  if (name === "instagram") {
    return (
      <svg {...common}>
        <defs>
          <radialGradient id="saimed-ig-gradient" cx="0.28" cy="1" r="1.15">
            <stop offset="0" stopColor="#FFDD55" />
            <stop offset="0.28" stopColor="#FF543E" />
            <stop offset="0.58" stopColor="#E5216B" />
            <stop offset="1" stopColor="#7A3CDF" />
          </radialGradient>
        </defs>
        <rect x="1" y="1" width="30" height="30" rx="9" fill="url(#saimed-ig-gradient)" />
        <rect x="7.2" y="7.2" width="17.6" height="17.6" rx="5.8" fill="none" stroke="#fff" strokeWidth="2.2" />
        <circle cx="16" cy="16" r="4.25" fill="none" stroke="#fff" strokeWidth="2.2" />
        <circle cx="22.6" cy="9.4" r="1.35" fill="#fff" />
      </svg>
    );
  }

  if (name === "whatsapp") {
    return (
      <svg {...common}>
        <rect x="1" y="1" width="30" height="30" rx="9" fill="#25D366" />
        <g transform="translate(4.6 4.6) scale(0.95)">
          <path
            fill="#fff"
            d="M17.47 14.38c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.94 1.17-.17.2-.35.22-.64.07-.3-.15-1.26-.46-2.39-1.47-.88-.79-1.48-1.76-1.65-2.06-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.03-.52-.07-.15-.67-1.61-.92-2.2-.24-.58-.48-.5-.66-.51-.18-.01-.37-.01-.57-.01-.2 0-.52.07-.8.37-.27.3-1.03 1.02-1.03 2.48 0 1.46 1.06 2.87 1.21 3.07.15.2 2.1 3.2 5.08 4.49.71.3 1.26.49 1.69.62.71.23 1.36.2 1.87.12.57-.09 1.76-.72 2.01-1.41.25-.7.25-1.29.17-1.41-.07-.13-.27-.2-.57-.35m-5.42 7.4h-.01a9.87 9.87 0 0 1-5.03-1.37l-.36-.22-3.74.98 1-3.65-.24-.37a9.86 9.86 0 0 1-1.51-5.26c0-5.45 4.44-9.89 9.89-9.89 2.64 0 5.12 1.03 6.99 2.9a9.83 9.83 0 0 1 2.89 6.99c0 5.45-4.44 9.89-9.89 9.89m8.41-18.3A11.82 11.82 0 0 0 12.05 0C5.5 0 .16 5.34.16 11.89c0 2.1.55 4.14 1.59 5.95L.06 24l6.3-1.65a11.88 11.88 0 0 0 5.69 1.45h.01c6.55 0 11.89-5.34 11.89-11.89a11.82 11.82 0 0 0-3.48-8.41"
          />
        </g>
      </svg>
    );
  }

  if (name === "shopee") {
    return (
      <svg {...common}>
        <path
          d="M7 9.5h18l-1.3 16.1a3.2 3.2 0 0 1-3.2 2.9H11.5a3.2 3.2 0 0 1-3.2-2.9L7 9.5Z"
          fill="#EE4D2D"
        />
        <path
          d="M12 10V7.8a4 4 0 0 1 8 0V10"
          fill="none"
          stroke="#EE4D2D"
          strokeWidth="2.2"
          strokeLinecap="round"
        />
        <text
          x="16"
          y="22.7"
          textAnchor="middle"
          fontFamily="Arial, sans-serif"
          fontSize="11.5"
          fontWeight="800"
          fill="#fff"
        >
          S
        </text>
      </svg>
    );
  }

  if (name === "tokopedia") {
    return (
      <svg {...common}>
        <rect x="2" y="2" width="28" height="28" rx="9" fill="#3FAE4A" />
        <path d="M7 12.5c2.2-2.7 5-4.1 9-4.1s6.8 1.4 9 4.1v9.2c0 1-.8 1.8-1.8 1.8H8.8A1.8 1.8 0 0 1 7 21.7v-9.2Z" fill="#fff" opacity=".96" />
        <circle cx="12.1" cy="15.2" r="3" fill="#3FAE4A" />
        <circle cx="19.9" cy="15.2" r="3" fill="#3FAE4A" />
        <circle cx="12.1" cy="15.2" r="1.2" fill="#fff" />
        <circle cx="19.9" cy="15.2" r="1.2" fill="#fff" />
        <path d="M14.4 19.7h3.2l-1.6 2.2-1.6-2.2Z" fill="#EE7B2B" />
        <path d="M11 8.5c1.1-1.7 2.8-2.5 5-2.5s3.9.8 5 2.5" fill="none" stroke="#fff" strokeWidth="1.7" strokeLinecap="round" />
      </svg>
    );
  }

  if (name === "website") {
    return (
      <svg {...common}>
        <rect x="1" y="1" width="30" height="30" rx="9" fill="#0B62A5" />
        <circle cx="16" cy="16" r="9.2" fill="none" stroke="#fff" strokeWidth="2" />
        <path d="M6.9 16h18.2M16 6.8c2.6 2.5 3.9 5.6 3.9 9.2s-1.3 6.7-3.9 9.2c-2.6-2.5-3.9-5.6-3.9-9.2s1.3-6.7 3.9-9.2Z" fill="none" stroke="#fff" strokeWidth="1.7" />
      </svg>
    );
  }

  return (
    <svg {...common}>
      <rect x="1" y="1" width="30" height="30" rx="9" fill="#0C6B52" />
      <path d="M13 11.5a4 4 0 0 1 5.6 0l2.2 2.2M19 20.5a4 4 0 0 1-5.6 0l-2.2-2.2M18.8 13.2l-5.6 5.6" fill="none" stroke="#fff" strokeWidth="2.2" strokeLinecap="round" />
    </svg>
  );
}
