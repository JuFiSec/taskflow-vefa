<?php
session_start();
require_once '../src/config.php';
require_once 'includes/security.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

$db = getDB();
$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $reference    = sanitize_string($_POST['reference'] ?? '', 50);
    $titre        = sanitize_string($_POST['titre'] ?? '', 200);
    $promoteur    = sanitize_string($_POST['promoteur'] ?? '', 150);
    $notaire      = sanitize_string($_POST['notaire'] ?? '', 150);
    $reservataire = sanitize_string($_POST['reservataire'] ?? '', 150);
    $description  = sanitize_string($_POST['bien_description'] ?? '', 1000);
    $prix         = sanitize_float($_POST['prix_vente'] ?? 0);
    $statut       = in_array($_POST['statut'] ?? '', ['en_cours','signe','suspendu','archive']) ? $_POST['statut'] : 'en_cours';
    $date_sig     = $_POST['date_signature'] ?? null;

    if (empty($reference) || empty($titre) || empty($promoteur) || empty($notaire) || empty($reservataire)) {
        $erreur = 'Veuillez remplir tous les champs obligatoires.';
    } else {
        try {
            $stmt = $db->prepare("INSERT INTO dossiers (reference, titre, promoteur, notaire, reservataire, bien_description, prix_vente, statut, date_signature, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$reference, $titre, $promoteur, $notaire, $reservataire, $description, $prix, $statut, $date_sig ?: null, $_SESSION['user_id']]);
            security_log('DOSSIER_CREATE', "Réf: $reference par user {$_SESSION['user_id']}");
            header('Location: dossiers.php?success=' . urlencode('Dossier créé avec succès'));
            exit;
        } catch (PDOException $e) {
            $erreur = strpos($e->getMessage(), 'Duplicate') !== false ? 'Cette référence existe déjà.' : 'Erreur lors de la création.';
        }
    }
}

$nb = $db->query("SELECT COUNT(*) FROM dossiers")->fetchColumn() + 1;
$ref_auto = 'VEFA-' . date('Y') . '-' . str_pad($nb, 3, '0', STR_PAD_LEFT);
$active_page = 'nouveau';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskFlow — Nouveau dossier</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="layout.css">
    <style>
        .form-card { background:var(--card); border:1px solid var(--border); border-radius:10px; max-width:760px; overflow:hidden; }
        .form-card-header { padding:20px 24px; border-bottom:1px solid var(--border); display:flex; align-items:center; gap:12px; }
        .form-card-header h2 { font-family:'Syne',sans-serif; font-size:1rem; font-weight:700; color:var(--text); }
        .form-card-body { padding:28px 24px; }
        .form-actions { display:flex; gap:10px; margin-top:28px; padding-top:20px; border-top:1px solid var(--border); }
        .ref-pill { font-family:monospace; font-size:0.75rem; background:var(--orange-glow); border:1px solid var(--border-h); color:var(--orange); padding:3px 10px; border-radius:20px; margin-left:auto; }
    </style>
</head>
<body>
<?php include 'includes/sidebar.php'; ?>
<div class="main">
    <div class="topbar">
        <div class="topbar-left">
            <h1>Nouveau dossier VEFA</h1>
            <p>Enregistrement d'une nouvelle vente en état futur d'achèvement</p>
        </div>
        <a href="dossiers.php" class="btn btn-ghost">← Retour</a>
    </div>
    <div class="content">
        <?php if ($erreur): ?>
            <div class="alert alert-error">⚠ <?= htmlspecialchars($erreur) ?></div>
        <?php endif; ?>
        <div class="form-card">
            <div class="form-card-header">
                <span style="font-size:1.2rem">📁</span>
                <h2>Informations du dossier</h2>
                <span class="ref-pill">Réf. suggérée : <?= $ref_auto ?></span>
            </div>
            <div class="form-card-body">
                <form method="POST">
                    <?= csrf_field() ?>
                    <div class="form-grid">
                        <div class="form-section-label">Identification</div>
                        <div class="form-group">
                            <label class="form-label">Référence <span class="form-required">*</span></label>
                            <input class="form-input" type="text" name="reference" value="<?= htmlspecialchars($_POST['reference'] ?? $ref_auto) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Statut</label>
                            <select class="form-select" name="statut">
                                <option value="en_cours">En cours</option>
                                <option value="signe">Signé</option>
                                <option value="suspendu">Suspendu</option>
                                <option value="archive">Archivé</option>
                            </select>
                        </div>
                        <div class="form-group full">
                            <label class="form-label">Titre du bien <span class="form-required">*</span></label>
                            <input class="form-input" type="text" name="titre" placeholder="Ex : Les Terrasses du Lac – Apt 4C" value="<?= htmlspecialchars($_POST['titre'] ?? '') ?>" required>
                        </div>
                        <div class="form-group full">
                            <label class="form-label">Description</label>
                            <textarea class="form-textarea" name="bien_description" placeholder="Appartement T3, 72m², 4ème étage..."><?= htmlspecialchars($_POST['bien_description'] ?? '') ?></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Prix de vente (€)</label>
                            <input class="form-input" type="number" name="prix_vente" step="1000" min="0" value="<?= htmlspecialchars($_POST['prix_vente'] ?? '') ?>" placeholder="285 000">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Date de signature prévue</label>
                            <input class="form-input" type="date" name="date_signature" value="<?= htmlspecialchars($_POST['date_signature'] ?? '') ?>">
                        </div>
                        <div class="form-section-label">Parties impliquées</div>
                        <div class="form-group">
                            <label class="form-label">Promoteur <span class="form-required">*</span></label>
                            <input class="form-input" type="text" name="promoteur" placeholder="Ex : Nexity Grand Paris" value="<?= htmlspecialchars($_POST['promoteur'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Notaire <span class="form-required">*</span></label>
                            <input class="form-input" type="text" name="notaire" placeholder="Ex : Me Dupont Marie" value="<?= htmlspecialchars($_POST['notaire'] ?? '') ?>" required>
                        </div>
                        <div class="form-group full">
                            <label class="form-label">Réservataire <span class="form-required">*</span></label>
                            <input class="form-input" type="text" name="reservataire" placeholder="Ex : Jean-Luc Bernard" value="<?= htmlspecialchars($_POST['reservataire'] ?? '') ?>" required>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">✓ Créer le dossier</button>
                        <a href="dossiers.php" class="btn btn-ghost">Annuler</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>