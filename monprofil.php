<?php
require_once 'auth.php';
if (!is_logged_in()) {
    header('Location: connexion.php');
    exit;
}

global $pdo;
$stmt = $pdo->prepare("SELECT * FROM visiteur WHERE id = :id");
$stmt->execute(['id' => $_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>GSB – Mon profil</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="monprofil.css">
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

    <main class="container">

        <section class="profil-card">
            <h2>Mon profil</h2>
            <div class="profil-grid">
                <div class="profil-item">
                    <span class="label">Nom</span>
                    <span class="value"><?= htmlspecialchars($user['nom']) ?></span>
                </div>
                <div class="profil-item">
                    <span class="label">Prénom</span>
                    <span class="value"><?= htmlspecialchars($user['prenom']) ?></span>
                </div>
                <div class="profil-item">
                    <span class="label">Login</span>
                    <span class="value"><?= htmlspecialchars($user['login']) ?></span>
                </div>
                <div class="profil-item">
                    <span class="label">Adresse</span>
                    <span class="value"><?= htmlspecialchars($user['adresse']) ?></span>
                </div>
                <div class="profil-item">
                    <span class="label">Ville</span>
                    <span class="value"><?= htmlspecialchars($user['cp'] . ' ' . $user['ville']) ?></span>
                </div>
                <div class="profil-item">
                    <span class="label">Date d'embauche</span>
                    <span class="value"><?= date('d/m/Y', strtotime($user['dateEmbauche'])) ?></span>
                </div>
                <div class="profil-item">
                    <span class="label">ID</span>
                    <span class="value"><?= htmlspecialchars($user['id']) ?></span>
                </div>
            </div>
            <div class="profil-actions">
                <a href="#modifier-infos" class="btn-primary">Modifier mes informations</a>
                <a href="#changer-mdp" class="btn-secondary">Changer mon mot de passe</a>
            </div>
        </section>

        <section class="profil-card" id="modifier-infos" style="margin-top:20px;">
            <h2>Modifier mes informations</h2>
            <?php if (isset($_GET['success_infos'])): ?>
                <p style="color:green; margin-bottom:10px;">Informations mises à jour !</p>
            <?php endif; ?>
            <form method="post" action="modifier-infos.php">
                <div class="profil-grid">
                    <div class="profil-item">
                        <label class="label">Adresse</label>
                        <input type="text" name="adresse" value="<?= htmlspecialchars($user['adresse']) ?>" class="input-field">
                    </div>
                    <div class="profil-item">
                        <label class="label">Code postal</label>
                        <input type="text" name="cp" value="<?= htmlspecialchars($user['cp']) ?>" class="input-field">
                    </div>
                    <div class="profil-item">
                        <label class="label">Ville</label>
                        <input type="text" name="ville" value="<?= htmlspecialchars($user['ville']) ?>" class="input-field">
                    </div>
                </div>
                <div class="profil-actions">
                    <button type="submit" class="btn-primary">Enregistrer</button>
                </div>
            </form>
        </section>

        <section class="profil-card" id="changer-mdp" style="margin-top:20px;">
            <h2>Changer mon mot de passe</h2>
            <?php if (isset($_GET['success_mdp'])): ?>
                <p style="color:green; margin-bottom:10px;"> Mot de passe mis à jour !</p>
            <?php endif; ?>
            <?php if (isset($_GET['error_mdp'])): ?>
                <p style="color:red; margin-bottom:10px;"> Ancien mot de passe incorrect.</p>
            <?php endif; ?>
            <form method="post" action="modifier-mdp.php">
                <div class="profil-grid">
                    <div class="profil-item">
                        <label class="label">Ancien mot de passe</label>
                        <input type="password" name="ancien_mdp" class="input-field" required>
                    </div>
                    <div class="profil-item">
                        <label class="label">Nouveau mot de passe</label>
                        <input type="password" name="nouveau_mdp" class="input-field" required>
                    </div>
                    <div class="profil-item">
                        <label class="label">Confirmer le nouveau mot de passe</label>
                        <input type="password" name="confirm_mdp" class="input-field" required>
                    </div>
                </div>
                <div class="profil-actions">
                    <button type="submit" class="btn-primary">Changer le mot de passe</button>
                </div>
            </form>
        </section>

    </main>

    <footer class="footer">
        <p>&copy; 2026 – Galaxy Swiss Bourdin</p>
        <p>Application interne – Accès réservé aux collaborateurs</p>
        <p>Contact : <a href="mailto:contact@gsb.fr">contact@gsb.fr</a> |
            Téléphone : <a href="tel:+330267457631">02 67 45 76 31</a></p>
    </footer>
</body>

</html>