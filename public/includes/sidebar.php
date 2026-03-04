<?php
// Inclure ce fichier dans chaque page après session_start()
// Usage: include 'includes/sidebar.php'; avec $active_page défini
$active = $active_page ?? '';
$db_counts = getDB()->query("SELECT statut, COUNT(*) as nb FROM dossiers GROUP BY statut")->fetchAll(PDO::FETCH_KEY_PAIR);
$en_cours_nb = $db_counts['en_cours'] ?? 0;
?>
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
        <a href="dashboard.php" class="nav-item <?= $active==='dashboard'?'active':'' ?>">
            <span class="nav-icon">◈</span> Tableau de bord
        </a>
        <a href="dossiers.php" class="nav-item <?= $active==='dossiers'?'active':'' ?>">
            <span class="nav-icon">⊞</span> Dossiers VEFA
            <?php if ($en_cours_nb > 0): ?>
                <span class="nav-badge"><?= $en_cours_nb ?></span>
            <?php endif; ?>
        </a>
        <a href="nouveau_dossier.php" class="nav-item <?= $active==='nouveau'?'active':'' ?>">
            <span class="nav-icon">＋</span> Nouveau dossier
        </a>
        <div style="height:16px"></div>
        <div class="sb-section-label">Monitoring</div>
        <a href="http://localhost:9090" target="_blank" class="nav-item">
            <span class="nav-icon">◉</span> Prometheus <span style="margin-left:auto;font-size:0.65rem;color:var(--muted2)">↗</span>
        </a>
        <a href="http://localhost:3000" target="_blank" class="nav-item">
            <span class="nav-icon">▣</span> Grafana <span style="margin-left:auto;font-size:0.65rem;color:var(--muted2)">↗</span>
        </a>
        <a href="http://localhost:9082" target="_blank" class="nav-item">
            <span class="nav-icon">⊗</span> Adminer <span style="margin-left:auto;font-size:0.65rem;color:var(--muted2)">↗</span>
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