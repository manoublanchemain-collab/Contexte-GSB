<?php
require_once 'auth.php';
if (!is_logged_in()) {
    header('Location: connexion.php');
    exit;
}

global $pdo;
$stmt = $pdo->prepare("UPDATE visiteur SET adresse=:adresse, cp=:cp, ville=:ville WHERE id=:id");
$stmt->execute([
    'adresse' => $_POST['adresse'],
    'cp'      => $_POST['cp'],
    'ville'   => $_POST['ville'],
    'id'      => $_SESSION['user_id']
]);

header('Location: monprofil.php?success_infos=1');
exit;
?>