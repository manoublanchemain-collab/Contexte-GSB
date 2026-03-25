<?php
require_once 'auth.php';
if (!is_logged_in()) {
    header('Location: connexion.php');
    exit;
}

global $pdo;

$stmt = $pdo->prepare("
    SELECT f.mois, f.idEtat, e.libelle AS etat
    FROM FicheFrais f
    JOIN Etat e ON f.idEtat = e.id
    WHERE f.idVisiteur = :id
    ORDER BY f.mois DESC");
$stmt->execute(['id' => $_SESSION['user_id']]);
$fiches = $stmt->fetchAll(PDO::FETCH_ASSOC);

$forfaits = $pdo->query("SELECT * FROM FraisForfait")->fetchAll(PDO::FETCH_ASSOC);

$mois_fr = [
    '01' => 'Janvier', '02' => 'Février', '03' => 'Mars', '04' => 'Avril',
    '05' => 'Mai', '06' => 'Juin', '07' => 'Juillet', '08' => 'Août',
    '09' => 'Septembre', '10' => 'Octobre', '11' => 'Novembre', '12' => 'Décembre'
];

$etats_fr = [
    'CR' => 'Saisie en cours',
    'CL' => 'Clôturée',
    'VA' => 'Validée et mise en paiement',
    'RB' => 'Remboursée'
];

$erreur = '';
$succes = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'creer') {
        $nouveauMois = $_POST['nouveauMoisAnnee'] . $_POST['nouveauMoisMois'];
        $check = $pdo->prepare("SELECT COUNT(*) FROM FicheFrais WHERE idVisiteur=:id AND mois=:mois");
        $check->execute(['id' => $_SESSION['user_id'], 'mois' => $nouveauMois]);
        if ($check->fetchColumn() > 0) {
            $erreur = "Une fiche existe déjà pour ce mois.";
        } else {
            $pdo->prepare("INSERT INTO FicheFrais (idVisiteur, mois, nbJustificatifs, montantValide, dateModif, idEtat) VALUES (:id, :mois, 0, 0, CURDATE(), 'CR')")
                ->execute(['id' => $_SESSION['user_id'], 'mois' => $nouveauMois]);
            foreach ($forfaits as $f) {
                $pdo->prepare("INSERT INTO LigneFraisForfait (idVisiteur, mois, idFraisForfait, quantite) VALUES (:id, :mois, :idf, 0)")
                    ->execute(['id' => $_SESSION['user_id'], 'mois' => $nouveauMois, 'idf' => $f['id']]);
            }
            header("Location: renseigner-fiche.php?mois=$nouveauMois&succes=1");
            exit;
        }
    }

    if ($_POST['action'] === 'enregistrer') {
        $mois = $_POST['mois'];
        $check = $pdo->prepare("SELECT idEtat FROM FicheFrais WHERE idVisiteur=:id AND mois=:mois");
        $check->execute(['id' => $_SESSION['user_id'], 'mois' => $mois]);
        $fiche = $check->fetch();

        if (!$fiche || $fiche['idEtat'] !== 'CR') {
            $erreur = "Cette fiche n'est pas modifiable.";
        } else {
            foreach ($_POST['forfait'] as $idForfait => $quantite) {
                $pdo->prepare("UPDATE LigneFraisForfait SET quantite=:q WHERE idVisiteur=:id AND mois=:mois AND idFraisForfait=:idf")
                    ->execute(['q' => max(0, (int)$quantite), 'id' => $_SESSION['user_id'], 'mois' => $mois, 'idf' => $idForfait]);
            }

            $pdo->prepare("DELETE FROM LigneFraisHorsForfait WHERE idVisiteur=:id AND mois=:mois")
                ->execute(['id' => $_SESSION['user_id'], 'mois' => $mois]);

            $dateMin = date('Y-m-d', strtotime('-1 year'));

            foreach ($_POST['hf_libelle'] ?? [] as $i => $libelle) {
                $libelle = trim($libelle);
                $date    = $_POST['hf_date'][$i] ?? '';
                $montant = $_POST['hf_montant'][$i] ?? 0;

                if ($libelle && $date && $montant > 0) {
                    if (!strtotime($date)) {
                        $erreur = "La date d'engagement doit être valide.";
                        break;
                    }
                    if ($date < $dateMin) {
                        $erreur = "La date d'engagement doit se situer dans l'année écoulée.";
                        break;
                    }
                    $pdo->prepare("INSERT INTO LigneFraisHorsForfait (idVisiteur, mois, libelle, date, montant) VALUES (:id, :mois, :lib, :date, :montant)")
                        ->execute(['id' => $_SESSION['user_id'], 'mois' => $mois, 'lib' => $libelle, 'date' => $date, 'montant' => $montant]);
                }
            }

            if (!$erreur) {
                $pdo->prepare("UPDATE FicheFrais SET dateModif=CURDATE() WHERE idVisiteur=:id AND mois=:mois")
                    ->execute(['id' => $_SESSION['user_id'], 'mois' => $mois]);
                $succes = "Fiche enregistrée avec succès !";
            }
        }
    }
}

$moisSelectionne = $_GET['mois'] ?? null;
$lignesForfait = [];
$lignesHorsForfait = [];
$ficheChargee = null;

if ($moisSelectionne) {
    $stmt = $pdo->prepare("SELECT f.*, e.libelle AS etat FROM FicheFrais f JOIN Etat e ON f.idEtat = e.id WHERE f.idVisiteur=:id AND f.mois=:mois");
    $stmt->execute(['id' => $_SESSION['user_id'], 'mois' => $moisSelectionne]);
    $ficheChargee = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT lff.*, ff.libelle, ff.montant FROM LigneFraisForfait lff JOIN FraisForfait ff ON lff.idFraisForfait = ff.id WHERE lff.idVisiteur=:id AND lff.mois=:mois");
    $stmt->execute(['id' => $_SESSION['user_id'], 'mois' => $moisSelectionne]);
    $lignesForfait = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT * FROM LigneFraisHorsForfait WHERE idVisiteur=:id AND mois=:mois ORDER BY date");
    $stmt->execute(['id' => $_SESSION['user_id'], 'mois' => $moisSelectionne]);
    $lignesHorsForfait = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>GSB – Renseigner une fiche</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="renseigner-fiche.css">
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

    <main class="container">
        <section class="form-card">
            <h2 class="form-title">Renseigner ou modifier une fiche de frais</h2>

            <?php if ($erreur): ?>
                <p style="color:red; margin-bottom:15px;"> <?= htmlspecialchars($erreur) ?></p>
            <?php endif; ?>
            <?php if ($succes || isset($_GET['succes'])): ?>
                <p style="color:green; margin-bottom:15px;"> <?= $succes ?: (isset($_GET['succes']) && $_GET['succes'] === 'suppression' ? 'Fiche supprimée.' : 'Fiche créée avec succès !') ?></p>
            <?php endif; ?>

            <div class="choix-mois">
                <h3>Modifier une fiche existante</h3>
                <form method="get" action="renseigner-fiche.php" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                    <select name="mois">
                        <option value="">Sélectionner un mois</option>
                        <?php foreach ($fiches as $f):
                            $a = substr($f['mois'], 0, 4);
                            $m = substr($f['mois'], 4, 2);
                        ?>
                        <option value="<?= $f['mois'] ?>" <?= $moisSelectionne === $f['mois'] ? 'selected' : '' ?>>
                            <?= ($mois_fr[$m] ?? $m) . ' ' . $a ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn-secondary">Charger la fiche</button>
                </form>

                <hr>

                <h3>Créer une nouvelle fiche</h3>
                <form method="post" action="renseigner-fiche.php" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                    <input type="hidden" name="action" value="creer">
                    <select name="nouveauMoisMois">
                        <option value="01">Janvier</option>
                        <option value="02">Février</option>
                        <option value="03">Mars</option>
                        <option value="04">Avril</option>
                        <option value="05">Mai</option>
                        <option value="06">Juin</option>
                        <option value="07">Juillet</option>
                        <option value="08">Août</option>
                        <option value="09">Septembre</option>
                        <option value="10">Octobre</option>
                        <option value="11">Novembre</option>
                        <option value="12">Décembre</option>
                    </select>
                    <select name="nouveauMoisAnnee">
                        <option value="2024">2024</option>
                        <option value="2025">2025</option>
                        <option value="2026" selected>2026</option>
                    </select>
                    <button type="submit" class="btn-primary">Créer la fiche</button>
                </form>
            </div>

            <?php if ($ficheChargee): ?>
            <hr>
            <?php
                $a = substr($moisSelectionne, 0, 4);
                $m = substr($moisSelectionne, 4, 2);
                $moisLabel = ($mois_fr[$m] ?? $m) . ' ' . $a;
                $modifiable = ($ficheChargee['idEtat'] === 'CR');
                $etatLabel = $etats_fr[$ficheChargee['idEtat']] ?? $ficheChargee['etat'];
            ?>

            <h3>Fiche de <?= $moisLabel ?> — <span style="color:#0a2a66"><?= $etatLabel ?></span></h3>

            <?php if (!$modifiable): ?>
                <p style="color:orange; margin:10px 0;"> Cette fiche ne peut plus être modifiée.</p>
            <?php endif; ?>

            <form method="post" action="renseigner-fiche.php">
                <input type="hidden" name="action" value="enregistrer">
                <input type="hidden" name="mois" value="<?= $moisSelectionne ?>">

                <h3 style="margin-top:20px;">Frais forfaitaires</h3>
                <div class="form-grid">
                    <?php foreach ($lignesForfait as $ligne): ?>
                    <div>
                        <label><?= htmlspecialchars($ligne['libelle']) ?> (<?= number_format($ligne['montant'], 2, ',', ' ') ?> € / unité)</label>
                        <input type="number" name="forfait[<?= $ligne['idFraisForfait'] ?>]"
                            min="0" value="<?= $ligne['quantite'] ?>"
                            <?= !$modifiable ? 'disabled' : '' ?> required>
                    </div>
                    <?php endforeach; ?>
                </div>

                <hr>

                <h3>Frais hors forfait</h3>
                <div class="hors-forfait-list" id="hf-list">
                    <?php if (!empty($lignesHorsForfait)): ?>
                        <?php foreach ($lignesHorsForfait as $ligne): ?>
                        <div class="hors-forfait-item">
                            <input type="date" name="hf_date[]" value="<?= $ligne['date'] ?>" <?= !$modifiable ? 'disabled' : '' ?> required>
                            <input type="text" name="hf_libelle[]" value="<?= htmlspecialchars($ligne['libelle']) ?>" placeholder="Libellé" <?= !$modifiable ? 'disabled' : '' ?> required>
                            <input type="number" step="0.01" min="0.01" name="hf_montant[]" value="<?= $ligne['montant'] ?>" placeholder="Montant (€)" <?= !$modifiable ? 'disabled' : '' ?> required>
                            <?php if ($modifiable): ?>
                                <button type="button" class="btn-secondary" onclick="this.parentElement.remove()">✕</button>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    <?php elseif ($modifiable): ?>
                        <div class="hors-forfait-item">
                            <input type="date" name="hf_date[]" required>
                            <input type="text" name="hf_libelle[]" placeholder="Libellé" required>
                            <input type="number" step="0.01" min="0.01" name="hf_montant[]" placeholder="Montant (€)" required>
                            <button type="button" class="btn-secondary" onclick="this.parentElement.remove()">✕</button>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($modifiable): ?>
                <button type="button" class="btn-secondary" style="margin-top:10px;" onclick="ajouterLigne()">
                    + Ajouter un frais hors forfait
                </button>
                <div style="display:flex; justify-content:space-between; align-items:center; margin-top:20px; width:100%;">
                    <button type="submit" form="form-supprimer" class="btn-primary"
                        onclick="return confirm('Supprimer définitivement cette fiche ?')">Supprimer la fiche</button>
                    <button type="submit" class="btn-primary">Enregistrer la fiche</button>
                </div>
                <?php endif; ?>
            </form>

            <?php if ($modifiable): ?>
            <form id="form-supprimer" method="post" action="supprimer-fiche.php">
                <input type="hidden" name="mois" value="<?= $moisSelectionne ?>">
            </form>
            <?php endif; ?>

            <?php endif; ?>

        </section>
    </main>

    <footer class="footer">
        <p>&copy; 2026 – Galaxy Swiss Bourdin</p>
        <p>Application interne – Accès réservé aux collaborateurs</p>
    </footer>

    <script>
        function ajouterLigne() {
            const list = document.getElementById('hf-list');
            const div = document.createElement('div');
            div.className = 'hors-forfait-item';
            div.innerHTML = `
                <input type="date" name="hf_date[]" required>
                <input type="text" name="hf_libelle[]" placeholder="Libellé" required>
                <input type="number" step="0.01" min="0.01" name="hf_montant[]" placeholder="Montant (€)" required>
                <button type="button" class="btn-secondary" onclick="this.parentElement.remove()">✕</button>
            `;
            list.appendChild(div);
        }
    </script>
</body>
</html>