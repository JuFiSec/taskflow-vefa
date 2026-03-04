<?php
session_start();
require_once '../src/config.php';
require_once 'includes/security.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

// Seul l'admin peut supprimer
if ($_SESSION['user_role'] !== 'admin') {
    security_log('UNAUTHORIZED_DELETE', "User {$_SESSION['user_id']} a tenté de supprimer");
    header('Location: dossiers.php?error=Action non autorisée');
    exit;
}

$db = getDB();
$id = sanitize_int($_GET['id'] ?? 0);
if (!$id) { header('Location: dossiers.php'); exit; }

// Vérifier que le dossier existe avant de supprimer
$d = check_dossier_access($db, $id);
if (!$d) { header('Location: dossiers.php?error=Dossier introuvable'); exit; }

$stmt = $db->prepare("DELETE FROM dossiers WHERE id = ?");
$stmt->execute([$id]);

security_log('DOSSIER_DELETE', "ID: $id (Réf: {$d['reference']}) par user {$_SESSION['user_id']}");
header('Location: dossiers.php?success=' . urlencode('Dossier supprimé avec succès'));
exit;