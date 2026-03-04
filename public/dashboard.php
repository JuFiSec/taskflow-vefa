<?php
session_start();
require_once '../src/config.php';
require_once 'includes/security.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

$db = getDB();
$total     = $db->query("SELECT COUNT(*) FROM dossiers")->fetchColumn();
$en_cours  = $db->query("SELECT COUNT(*) FROM dossiers WHERE statut='en_cours'")->fetchColumn();
$signes    = $db->query("SELECT COUNT(*) FROM dossiers WHERE statut='signe'")->fetchColumn();
$valeur    = $db->query("SELECT SUM(prix_vente) FROM dossiers")->fetchColumn();
$recents   = $db->query("SELECT * FROM dossiers ORDER BY created_at DESC LIMIT 5")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskFlow — Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="layout.css">
    <style>
        .welcome-bar {
            background: linear-gradient(135deg, var(--card) 0%, #1A1410 100%);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 24px 28px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }
        .welcome-bar::before {
            content: '';
            position: absolute;
            left: 0; top: 0; bottom: 0;
            width: 3px;
            background: linear-gradient(to bottom, var(--orange), transparent);
            border-radius: 10px 0 0 10px;
        }
        .welcome-bar::after {
            content: '';
            position: absolute;
            right: -40px; top: -40px;
            width: 180px; height: 180px;
            background: radial-gradient(circle, var(--orange-glow), transparent 70%);
        }
        .welcome-text h2 { font-family:'Syne',sans-serif; font-size:1.2rem; font-weight:700; color:var(--text); letter-spacing:-0.02em; margin-bottom:4px; }
        .welcome-text p  { font-size:0.85rem; color:var(--muted); }
        .grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:20px; }

        /* Quick links */
        .quick-link { display:flex; align-items:center; gap:12px; padding:12px; background:var(--card2); border:1px solid var(--border); border-radius:8px; text-decoration:none; transition:all 0.15s; margin-bottom:8px; }
        .quick-link:hover { border-color:var(--border-h); background:var(--orange-glow); }
        .quick-link:last-child { margin-bottom:0; }
        .quick-link-icon { width:32px; height:32px; border-radius:7px; display:flex; align-items:center; justify-content:center; font-size:15px; flex-shrink:0; }
        .quick-link-text { font-size:0.85rem; font-weight:500; color:var(--text2); }
        .quick-link:hover .quick-link-text { color:var(--orange); }
        .quick-link-arr { margin-left:auto; color:var(--muted2); font-size:12px; }
    </style>
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
        <a href="dashboard.php" class="nav-item active">
            <span class="nav-icon">◈</span> Tableau de bord
        </a>
        <a href="dossiers.php" class="nav-item">
            <span class="nav-icon">⊞</span> Dossiers VEFA
            <?php if ($en_cours > 0): ?>
                <span class="nav-badge"><?= $en_cours ?></span>
            <?php endif; ?>
        </a>
        <a href="nouveau_dossier.php" class="nav-item">
            <span class="nav-icon">＋</span> Nouveau dossier
        </a>
        <div style="height:16px"></div>
        <div class="sb-section-label">Monitoring</div>
        <a href="http://localhost:9090" target="_blank" class="nav-item">
            <span class="nav-icon">◉</span> Prometheus
        </a>
        <a href="http://localhost:3000" target="_blank" class="nav-item">
            <span class="nav-icon">▣</span> Grafana
        </a>
        <a href="http://localhost:9082" target="_blank" class="nav-item">
            <span class="nav-icon">⊗</span> Adminer
        </a>
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
            <h1>Tableau de bord</h1>
            <p>Vue d'ensemble de l'activité</p>
        </div>
        <div class="topbar-right">
            <div class="topbar-badge">
                <span class="dot-pulse"></span>
                Tous les services opérationnels
            </div>
        </div>
    </div>

    <div class="content">

        <div class="welcome-bar">
            <div class="welcome-text">
                <h2>Bonjour, <?= htmlspecialchars(explode(' ', $_SESSION['user_nom'])[0]) ?> 👋</h2>
                <p><?= date('l d F Y') ?> · Espace de gestion VEFA</p>
            </div>
            <a href="nouveau_dossier.php" class="btn btn-primary">+ Nouveau dossier</a>
        </div>

        <!-- Stats -->
        <div class="stat-grid">
            <div class="stat-card orange">
                <div class="stat-label">Total dossiers</div>
                <div class="stat-val"><?= $total ?></div>
                <div class="stat-sub">Tous statuts confondus</div>
            </div>
            <div class="stat-card blue">
                <div class="stat-label">En cours</div>
                <div class="stat-val"><?= $en_cours ?></div>
                <div class="stat-sub">Dossiers actifs</div>
            </div>
            <div class="stat-card green">
                <div class="stat-label">Signés</div>
                <div class="stat-val"><?= $signes ?></div>
                <div class="stat-sub">Actes finalisés</div>
            </div>
            <div class="stat-card amber">
                <div class="stat-label">Valeur portefeuille</div>
                <div class="stat-val" style="font-size:1.3rem"><?= number_format($valeur, 0, ',', ' ') ?> €</div>
                <div class="stat-sub">Montant total VEFA</div>
            </div>
        </div>

        <div class="grid-2">
            <!-- Dossiers récents -->
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Dossiers récents</span>
                    <a href="dossiers.php" class="btn btn-ghost btn-sm">Voir tout →</a>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Référence</th>
                                <th>Bien</th>
                                <th>Prix</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($recents as $d): ?>
                            <tr>
                                <td><a href="voir_dossier.php?id=<?= $d['id'] ?>" class="td-ref"><?= htmlspecialchars($d['reference']) ?></a></td>
                                <td class="td-title" style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($d['titre']) ?></td>
                                <td class="td-price" style="font-size:0.82rem"><?= number_format($d['prix_vente'], 0, ',', ' ') ?> €</td>
                                <td><span class="badge badge-<?= $d['statut'] ?>"><?= ucfirst(str_replace('_', ' ', $d['statut'])) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Right column -->
            <div style="display:flex; flex-direction:column; gap:20px;">

                <!-- Quick actions -->
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">Accès rapides</span>
                    </div>
                    <div class="card-body" style="padding:12px">
                        <a href="nouveau_dossier.php" class="quick-link">
                            <div class="quick-link-icon" style="background:var(--orange-glow)">📁</div>
                            <span class="quick-link-text">Créer un dossier VEFA</span>
                            <span class="quick-link-arr">→</span>
                        </a>
                        <a href="dossiers.php?statut=en_cours" class="quick-link">
                            <div class="quick-link-icon" style="background:var(--blue-bg)">⏳</div>
                            <span class="quick-link-text">Dossiers en cours</span>
                            <span class="quick-link-arr">→</span>
                        </a>
                        <a href="http://localhost:3000" target="_blank" class="quick-link">
                            <div class="quick-link-icon" style="background:var(--green-bg)">📊</div>
                            <span class="quick-link-text">Ouvrir Grafana</span>
                            <span class="quick-link-arr">↗</span>
                        </a>
                        <a href="http://localhost:9090" target="_blank" class="quick-link">
                            <div class="quick-link-icon" style="background:var(--amber-bg)">◉</div>
                            <span class="quick-link-text">Prometheus metrics</span>
                            <span class="quick-link-arr">↗</span>
                        </a>
                    </div>
                </div>

                <!-- Services status -->
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">État des services</span>
                    </div>
                    <div class="card-body" style="padding:12px 20px">
                        <?php
                        $services = [
                            ['TaskFlow App',  '9080', 'Fait'],
                            ['MySQL 8.0',     '3307', 'Fait'],
                            ['Prometheus',    '9090', 'Fait'],
                            ['Grafana',       '3000', 'Fait'],
                            ['Adminer',       '9082', 'Fait'],
                            ['MySQL Exporter','9104', 'Fait'],
                        ];
                        ?>
                        <?php foreach ($services as $s): ?>
                        <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border)">
                            <div style="display:flex;align-items:center;gap:8px">
                                <span style="font-size:10px;color:var(--green)">●</span>
                                <span style="font-size:0.82rem;color:var(--text2)"><?= $s[0] ?></span>
                            </div>
                            <span style="font-family:monospace;font-size:0.72rem;color:var(--muted)">:<?= $s[1] ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div>
        </div>

    </div>
</div>

</body>
</html>