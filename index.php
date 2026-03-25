<?php require_once 'auth.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>GSB – Accueil</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="index.css">
</head>
<body>

<header class="header">
    <div class="header-left">
        <h1>GSB</h1>
        <span class="subtitle">Application interne – Laboratoire pharmaceutique</span>
    </div>
    <nav class="header-nav">
        <a href="index.php">Accueil</a>
        <a href="notedefrais.php">Notes de frais</a>
        <?php if (is_logged_in()): ?>
             <a href="monprofil.php">Mon profil</a>
             <?php endif; ?>
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

<main>

    <section class="hero">
        <h2>Bienvenue sur GSB</h2>
        <p>
            Votre laboratoire pharmaceutique de référence.
            Cette application interne permet aux visiteurs médicaux de gérer
            efficacement leurs notes de frais et le suivi administratif.
        </p>
        <div class="hero-actions">
            <a href="<?= is_logged_in() ? 'monprofil.php' : 'connexion.php' ?>" class="btn-primary">Accéder à mon espace</a>
            <a href="ensavoirplus.php" class="btn-secondary">En savoir plus</a>
        </div>
    </section>

    <section class="section light">
        <h3>Nos finalités</h3>
        <div class="cards">
            <div class="card">
                <h4>Économique</h4>
                <p>Réduire les coûts en conservant les laboratoires les plus performants et en optimisant les ressources.</p>
            </div>
            <div class="card">
                <h4>Sociale</h4>
                <p>Garantir aux employés un environnement de travail moderne et adapté aux besoins terrain.</p>
            </div>
            <div class="card">
                <h4>Sociétale</h4>
                <p>Améliorer le suivi des activités des visiteurs médicaux et renforcer la confiance des équipes.</p>
            </div>
        </div>
    </section>

    <section class="section">
        <h3>Effectifs & clients</h3>
        <div class="stats">
            <div class="stat">
                <span class="number">4500</span>
                <span class="label">Salariés dans le monde</span>
            </div>
            <div class="stat">
                <span class="number">Clients</span>
                <span class="label">Visiteurs médicaux et comptables </span>
            </div>
        </div>
    </section>

    <section class="section light">
        <h3>Lieux des locaux</h3>
        <div class="cards">
            <div class="card">
                <h4>Philadelphie (États-Unis)</h4>
                <p>Créé en 2009<br>Laboratoire principal</p>
            </div>
            <div class="card">
                <h4>Paris (France)</h4>
                <p>Second laboratoire européen</p>
            </div>
        </div>
    </section>

</main>

<footer class="footer">
    <p>&copy; 2026 – Galaxy Swiss Bourdin</p>
    <p>Application interne – Accès réservé aux collaborateurs</p>
    <p>Contact : <a href="mailto:contact@gsb.fr">contact@gsb.fr</a> | 
       Téléphone : <a href="tel:+330267457631">02 67 45 76 31</a></p>
    <p><a href="mentionlegale.php">Mentions légales</a></p>
</footer>
</body>
</html>
