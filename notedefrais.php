<?php require_once 'auth.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>GSB – Notes de frais</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="notedefrais.css">
</head>
<body>
<header class="header">
    <div class="header-left">
        <h1>GSB</h1>
        <span class="subtitle">Application interne – Laboratoire pharmaceutique</span>
    </div>
    <nav class="header-nav">
        <a href="index.php">Accueil</a>
        <a href="monprofil.php">Mon profil</a>
    </nav>
    <div class="header-right">
        <?php if (is_logged_in()): ?>
            <span class="login-btn">
                👤 <?= htmlspecialchars($_SESSION['user_prenom'] . ' ' . $_SESSION['user_nom']) ?>
            </span>
            <a href="deconnexion.php" class="login-btn" style="margin-left:8px;">Déconnexion</a>
        <?php else: ?>
            <a href="connexion.php" class="login-btn">Connexion</a>
        <?php endif; ?>
    </div>
</header>

<main class="section">
    <h2 style="margin-bottom:30px;">Gestion des notes de frais</h2>
    <div class="cards">
        <div class="card">
            <h3>Consulter mes fiches</h3>
            <p>Visualiser l'historique de vos fiches de frais, suivre leur état (validée, en attente, refusée).</p>
            <br>
            <a href="consulter-fiche.php" class="btn-primary">Consulter</a>
        </div>
        <div class="card">
            <h3>Renseigner / Modifier</h3>
            <p>Saisir une nouvelle fiche de frais ou modifier une fiche existante non validée.</p>
            <br>
            <a href="renseigner-fiche.php" class="btn-primary">Renseigner</a>
        </div>
    </div>
</main>

<footer class="footer">
    <p>&copy; 2026 – Galaxy Swiss Bourdin</p>
    <p>Application interne – Accès réservé aux visiteurs médicaux et comptables de GSB</p>
</footer>
</body>
</html>