<?php
declare(strict_types=1);
date_default_timezone_set('UTC');
const WHATSAPP = 'https://wa.me/6281113310040?text=Halo%20SAIMED%2C%20saya%20ingin%20bertanya%20mengenai%20produk%20alat%20kesehatan.';
const INSTAGRAM = 'https://www.instagram.com/sentraalkesindonesia/';
const ICONS = ['link', 'website', 'shopee', 'tokopedia', 'whatsapp'];
function h($value): string { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function db(): PDO {
    static $db = null;
    if ($db instanceof PDO) return $db;
    $url = getenv('DATABASE_URL');
    if (!$url) throw new RuntimeException('DATABASE_URL belum dikonfigurasi');
    $parts = parse_url($url);
    if (!$parts || !in_array($parts['scheme'] ?? '', ['postgres', 'postgresql'], true) || empty($parts['host']) || empty($parts['path'])) throw new RuntimeException('DATABASE_URL tidak valid');
    parse_str($parts['query'] ?? '', $query);
    $dsn = 'pgsql:host='.$parts['host'].';port='.($parts['port'] ?? 5432).';dbname='.ltrim($parts['path'], '/');
    if (isset($query['sslmode']) && in_array($query['sslmode'], ['disable','allow','prefer','require','verify-ca','verify-full'], true)) $dsn .= ';sslmode='.$query['sslmode'];
    $db = new PDO($dsn, rawurldecode($parts['user'] ?? ''), rawurldecode($parts['pass'] ?? ''), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]);
    return $db;
}
function fallback_links(): array { return [
 ['id'=>-1,'key'=>'shopee','title'=>'Shopee','url'=>'https://shopee.co.id/search?keyword=Sentra%20Alkes%20Indonesia','icon'=>'shopee','featured'=>false,'is_active'=>true,'sort_order'=>1],
 ['id'=>-2,'key'=>'tokopedia','title'=>'Tokopedia','url'=>'https://www.tokopedia.com/sentraalkes','icon'=>'tokopedia','featured'=>false,'is_active'=>true,'sort_order'=>2],
 ['id'=>-3,'key'=>'whatsapp','title'=>'WhatsApp','url'=>WHATSAPP,'icon'=>'whatsapp','featured'=>false,'is_active'=>true,'sort_order'=>3],
 ['id'=>-4,'key'=>'website-resmi','title'=>'Website Resmi SAIMED','url'=>'?page=official','icon'=>'website','featured'=>true,'is_active'=>true,'sort_order'=>4],
]; }
function boolval_db($value): bool { return $value === true || $value === 1 || $value === '1' || $value === 't'; }
function links(bool $active = false): array {
    try { return db()->query('SELECT * FROM links '.($active ? 'WHERE is_active = true ' : '').'ORDER BY sort_order, id')->fetchAll(); }
    catch (Throwable $e) { error_log('SAIMED database: '.$e->getMessage()); return fallback_links(); }
}
function safe_url(string $url): ?string {
    $url = trim($url);
    if (preg_match('/[\x00-\x20\x7f\\\\]/', $url)) return null;
    if ((str_starts_with($url, '/') && !str_starts_with($url, '//')) || preg_match('/^\?page=official$/', $url)) return $url;
    if (filter_var($url, FILTER_VALIDATE_URL) && in_array(strtolower((string)parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true)) return $url;
    return null;
}
function csrf(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); }
function check_csrf(): void {
    if (!isset($_POST['csrf'], $_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], (string)$_POST['csrf'])) { http_response_code(403); exit('Permintaan tidak valid.'); }
}
function redirect_admin(string $tab = 'overview'): void { header('Location: ?page=admin&tab='.rawurlencode($tab), true, 303); exit; }
function avatar(bool $compact = false): void { echo '<div class="brand-avatar'.($compact ? ' brand-avatar--compact' : '').'"><div class="brand-symbol" aria-hidden="true">✚</div>'.($compact ? '' : '<span class="brand-avatar__word">SAIMED<sup>+</sup></span>').'</div>'; }
function icon(string $name): string { $symbols = ['shopee'=>'S','tokopedia'=>'T','whatsapp'=>'☎','instagram'=>'◎','website'=>'◎','link'=>'↗']; return '<span class="platform-icon icon-'.h($name).'" aria-hidden="true">'.h($symbols[$name] ?? '↗').'</span>'; }
function page_start(string $title): void { ?><!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="theme-color" content="#e9e9dd"><meta name="description" content="Tautan resmi SAIMED – Sentra Alkes Indonesia."><title><?= h($title) ?></title><link rel="stylesheet" href="assets/style.css"></head><body><?php }
function page_end(): void { echo '</body></html>'; }
function json_response(array $data, int $status = 200): void { http_response_code($status); header('Content-Type: application/json; charset=utf-8'); echo json_encode($data); exit; }
function parse_agent(string $ua): array {
    $device = preg_match('/ipad|tablet/i', $ua) ? 'Tablet' : (preg_match('/iphone|android|mobile|windows phone/i', $ua) ? 'Mobile' : 'Desktop');
    $browser = 'Lainnya'; foreach (['Edge'=>'/edg\//i','Opera'=>'/opr\/|opera/i','Firefox'=>'/firefox\//i','Chrome'=>'/chrome\/|crios\//i','Safari'=>'/safari\//i'] as $name=>$pattern) if (preg_match($pattern, $ua)) { $browser=$name; break; }
    $os = 'Lainnya'; foreach (['Android'=>'/android/i','iOS'=>'/iphone|ipad|ipod/i','Windows'=>'/windows/i','macOS'=>'/mac os|macintosh/i','Linux'=>'/linux/i'] as $name=>$pattern) if (preg_match($pattern, $ua)) { $os=$name; break; }
    return [$device,$browser,$os];
}
function clean($value, int $max): ?string { return is_string($value) && trim($value) !== '' ? substr(trim($value), 0, $max) : null; }
function track(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['error'=>'Metode tidak diizinkan'], 405);
    $body = json_decode(file_get_contents('php://input'), true);
    if (!is_array($body) || !in_array($body['eventType'] ?? null, ['view','link_click','social_click','link_action'], true)) json_response(['ok'=>false,'error'=>'Jenis event tidak valid'], 400);
    $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 700); [$device,$browser,$os] = parse_agent($ua);
    $key = clean($body['linkKey'] ?? null, 120);
    try {
        $query = db()->prepare('INSERT INTO analytics_events (event_type, link_id, link_key, target_label, visitor_id, path, referrer, user_agent, device, browser, os) VALUES (:event, (SELECT id FROM links WHERE key = :lookup), :key, :label, :visitor, :path, :referrer, :ua, :device, :browser, :os)');
        $query->execute(['event'=>$body['eventType'],'lookup'=>$key,'key'=>$key,'label'=>clean($body['targetLabel'] ?? null, 180),'visitor'=>clean($body['visitorId'] ?? null, 120) ?? 'anon-'.substr(hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '').$ua.gmdate('Y-m-d')), 0, 24),'path'=>clean($body['path'] ?? null, 240) ?? '/', 'referrer'=>clean($body['referrer'] ?? null, 500),'ua'=>$ua,'device'=>$device,'browser'=>$browser,'os'=>$os]);
        json_response(['ok'=>true,'tracked'=>true]);
    } catch (Throwable $e) { error_log('SAIMED tracking: '.$e->getMessage()); json_response(['ok'=>true,'tracked'=>false], 202); }
}
