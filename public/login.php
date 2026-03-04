<?php
session_start();
require_once '../src/config.php';
require_once 'includes/security.php';

$erreur = '';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF
    csrf_verify();

    $email        = sanitize_string($_POST['email'] ?? '', 150);
    $mot_de_passe = $_POST['mot_de_passe'] ?? '';

    // Rate limiting : max 5 tentatives par 5 min par IP
    if (!rate_limit_check('login_' . md5($_SERVER['REMOTE_ADDR']))) {
        security_log('RATE_LIMIT', "Login bloqué pour $email");
        $erreur = 'Trop de tentatives. Réessayez dans 5 minutes.';
    } elseif (empty($email) || empty($mot_de_passe)) {
        $erreur = 'Veuillez remplir tous les champs.';
    } elseif (!validate_email($email)) {
        $erreur = 'Format d\'email invalide.';
    } else {
        try {
            $db = getDB();
            // Prepared statement — protection SQL injection
            $stmt = $db->prepare("SELECT * FROM utilisateurs WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($mot_de_passe, $user['mot_de_passe'])) {
                // Régénérer l'ID de session (anti session fixation)
                session_regenerate_id(true);
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_nom']  = $user['prenom'] . ' ' . $user['nom'];
                $_SESSION['user_role'] = $user['role'];
                security_log('LOGIN_SUCCESS', "Utilisateur {$user['email']}");
                header('Location: dashboard.php');
                exit;
            } else {
                // Message générique — ne pas indiquer si c'est l'email ou le mdp
                $erreur = 'Identifiants incorrects.';
                security_log('LOGIN_FAIL', "Tentative pour $email");
            }
        } catch (Exception $e) {
            $erreur = 'Erreur serveur. Réessayez.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskFlow — Connexion</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="layout.css">
    <style>
        body { display:flex; min-height:100vh; overflow:hidden; }
        .left-panel { flex:1; position:relative; display:flex; flex-direction:column; justify-content:center; padding:60px; overflow:hidden; }
        .left-panel::before { content:''; position:absolute; inset:0; background:radial-gradient(ellipse 80% 60% at 20% 50%,#F9731612 0%,transparent 70%),radial-gradient(ellipse 60% 80% at 80% 20%,#F9731608 0%,transparent 60%); }
        .grid-bg { position:absolute; inset:0; background-image:linear-gradient(var(--border) 1px,transparent 1px),linear-gradient(90deg,var(--border) 1px,transparent 1px); background-size:48px 48px; opacity:0.4; }
        .brand { position:relative; z-index:1; margin-bottom:56px; }
        .brand-logo { display:flex; align-items:center; gap:12px; margin-bottom:6px; }
        .brand-icon { width:44px; height:44px; background:linear-gradient(135deg,var(--orange),var(--orange-dim)); border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:22px; box-shadow:0 0 30px var(--orange-glow); }
        .brand-name { font-family:'Syne',sans-serif; font-size:1.5rem; font-weight:700; color:var(--text); letter-spacing:-0.02em; }
        .brand-sub { font-size:0.8rem; color:var(--muted); letter-spacing:0.08em; text-transform:uppercase; padding-left:56px; }
        .hero-title { position:relative; z-index:1; font-family:'Syne',sans-serif; font-size:clamp(2rem,4vw,3.4rem); font-weight:800; color:var(--text); line-height:1.1; letter-spacing:-0.03em; margin-bottom:20px; }
        .hero-title span { color:var(--orange); }
        .hero-desc { position:relative; z-index:1; font-size:1rem; color:var(--muted); line-height:1.7; max-width:420px; margin-bottom:48px; }
        .stats-row { position:relative; z-index:1; display:flex; gap:32px; }
        .stat { border-left:2px solid var(--border); padding-left:16px; }
        .stat-val { font-family:'Syne',sans-serif; font-size:1.6rem; font-weight:700; color:var(--orange); line-height:1; }
        .stat-label { font-size:0.72rem; color:var(--muted); margin-top:4px; text-transform:uppercase; letter-spacing:0.06em; }
        .deco-card { position:absolute; background:var(--card); border:1px solid var(--border); border-radius:12px; padding:14px 18px; font-size:0.78rem; color:var(--muted); z-index:1; animation:float 6s ease-in-out infinite; }
        .deco-card .deco-val { font-family:'Syne',sans-serif; font-size:1.1rem; font-weight:700; color:var(--text); margin-bottom:2px; }
        .deco-card.c1 { bottom:200px; right:80px; }
        .deco-card.c2 { bottom:120px; right:200px; animation-delay:2s; }
        .badge-live { display:inline-flex; align-items:center; gap:5px; background:#16A34A15; border:1px solid #16A34A40; color:#4ADE80; padding:4px 10px; border-radius:20px; font-size:0.72rem; margin-bottom:4px; }
        .dot-live { width:6px; height:6px; background:#4ADE80; border-radius:50%; animation:pulse 2s infinite; }
        @keyframes float { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-8px)} }
        @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:0.3} }
        .right-panel { width:480px; background:var(--card); border-left:1px solid var(--border); display:flex; align-items:center; justify-content:center; padding:48px; }
        .login-box { width:100%; }
        .login-title { font-family:'Syne',sans-serif; font-size:1.6rem; font-weight:700; color:var(--text); letter-spacing:-0.02em; margin-bottom:6px; }
        .login-sub { font-size:0.88rem; color:var(--muted); margin-bottom:36px; }
        .form-group { margin-bottom:20px; }
        .form-label { display:block; font-size:0.75rem; font-weight:500; color:var(--muted); text-transform:uppercase; letter-spacing:0.07em; margin-bottom:8px; }
        .form-input { width:100%; padding:13px 16px; background:var(--dark); border:1px solid var(--border); border-radius:8px; color:var(--text); font-size:0.95rem; font-family:'DM Sans',sans-serif; transition:border-color 0.2s,box-shadow 0.2s; outline:none; }
        .form-input:focus { border-color:var(--orange); box-shadow:0 0 0 3px var(--orange-glow); }
        .form-input::placeholder { color:var(--muted2); }
        .btn-submit { width:100%; padding:14px; background:var(--orange); color:#fff; border:none; border-radius:8px; font-family:'Syne',sans-serif; font-size:0.95rem; font-weight:700; cursor:pointer; transition:all 0.2s; margin-top:8px; }
        .btn-submit:hover { background:var(--orange-dim); box-shadow:0 0 24px var(--orange-glow); }
        .btn-submit:active { transform:scale(0.98); }
        .erreur-box { background:var(--red-bg); border:1px solid var(--red-bd); color:var(--red); padding:12px 16px; border-radius:8px; font-size:0.85rem; margin-bottom:20px; }
        .divider { display:flex; align-items:center; gap:12px; margin:24px 0; }
        .divider::before,.divider::after { content:''; flex:1; height:1px; background:var(--border); }
        .divider span { font-size:0.72rem; color:var(--muted2); text-transform:uppercase; letter-spacing:0.08em; }
        .hint-box { background:var(--dark); border:1px solid var(--border); border-radius:8px; padding:14px 16px; }
        .hint-label { font-size:0.7rem; color:var(--muted2); text-transform:uppercase; letter-spacing:0.08em; margin-bottom:8px; }
        .hint-row { display:flex; justify-content:space-between; font-size:0.82rem; margin-bottom:4px; }
        .hint-row .key { color:var(--muted); }
        .hint-row .val { color:var(--orange); font-family:monospace; }
        @media(max-width:768px){ .left-panel{display:none} .right-panel{width:100%;border-left:none} }
    </style>
</head>
<body>
    <div class="left-panel">
        <div class="grid-bg"></div>
        <div class="brand">
            <div class="brand-logo">
                <div class="brand-icon">🦊</div>
                <span class="brand-name">TaskFlow</span>
            </div>
            <div class="brand-sub">Powered by Notatech · PromNot</div>
        </div>
        <h1 class="hero-title">Gérez vos<br>dossiers <span>VEFA</span><br>en temps réel.</h1>
        <p class="hero-desc">La plateforme collaborative entre notaires, promoteurs et réservataires. Suivi en temps réel, monitoring intégré, sécurité totale.</p>
        <div class="stats-row">
            <div class="stat"><div class="stat-val">99.9%</div><div class="stat-label">Uptime</div></div>
            <div class="stat"><div class="stat-val">6</div><div class="stat-label">Services</div></div>
            <div class="stat"><div class="stat-val">24/7</div><div class="stat-label">Monitoring</div></div>
        </div>
        <div class="deco-card c1">
            <div class="badge-live"><span class="dot-live"></span> Live</div>
            <div class="deco-val">955 000 €</div>
            <div>Valeur portefeuille actif</div>
        </div>
        <div class="deco-card c2">
            <div class="deco-val">4 dossiers</div>
            <div>En gestion active</div>
        </div>
    </div>
    <div class="right-panel">
        <div class="login-box">
            <h2 class="login-title">Bon retour.</h2>
            <p class="login-sub">Connectez-vous à votre espace de travail.</p>
            <?php if ($erreur): ?>
                <div class="erreur-box">⚠ <?= htmlspecialchars($erreur) ?></div>
            <?php endif; ?>
            <form method="POST">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label class="form-label">Adresse email</label>
                    <input class="form-input" type="email" name="email" placeholder="vous@exemple.fr" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" autofocus required autocomplete="email">
                </div>
                <div class="form-group">
                    <label class="form-label">Mot de passe</label>
                    <input class="form-input" type="password" name="mot_de_passe" placeholder="••••••••••" required autocomplete="current-password">
                </div>
                <button type="submit" class="btn-submit">Accéder à l'espace →</button>
            </form>
            <div class="divider"><span>accès démo</span></div>
            <div class="hint-box">
                <div class="hint-label">Compte de démonstration</div>
                <div class="hint-row"><span class="key">Email</span><span class="val">admin@taskflow.fr</span></div>
                <div class="hint-row"><span class="key">Mot de passe</span><span class="val">password</span></div>
            </div>
        </div>
    </div>
</body>
</html>