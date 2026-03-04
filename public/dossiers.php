<?php
session_start();
require_once '../src/config.php';
require_once 'includes/security.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

$db = getDB();

$statut_filter = $_GET['statut'] ?? '';
$search        = trim($_GET['q'] ?? '');

$counts = [];
foreach (['', 'en_cours', 'signe', 'archive', 'suspendu'] as $s) {
    $q = $s ? "SELECT COUNT(*) FROM dossiers WHERE statut=?" : "SELECT COUNT(*) FROM dossiers";
    $st = $db->prepare($q);
    $s ? $st->execute([$s]) : $st->execute([]);
    $counts[$s] = $st->fetchColumn();
}

$where = []; $params = [];
if ($statut_filter) { $where[] = "statut = ?"; $params[] = $statut_filter; }
if ($search) { $where[] = "(titre LIKE ? OR reference LIKE ? OR promoteur LIKE ? OR notaire LIKE ?)"; $params = array_merge($params, array_fill(0, 4, "%$search%")); }

$sql = "SELECT * FROM dossiers" . ($where ? " WHERE " . implode(" AND ", $where) : "") . " ORDER BY created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$dossiers = $stmt->fetchAll();

$success = $_GET['success'] ?? '';
$error   = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskFlow — Dossiers VEFA</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="layout.css">
</head>
<body>
<aside class="sidebar">
    <div class="sb-brand">
        <div class="sb-icon">🦊</div>
        <div class="sb-brand-text">
            <div class="sb-name">TaskFlow</div>
            <div class="sb-sub">Gestion VEFA</div>
        </div>
    </div>
    <div class="sb-section">
        <div class="sb-section-label">Navigation</div>
        <a href="dashboard.php" class="nav-item"><span class="nav-icon">◈</span> Tableau de bord</a>
        <a href="dossiers.php" class="nav-item active"><span class="nav-icon">⊞</span> Dossiers VEFA</a>
        <a href="nouveau_dossier.php" class="nav-item"><span class="nav-icon">＋</span> Nouveau dossier</a>
        <div style="height:16px"></div>
        <div class="sb-section-label">Monitoring</div>
        <a href="http://localhost:9090" target="_blank" class="nav-item"><span class="nav-icon">◉</span> Prometheus</a>
        <a href="http://localhost:3000" target="_blank" class="nav-item"><span class="nav-icon">▣</span> Grafana</a>
        <a href="http://localhost:9082" target="_blank" class="nav-item"><span class="nav-icon">⊗</span> Adminer</a>
    </div>
    <div class="sb-footer">
        <div class="sb-user">
            <div class="sb-avatar">👤</div>
            <div>
                <div class="sb-user-name"><?= htmlspecialchars($_SESSION['user_nom']) ?></div>
                <div class="sb-user-role"><?= htmlspecialchars($_SESSION['user_role']) ?></div>
            </div>
        </div>
        <a href="logout.php" class="btn-logout">← Déconnexion</a>
    </div>
</aside>

<div class="main">
    <div class="topbar">
        <div class="topbar-left">
            <h1>Dossiers VEFA</h1>
            <p>Gestion des ventes en état futur d'achèvement</p>
        </div>
        <a href="nouveau_dossier.php" class="btn btn-primary">+ Nouveau dossier</a>
    </div>

    <div class="content">

        <?php if ($success): ?>
            <div class="alert alert-success">✓ <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error">⚠ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <!-- Filters -->
                <div class="filter-bar" style="margin-bottom:0">
                    <?php
                    $filters = [
                        '' => 'Tous', 'en_cours' => 'En cours',
                        'signe' => 'Signés', 'archive' => 'Archivés', 'suspendu' => 'Suspendus'
                    ];
                    foreach ($filters as $val => $label):
                    ?>
                    <a href="?statut=<?= $val ?><?= $search ? '&q='.urlencode($search) : '' ?>"
                       class="filter-btn <?= $statut_filter === $val ? 'active' : '' ?>">
                        <?= $label ?>
                        <span class="filter-count"><?= $counts[$val] ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>

                <!-- Search -->
                <form method="GET" style="display:flex;gap:8px">
                    <?php if ($statut_filter): ?><input type="hidden" name="statut" value="<?= htmlspecialchars($statut_filter) ?>"><?php endif; ?>
                    <div class="search-wrap">
                        <span class="search-icon">🔍</span>
                        <input class="search-input" type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Référence, titre, promoteur...">
                    </div>
                    <button type="submit" class="btn btn-ghost">Chercher</button>
                </form>
            </div>

            <?php if (empty($dossiers)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📂</div>
                    <p>Aucun dossier trouvé<?= $search ? " pour « $search »" : '' ?>.</p>
                </div>
            <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Référence</th>
                            <th>Titre du bien</th>
                            <th>Promoteur</th>
                            <th>Notaire</th>
                            <th>Prix</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($dossiers as $d): ?>
                        <tr>
                            <td><a href="voir_dossier.php?id=<?= $d['id'] ?>" class="td-ref"><?= htmlspecialchars($d['reference']) ?></a></td>
                            <td class="td-title" style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($d['titre']) ?></td>
                            <td style="color:var(--text2)"><?= htmlspecialchars($d['promoteur']) ?></td>
                            <td style="color:var(--muted);font-size:0.82rem"><?= htmlspecialchars($d['notaire']) ?></td>
                            <td class="td-price"><?= number_format($d['prix_vente'], 0, ',', ' ') ?> €</td>
                            <td><span class="badge badge-<?= $d['statut'] ?>"><?= ucfirst(str_replace('_', ' ', $d['statut'])) ?></span></td>
                            <td>
                                <div style="display:flex;gap:6px">
                                    <a href="voir_dossier.php?id=<?= $d['id'] ?>" class="btn btn-ghost btn-sm">Voir</a>
                                    <a href="modifier_dossier.php?id=<?= $d['id'] ?>" class="btn btn-warning btn-sm">Modifier</a>
                                    <?php if ($_SESSION['user_role'] === 'admin'): ?>
                                    <a href="supprimer_dossier.php?id=<?= $d['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Supprimer ce dossier ?')">✕</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div style="padding:12px 20px;border-top:1px solid var(--border);font-size:0.78rem;color:var(--muted)">
                <?= count($dossiers) ?> dossier(s) affiché(s)
            </div>
            <?php endif; ?>
        </div>

    </div>
</div>

</body>
</html>