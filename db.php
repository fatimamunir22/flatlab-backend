<?php
/**
 * Returns a shared PDO/SQLite connection.
 * Creates the database file and all tables on first run.
 */
date_default_timezone_set('Asia/Karachi');

function get_db(): PDO {
    static $db = null;
    if ($db !== null) return $db;

    $db_path = __DIR__ . '/../data/flatlab.db';
    $data_dir = dirname($db_path);
    if (!is_dir($data_dir)) {
        mkdir($data_dir, 0755, true);
    }

    $db = new PDO('sqlite:' . $db_path);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $db->exec("
        CREATE TABLE IF NOT EXISTS contact_messages (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            name        TEXT    NOT NULL,
            email       TEXT    NOT NULL,
            phone       TEXT,
            subject     TEXT,
            message     TEXT    NOT NULL,
            ip_address  TEXT,
            created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS newsletter_subscribers (
            id             INTEGER PRIMARY KEY AUTOINCREMENT,
            email          TEXT    UNIQUE NOT NULL,
            subscribed_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
            is_active      INTEGER DEFAULT 1
        )
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS blog_comments (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            post_id     TEXT    NOT NULL DEFAULT 'blog-details',
            name        TEXT    NOT NULL,
            email       TEXT    NOT NULL,
            message     TEXT    NOT NULL,
            reply_to    TEXT,
            ip_address  TEXT,
            is_approved INTEGER DEFAULT 1,
            created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Add reply_to to existing tables that were created before this column existed
    try { $db->exec("ALTER TABLE blog_comments ADD COLUMN reply_to TEXT"); } catch (\Exception $e) {}

    return $db;
}

/** Emit a JSON response and exit. */
function json_response(bool $success, string $message, array $data = []): void {
    header('Content-Type: application/json');
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit;
}

/** Return sanitised string input or null if blank. */
function input(string $key, bool $required = false): ?string {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    // Fall back to POST for form-encoded submissions
    $val = isset($body[$key]) ? $body[$key] : ($_POST[$key] ?? '');
    $val = trim(strip_tags((string) $val));
    if ($required && $val === '') return null;
    return $val === '' ? null : $val;
}

/** True if the hidden honeypot field was filled in - real users never see or fill it. */
function is_spam(): bool {
    return input('website') !== null;
}

/** True if this IP has submitted to $table more than $max times in the last $minutes. */
function rate_limited(string $table, ?string $ip, int $max = 5, int $minutes = 10): bool {
    if (!$ip) return false;
    $stmt = get_db()->prepare("
        SELECT COUNT(*) AS c FROM {$table}
        WHERE ip_address = :ip AND created_at >= datetime('now', :window)
    ");
    $stmt->execute([':ip' => $ip, ':window' => "-{$minutes} minutes"]);
    return (int) $stmt->fetch()['c'] >= $max;
}
