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
 ['id'=>-4,'key'=>'website-resmi','title'=>'Website Resmi','url'=>'?page=official','icon'=>'website','featured'=>true,'is_active'=>true,'sort_order'=>4],
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
function avatar(bool $compact = false): void {
    echo '<div class="brand-avatar'.($compact ? ' brand-avatar--compact' : '').'"><img class="brand-logo" src="assets/saimed-logo.png" alt="Logo SAIMED"></div>';
}
function icon(string $name): string {
    $icons = [
        'instagram' => '<defs><linearGradient id="instagram-gradient" x1="0" y1="1" x2="1" y2="0"><stop stop-color="#ffdc54"/><stop offset=".4" stop-color="#ff2e55"/><stop offset="1" stop-color="#b000d9"/></linearGradient></defs><rect x="2" y="2" width="20" height="20" rx="5" fill="url(#instagram-gradient)"/><rect x="6" y="6" width="12" height="12" rx="4" fill="none" stroke="white" stroke-width="1.6"/><circle cx="12" cy="12" r="3" fill="none" stroke="white" stroke-width="1.6"/><circle cx="17" cy="7" r="1" fill="white"/>',
        'whatsapp' => '<rect x="2" y="2" width="20" height="20" rx="1" fill="#25d366"/><path d="M7.2 18l.7-2.6A6.1 6.1 0 1 1 10 17.3L7.2 18Z" fill="none" stroke="white" stroke-width="1.25"/><path d="M9.2 8.7c.3-.3.6-.3.8 0l.8 1.3c.1.2 0 .4-.3.7-.2.2-.2.3 0 .6.5.8 1.2 1.5 2.1 1.9.3.1.4.1.6-.1l.6-.6c.2-.2.4-.2.6-.1l1.3.7c.3.2.3.5.1.8-.4.7-1.1 1-1.8.8-2.4-.6-4.4-2.5-5.1-4.8-.2-.5 0-1 .3-1.2Z" fill="white"/>',
        'shopee' => '<path d="M4.2 7.5h15.6l-1.1 12a2 2 0 0 1-2 1.8H7.3a2 2 0 0 1-2-1.8L4.2 7.5Z" fill="#ee4d2d"/><path d="M8.2 8V6.8a3.8 3.8 0 0 1 7.6 0V8" fill="none" stroke="#ee4d2d" stroke-width="1.7"/><text x="12" y="18" text-anchor="middle" font-family="Arial,sans-serif" font-size="10" font-weight="bold" fill="white">S</text>',
        'tokopedia' => '<path d="M4 8.5 6.2 5l3.3 1.9a10 10 0 0 1 5 0L17.8 5 20 8.5v9.6a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2Z" fill="#58b84f" stroke="#28683b" stroke-width=".8"/><path d="M5.5 10c1.4-2 3.5-2.8 6.5-2.8s5.1.8 6.5 2.8v6.4a1.5 1.5 0 0 1-1.5 1.5H7a1.5 1.5 0 0 1-1.5-1.5Z" fill="white"/><circle cx="9" cy="12.3" r="2.3" fill="#429e4b"/><circle cx="15" cy="12.3" r="2.3" fill="#429e4b"/><circle cx="9" cy="12.3" r="1" fill="#173c2b"/><circle cx="15" cy="12.3" r="1" fill="#173c2b"/><path d="m10.6 15.3 1.4 1.6 1.4-1.6Z" fill="#ef8b2c"/>',
        'website' => '<circle cx="11" cy="11" r="8" fill="none" stroke="#0092cc" stroke-width="1.7"/><path d="M3 11h16M11 3c2.1 2.2 3.1 4.9 3.1 8S13.1 16.8 11 19c-2.1-2.2-3.1-4.9-3.1-8S8.9 5.2 11 3Z" fill="none" stroke="#0092cc" stroke-width="1.4"/><path d="m14 14 1.8 6 1.2-2.4 2.7-1Z" fill="#1374a9" stroke="white" stroke-width=".5"/>',
        'link' => '<path d="M10 14a4 4 0 0 0 5.7 0l2-2a4 4 0 0 0-5.7-5.7l-1.1 1.1M14 10a4 4 0 0 0-5.7 0l-2 2a4 4 0 0 0 5.7 5.7l1.1-1.1" fill="none" stroke="#0c6b52" stroke-width="2" stroke-linecap="round"/>',
    ];
    return '<span class="platform-icon icon-'.h($name).'" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false">'.($icons[$name] ?? $icons['link']).'</svg></span>';
}
function page_start(string $title): void { ?><!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="theme-color" content="#ffffff"><meta name="description" content="Tautan resmi SAIMED – Sentra Alkes Indonesia."><title><?= h($title) ?></title><link rel="stylesheet" href="assets/style.css"></head><body><?php }
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
