-- Pilih database Link Hub yang sudah ada sebelum menjalankan migrasi ini.
-- Kolom nullable agar event lama tetap tersimpan tanpa mengarang alamat IP.
-- Aman dijalankan ulang: hanya menambah kolom jika belum ada.
SET @saimed_ip_migration = IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'analytics_events' AND COLUMN_NAME = 'ip_address'),
    'SELECT ''Kolom ip_address sudah tersedia'' AS hasil',
    'ALTER TABLE analytics_events ADD COLUMN ip_address VARCHAR(45) NULL AFTER visitor_id'
);
PREPARE saimed_ip_statement FROM @saimed_ip_migration;
EXECUTE saimed_ip_statement;
DEALLOCATE PREPARE saimed_ip_statement;
SET @saimed_ip_migration = NULL;
