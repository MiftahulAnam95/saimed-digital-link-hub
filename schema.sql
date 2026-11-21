-- Pilih database MySQL/MariaDB terlebih dahulu, misalnya: USE test_saimed_link;
-- Import file ini lewat phpMyAdmin atau mysql CLI. Data yang sudah ada tidak dihapus.
CREATE TABLE IF NOT EXISTS links (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(120) COLLATE utf8mb4_bin NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL,
    url TEXT NOT NULL,
    icon VARCHAR(30) NOT NULL DEFAULT 'link',
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    featured TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS analytics_events (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(40) NOT NULL,
    link_id INT NULL,
    link_key VARCHAR(120) COLLATE utf8mb4_bin NULL,
    target_label VARCHAR(180) NULL,
    visitor_id VARCHAR(120) NOT NULL,
    path VARCHAR(240) NOT NULL DEFAULT '/',
    referrer TEXT NULL,
    user_agent TEXT NULL,
    device VARCHAR(30) NOT NULL DEFAULT 'Desktop',
    browser VARCHAR(30) NOT NULL DEFAULT 'Lainnya',
    os VARCHAR(30) NOT NULL DEFAULT 'Lainnya',
    -- Disimpan untuk kompatibilitas dan menyaring data contoh dari versi lama.
    is_demo TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX analytics_events_created_at_idx (created_at),
    CONSTRAINT analytics_events_link_id_fk FOREIGN KEY (link_id)
        REFERENCES links(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tambahkan tautan bawaan hanya jika tabel masih kosong.
INSERT INTO links (`key`, title, url, icon, sort_order, featured)
SELECT defaults_data.link_key, defaults_data.title, defaults_data.url,
       defaults_data.icon, defaults_data.sort_order, defaults_data.featured
FROM (
    SELECT 'shopee' AS link_key, 'Shopee' AS title,
        'https://shopee.co.id/search?keyword=Sentra%20Alkes%20Indonesia' AS url,
        'shopee' AS icon, 1 AS sort_order, 0 AS featured
    UNION ALL SELECT 'tokopedia', 'Tokopedia', 'https://www.tokopedia.com/sentraalkes', 'tokopedia', 2, 0
    UNION ALL SELECT 'whatsapp', 'WhatsApp', 'https://wa.me/6281113310040?text=Halo%20SAIMED%2C%20saya%20ingin%20bertanya%20mengenai%20produk%20alat%20kesehatan.', 'whatsapp', 3, 0
    UNION ALL SELECT 'website-resmi', 'Website Resmi', '?page=official', 'website', 4, 1
) AS defaults_data
CROSS JOIN (SELECT COUNT(*) AS total FROM links) AS existing
WHERE existing.total = 0;
