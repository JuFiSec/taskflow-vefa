<?php
session_start();
require_once '../src/config.php';
require_once 'includes/security.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

$db = getDB();
$id = sanitize_int($_GET['id'] ?? 0);
if (!$id) { header('Location: dossiers.php'); exit; }

$d = check_dossier_access($db, $id);
if (!$d) { header('Location: dossiers.php?error=Dossier introuvable'); exit; }

$stmt2 = $db->prepare("SELECT s.*, u.prenom, u.nom FROM suivi_dossiers s JOIN utilisateurs u ON s.utilisateur_id = u.id WHERE s.dossier_id = ? ORDER BY s.created_at DESC");
$stmt2->execute([$id]);
$suivis = $stmt2->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['commentaire'])) {
    csrf_verify();
    $commentaire = sanitize_string($_POST['commentaire'], 2000);
    if (!empty($commentaire)) {
        $ins = $db->prepare("INSERT INTO suivi_dossiers (dossier_id, utilisateur_id, commentaire) VALUES (?, ?, ?)");
        $ins->execute([$id, $_SESSION['user_id'], $commentaire]);
    }
    header("Location: voir_dossier.php?id=$id");
    exit;
}

$statut_labels = ['en_cours' => 'En cours', 'signe' => 'Signé', 'archive' => 'Archivé', 'suspendu' => 'Suspendu'];
$active_page = 'dossiers';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskFlow — <?= htmlspecialchars($d['reference']) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="layout.css">
    <style>
        .dossier-hero { background:var(--card); border:1px solid var(--border); border-radius:10px; padding:28px; margin-bottom:20px; display:flex; justify-content:space-between; align-items:flex-start; gap:20px; position:relative; overflow:hidden; }
        .dossier-hero::before { content:''; position:absolute; left:0;top:0;bottom:0; width:3px; background:linear-gradient(to bottom,var(--orange),transparent); }
        .dossier-hero::after { content:''; position:absolute; right:-30px;top:-30px; width:160px;height:160px; background:radial-gradient(circle,var(--orange-glow),transparent 70%); }
        .dossier-ref { font-family:monospace; font-size:0.8rem; color:var(--orange); font-weight:600; margin-bottom:8px; }
        .dossier-title { font-family:'Syne',sans-serif; font-size:1.4rem; font-weight:700; color:var(--text); letter-spacing:-0.02em; margin-bottom:12px; }
        .dossier-price { font-family:'Syne',sans-serif; font-size:2rem; font-weight:800; color:var(--green); letter-spacing:-0.03em; }
        .dossier-price-label { font-size:0.72rem; color:var(--muted); text-transform:uppercase; letter-spacing:0.06em; margin-bottom:8px; }
        .info-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:20px; }
        .info-card { background:var(--card); border:1px solid var(--border); border-radius:10px; }
        .info-card-header { padding:14px 18px; border-bottom:1px solid var(--border); font-size:0.72rem; font-weight:700; color:var(--orange); text-transform:uppercase; letter-spacing:0.07em; }
        .info-row { display:flex; justify-content:space-between; align-items:center; padding:10px 18px; border-bottom:1px solid var(--border); font-size:0.85rem; }
        .info-row:last-child { border-bottom:none; }
        .info-row .lbl { color:var(--muted); }
        .info-row .val { color:var(--text); font-weight:500; text-align:right; max-width:60%; }
        .comments-card { background:var(--card); border:1px solid var(--border); border-radius:10px; }
        .comment-form-area { padding:18px; border-bottom:1px solid var(--border); }
        .comment-form-area textarea { width:100%; padding:10px 14px; background:var(--dark); border:1px solid var(--border); border-radius:7px; color:var(--text); font-size:0.875rem; font-family:'DM Sans',sans-serif; resize:vertical; min-height:80px; outline:none; transition:border-color 0.2s; }
        .comment-form-area textarea:focus { border-color:var(--orange); box-shadow:0 0 0 3px var(--orange-glow); }
        .comment-form-area textarea::placeholder { color:var(--muted2); }
        .comment-item { padding:16px 18px; border-bottom:1px solid var(--border); }
        .comment-item:last-child { border-bottom:none; }
        .comment-meta { display:flex; align-items:center; gap:8px; margin-bottom:6px; }
        .comment-avatar { width:24px;height:24px; background:var(--orange-glow); border:1px solid var(--border-h); border-radius:6px; display:flex;align-items:center;justify-content:center; font-size:11px; color:var(--orange); flex-shrink:0; }
        .comment-author { font-size:0.82rem; font-weight:600; color:var(--text); }
        .comment-time { font-size:0.72rem; color:var(--muted); margin-left:auto; }
        .comment-text { font-size:0.85rem; color:var(--text2); line-height:1.5; padding-left:32px; }
        .no-comments { text-align:center; padding:32px; font-size:0.85rem; color:var(--muted); }
    </style>
</head>
<body>
<?php include 'includes/sidebar.php'; ?>
<div class="main">
    <div class="topbar">
        <div class="topbar-left">
            <h1><?= htmlspecialchars($d['reference']) ?></h1>
            <p>Fiche détaillée du dossier VEFA</p>
        </div>
        <div style="display:flex;gap:8px">
            <a href="modifier_dossier.php?id=<?= $d['id'] ?>" class="btn btn-warning">✏ Modifier</a>
            <?php if ($_SESSION['user_role'] === 'admin'): ?>
            <a href="supprimer_dossier.php?id=<?= $d['id'] ?>" class="btn btn-danger" onclick="return confirm('Confirmer la suppression de ce dossier ?')">✕ Supprimer</a>
            <?php endif; ?>
            <a href="dossiers.php" class="btn btn-ghost">← Retour</a>
        </div>
    </div>
    <div class="content">
        <div class="dossier-hero">
            <div>
                <div class="dossier-ref"><?= htmlspecialchars($d['reference']) ?></div>
                <div class="dossier-title"><?= htmlspecialchars($d['titre']) ?></div>
                <span class="badge badge-<?= $d['statut'] ?>"><?= $statut_labels[$d['statut']] ?? $d['statut'] ?></span>
                <?php if ($d['bien_description']): ?>
                    <p style="margin-top:12px;color:var(--muted);font-size:0.85rem;max-width:520px;line-height:1.6"><?= htmlspecialchars($d['bien_description']) ?></p>
                <?php endif; ?>
            </div>
            <div style="text-align:right;position:relative;z-index:1">
                <div class="dossier-price-label">Prix de vente</div>
                <div class="dossier-price"><?= number_format($d['prix_vente'], 0, ',', ' ') ?> €</div>
            </div>
        </div>
        <div class="info-grid">
            <div class="info-card">
                <div class="info-card-header">👥 Parties impliquées</div>
                <div class="info-row"><span class="lbl">Promoteur</span><span class="val"><?= htmlspecialchars($d['promoteur']) ?></span></div>
                <div class="info-row"><span class="lbl">Notaire</span><span class="val"><?= htmlspecialchars($d['notaire']) ?></span></div>
                <div class="info-row"><span class="lbl">Réservataire</span><span class="val"><?= htmlspecialchars($d['reservataire']) ?></span></div>
            </div>
            <div class="info-card">
                <div class="info-card-header">📅 Chronologie</div>
                <div class="info-row"><span class="lbl">Création</span><span class="val"><?= date('d/m/Y', strtotime($d['created_at'])) ?></span></div>
                <div class="info-row"><span class="lbl">Mise à jour</span><span class="val"><?= date('d/m/Y', strtotime($d['updated_at'])) ?></span></div>
                <div class="info-row"><span class="lbl">Signature prévue</span><span class="val"><?= $d['date_signature'] ? date('d/m/Y', strtotime($d['date_signature'])) : '—' ?></span></div>
            </div>
        </div>
        <div class="comments-card">
            <div class="card-header">
                <span class="card-title">💬 Journal de suivi</span>
                <span style="font-size:0.75rem;color:var(--muted)"><?= count($suivis) ?> note(s)</span>
            </div>
            <div class="comment-form-area">
                <form method="POST" style="display:flex;flex-direction:column;gap:10px">
                    <?= csrf_field() ?>
                    <textarea name="commentaire" placeholder="Ajouter une note de suivi, un commentaire ou une mise à jour..."></textarea>
                    <div><button type="submit" class="btn btn-primary btn-sm">+ Ajouter une note</button></div>
                </form>
            </div>
            <?php if (empty($suivis)): ?>
                <div class="no-comments">Aucune note pour ce dossier.</div>
            <?php else: ?>
                <?php foreach ($suivis as $s): ?>
                <div class="comment-item">
                    <div class="comment-meta">
                        <div class="comment-avatar">👤</div>
                        <span class="comment-author"><?= htmlspecialchars($s['prenom'] . ' ' . $s['nom']) ?></span>
                        <span class="comment-time"><?= date('d/m/Y à H:i', strtotime($s['created_at'])) ?></span>
                    </div>
                    <div class="comment-text"><?= nl2br(htmlspecialchars($s['commentaire'])) ?></div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>