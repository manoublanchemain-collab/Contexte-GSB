<?php
require_once 'auth.php';
if (!is_logged_in()) {
    header('Location: connexion.php');
    exit;
}

global $pdo;

// Récupérer uniquement les fiches depuis début année précédente
$anneeMin = (date('Y') - 1) . '01';

$stmt = $pdo->prepare("
    SELECT f.mois, f.idEtat, e.libelle AS etat
    FROM FicheFrais f
    JOIN Etat e ON f.idEtat = e.id
    WHERE f.idVisiteur = :id AND f.mois >= :anneeMin
    ORDER BY f.mois DESC
");
$stmt->execute(['id' => $_SESSION['user_id'], 'anneeMin' => $anneeMin]);
$fiches = $stmt->fetchAll(PDO::FETCH_ASSOC);

$mois_fr = ['01'=>'Janvier','02'=>'Février','03'=>'Mars','04'=>'Avril',
            '05'=>'Mai','06'=>'Juin','07'=>'Juillet','08'=>'Août',
            '09'=>'Septembre','10'=>'Octobre','11'=>'Novembre','12'=>'Décembre'];

$etat_labels = [
    'CR' => 'Saisie en cours',
    'CL' => 'Clôturée',
    'VA' => 'Validée et mise en paiement',
    'RB' => 'Remboursée',
];

$etat_classes = [
    'CR' => 'en-cours',
    'CL' => 'cloture',
    'VA' => 'valide',
    'RB' => 'rembourse',
];

// Charger la fiche si un mois est sélectionné
$moisSelectionne = $_GET['mois'] ?? null;
$fiche = null;
$lignesForfait = [];
$lignesHorsForfait = [];

if ($moisSelectionne) {
    $stmt = $pdo->prepare("
        SELECT f.*, e.libelle AS etat
        FROM FicheFrais f
        JOIN Etat e ON f.idEtat = e.id
        WHERE f.idVisiteur = :id AND f.mois = :mois
    ");
    $stmt->execute(['id' => $_SESSION['user_id'], 'mois' => $moisSelectionne]);
    $fiche = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("
        SELECT lff.quantite, lff.idFraisForfait, ff.libelle, ff.montant,
               (lff.quantite * ff.montant) AS total
        FROM LigneFraisForfait lff
        JOIN FraisForfait ff ON lff.idFraisForfait = ff.id
        WHERE lff.idVisiteur = :id AND lff.mois = :mois
    ");
    $stmt->execute(['id' => $_SESSION['user_id'], 'mois' => $moisSelectionne]);
    $lignesForfait = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("
        SELECT * FROM LigneFraisHorsForfait
        WHERE idVisiteur = :id AND mois = :mois
        ORDER BY date ASC
    ");
    $stmt->execute(['id' => $_SESSION['user_id'], 'mois' => $moisSelectionne]);
    $lignesHorsForfait = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>GSB – Consulter mes fiches</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="consulter-fiche.css">
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

<main style="max-width:1100px; margin:40px auto; padding:0 20px;">
    <h2 style="text-align:center; color:#0a2a66; margin-bottom:30px;">Consulter mes fiches de frais</h2>

    <!-- Sélection du mois -->
    <div style="background:white; padding:25px; border-radius:8px; box-shadow:0 4px 10px rgba(0,0,0,0.05); margin-bottom:30px;">
        <form method="get" action="consulter-fiche.php" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
            <label style="font-weight:bold; color:#0a2a66;">Sélectionnez un mois :</label>
            <select name="mois" style="padding:8px 12px; border:1px solid #ddd; border-radius:6px; font-size:14px;">
                <option value="">-- Choisir un mois --</option>
                <?php foreach ($fiches as $f):
                    $a = substr($f['mois'], 0, 4);
                    $m = substr($f['mois'], 4, 2);
                    $label = ($mois_fr[$m] ?? $m) . ' ' . $a;
                ?>
                <option value="<?= $f['mois'] ?>" <?= $moisSelectionne === $f['mois'] ? 'selected' : '' ?>>
                    <?= $label ?>
                </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-primary">Consulter</button>
        </form>
    </div>

    <!-- Détail de la fiche -->
    <?php if ($fiche): ?>
    <?php
        $annee = substr($moisSelectionne, 0, 4);
        $moisNum = substr($moisSelectionne, 4, 2);
        $moisLabel = ($mois_fr[$moisNum] ?? $moisNum) . ' ' . $annee;
        $etatLabel = $etat_labels[$fiche['idEtat']] ?? $fiche['etat'];
        $etatClass = $etat_classes[$fiche['idEtat']] ?? '';
        $totalForfait = array_sum(array_column($lignesForfait, 'total'));
        $totalHorsForfait = array_sum(array_column($lignesHorsForfait, 'montant'));
    ?>

    <div style="background:white; padding:25px; border-radius:8px; box-shadow:0 4px 10px rgba(0,0,0,0.05);">

        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
            <h3 style="color:#0a2a66; margin:0;">Fiche de <?= $moisLabel ?></h3>
            <span class="etat <?= $etatClass ?>"><?= $etatLabel ?></span>
        </div>

        <p style="color:#666; font-size:14px; margin-bottom:20px;">
            Dernière modification : <strong><?= $fiche['dateModif'] ? date('d/m/Y', strtotime($fiche['dateModif'])) : '–' ?></strong>
        </p>

        <!-- Frais forfaitisés -->
        <h4 style="color:#0a2a66; margin-bottom:10px;">Frais forfaitisés</h4>
        <?php if (empty($lignesForfait)): ?>
            <p style="color:#666; margin-bottom:20px;">Aucun frais forfaitisé.</p>
        <?php else: ?>
        <table class="table-fiches" style="margin-bottom:25px;">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Montant unitaire</th>
                    <th>Quantité</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($lignesForfait as $ligne): ?>
                <tr>
                    <td><?= htmlspecialchars($ligne['libelle']) ?></td>
                    <td><?= number_format($ligne['montant'], 2, ',', ' ') ?> €</td>
                    <td><?= $ligne['quantite'] ?></td>
                    <td><?= number_format($ligne['total'], 2, ',', ' ') ?> €</td>
                </tr>
                <?php endforeach; ?>
                <tr style="font-weight:bold; background:#f0f4fa;">
                    <td colspan="3">Total frais forfaitisés</td>
                    <td><?= number_format($totalForfait, 2, ',', ' ') ?> €</td>
                </tr>
            </tbody>
        </table>
        <?php endif; ?>

        <!-- Frais hors forfait -->
        <h4 style="color:#0a2a66; margin-bottom:10px;">Frais hors forfait</h4>
        <?php if (empty($lignesHorsForfait)): ?>
            <p style="color:#666; margin-bottom:20px;">Aucun frais hors forfait.</p>
        <?php else: ?>
        <table class="table-fiches" style="margin-bottom:25px;">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Libellé</th>
                    <th>Montant</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($lignesHorsForfait as $ligne): ?>
                <tr>
                    <td><?= date('d/m/Y', strtotime($ligne['date'])) ?></td>
                    <td><?= htmlspecialchars($ligne['libelle']) ?></td>
                    <td><?= number_format($ligne['montant'], 2, ',', ' ') ?> €</td>
                </tr>
                <?php endforeach; ?>
                <tr style="font-weight:bold; background:#f0f4fa;">
                    <td colspan="2">Total frais hors forfait</td>
                    <td><?= number_format($totalHorsForfait, 2, ',', ' ') ?> €</td>
                </tr>
            </tbody>
        </table>
        <?php endif; ?>

        <!-- Total général -->
        <div style="text-align:right; font-size:18px; font-weight:bold; color:#0a2a66; padding:15px; background:#f0f4fa; border-radius:8px;">
            Total général : <?= number_format($totalForfait + $totalHorsForfait, 2, ',', ' ') ?> €
        </div>

    </div>

    <?php elseif ($moisSelectionne): ?>
        <p style="color:red;">Aucune fiche trouvée pour ce mois.</p>
    <?php endif; ?>

</main>

<footer class="footer">
    <p>&copy; 2026 – Galaxy Swiss Bourdin</p>
    <p>Application interne – Accès réservé aux collaborateurs</p>
</footer>
</body>
</html>