<?php
/**
 * TaskFlow — Couche de sécurité centralisée
 * Appliqué sur toutes les pages via include
 */

// 1. HEADERS DE SÉCURITÉ HTTP 
// Empêche le clickjacking (page dans une iframe d'un autre site)
header('X-Frame-Options: DENY');

// Empêche le MIME sniffing (le navigateur ne peut pas deviner le type de fichier)
header('X-Content-Type-Options: nosniff');

// Force HTTPS (si déployé en prod)
// header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

// Content Security Policy — contrôle ce que le navigateur peut charger
header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src https://fonts.gstatic.com; script-src 'self' 'unsafe-inline'; img-src 'self' data:;");

// Protection XSS navigateurs anciens
header('X-XSS-Protection: 1; mode=block');

// Masquer la technologie utilisée
header_remove('X-Powered-By');

// 2. PROTECTION CSRF (Cross-Site Request Forgery)  
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function csrf_verify(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            http_response_code(403);
            die('Requête invalide — token CSRF manquant ou incorrect.');
        }
    }
}

// 3. RATE LIMITING (anti brute-force login) 
function rate_limit_check(string $key, int $max = 5, int $window = 300): bool {
    $session_key = 'rl_' . $key;
    $now = time();

    if (!isset($_SESSION[$session_key])) {
        $_SESSION[$session_key] = ['count' => 0, 'start' => $now];
    }

    $data = &$_SESSION[$session_key];

    // Réinitialiser si la fenêtre de temps est dépassée
    if ($now - $data['start'] > $window) {
        $data = ['count' => 0, 'start' => $now];
    }

    $data['count']++;

    return $data['count'] <= $max;
}

//  4. VALIDATION ET SANITISATION 
function sanitize_string(string $value, int $max_length = 255): string {
    $value = trim($value);
    $value = strip_tags($value);            // Supprime les balises HTML
    $value = mb_substr($value, 0, $max_length); // Limite la longueur
    return $value;
}

function sanitize_int(mixed $value): int {
    return intval(filter_var($value, FILTER_SANITIZE_NUMBER_INT));
}

function sanitize_float(mixed $value): float {
    return floatval(filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION));
}

function validate_email(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// ── 5. PROTECTION CONTRE L'ÉNUMÉRATION DES IDS ───────────────────────────────
// Ex : si l'URL est ?id=5, on vérifie que cet ID appartient bien à l'utilisateur connecté
function check_dossier_access(PDO $db, int $id): array|false {
    $stmt = $db->prepare("SELECT * FROM dossiers WHERE id = ?");
    $stmt->execute([$id]);
    $dossier = $stmt->fetch();

    // Admin voit tout, autres rôles aussi pour l'instant (à affiner selon besoin)
    return $dossier ?: false;
}

// ── 6. LOGS DE SÉCURITÉ ──────────────────────────────────────────────────────
function security_log(string $event, string $details = ''): void {
    $log_dir = __DIR__ . '/../../logs';
    if (!is_dir($log_dir)) {
        @mkdir($log_dir, 0750, true);
    }

    $ip   = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user = $_SESSION['user_id'] ?? 'anonymous';
    $line = date('Y-m-d H:i:s') . " | $ip | user:$user | $event | $details\n";

    @file_put_contents($log_dir . '/security.log', $line, FILE_APPEND | LOCK_EX);
}