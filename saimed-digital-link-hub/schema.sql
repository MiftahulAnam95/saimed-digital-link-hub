-- Jalankan sekali pada database PostgreSQL yang digunakan aplikasi.
CREATE TABLE IF NOT EXISTS links (
  id SERIAL PRIMARY KEY, key TEXT NOT NULL UNIQUE, title TEXT NOT NULL,
  url TEXT NOT NULL, icon TEXT NOT NULL DEFAULT 'link', sort_order INTEGER NOT NULL DEFAULT 0,
  is_active BOOLEAN NOT NULL DEFAULT TRUE, featured BOOLEAN NOT NULL DEFAULT FALSE,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(), updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE TABLE IF NOT EXISTS analytics_events (
  id SERIAL PRIMARY KEY, event_type TEXT NOT NULL,
  link_id INTEGER REFERENCES links(id) ON DELETE SET NULL, link_key TEXT, target_label TEXT,
  visitor_id TEXT NOT NULL, path TEXT NOT NULL DEFAULT '/', referrer TEXT, user_agent TEXT,
  device TEXT NOT NULL DEFAULT 'Desktop', browser TEXT NOT NULL DEFAULT 'Lainnya',
  os TEXT NOT NULL DEFAULT 'Lainnya', is_demo BOOLEAN NOT NULL DEFAULT FALSE,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS analytics_events_created_at_idx ON analytics_events(created_at DESC);
-- Tidak menimpa pengaturan tautan yang sudah ada.
INSERT INTO links (key, title, url, icon, sort_order, featured)
SELECT seed.key, seed.title, seed.url, seed.icon, seed.sort_order, seed.featured
FROM (VALUES
 ('shopee', 'Shopee', 'https://shopee.co.id/search?keyword=Sentra%20Alkes%20Indonesia', 'shopee', 1, false),
 ('tokopedia', 'Tokopedia', 'https://www.tokopedia.com/sentraalkes', 'tokopedia', 2, false),
 ('whatsapp', 'WhatsApp', 'https://wa.me/6281113310040?text=Halo%20SAIMED%2C%20saya%20ingin%20bertanya%20mengenai%20produk%20alat%20kesehatan.', 'whatsapp', 3, false),
 ('website-resmi', 'Website Resmi', '?page=official', 'website', 4, true)
) AS seed(key, title, url, icon, sort_order, featured)
WHERE NOT EXISTS (SELECT 1 FROM links)
ON CONFLICT (key) DO NOTHING;
