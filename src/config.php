<?php
// ================================================
// TaskFlow - Configuration base de données
// ================================================

define('DB_HOST', 'mysql');
define('DB_NAME', 'taskflow');
define('DB_USER', 'taskflow_user');
define('DB_PASS', 'taskflow_pass');
define('DB_CHARSET', 'utf8mb4');

// Connexion PDO
function getDB(): PDO {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die(json_encode(['error' => 'Connexion échouée : ' . $e->getMessage()]));
        }
    }
    
    return $pdo;
}