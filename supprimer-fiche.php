<?php
require_once 'auth.php';
if (!is_logged_in()) {
    header('Location: connexion.php');
    exit;
}

global $pdo;
$mois = $_POST['mois'] ?? null;

if ($mois) {
    $check = $pdo->prepare("SELECT idEtat FROM FicheFrais WHERE idVisiteur=:id AND mois=:mois");
    $check->execute(['id' => $_SESSION['user_id'], 'mois' => $mois]);
    $fiche = $check->fetch();

    if ($fiche && $fiche['idEtat'] === 'CR') {
        $pdo->prepare("DELETE FROM LigneFraisForfait WHERE idVisiteur=:id AND mois=:mois")
            ->execute(['id' => $_SESSION['user_id'], 'mois' => $mois]);
        $pdo->prepare("DELETE FROM LigneFraisHorsForfait WHERE idVisiteur=:id AND mois=:mois")
            ->execute(['id' => $_SESSION['user_id'], 'mois' => $mois]);
        $pdo->prepare("DELETE FROM FicheFrais WHERE idVisiteur=:id AND mois=:mois")
            ->execute(['id' => $_SESSION['user_id'], 'mois' => $mois]);
    }
}

header('Location: renseigner-fiche.php?succes=suppression');
exit;
?>