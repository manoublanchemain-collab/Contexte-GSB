<?php
require_once 'auth.php';
if (!is_logged_in()) {
    header('Location: connexion.php');
    exit;
}

global $pdo;
$stmt = $pdo->prepare("SELECT mdp FROM visiteur WHERE id = :id");
$stmt->execute(['id' => $_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!password_verify($_POST['ancien_mdp'], $user['mdp'])) {
    header('Location: monprofil.php?error_mdp=1#changer-mdp');
    exit;
}

if ($_POST['nouveau_mdp'] !== $_POST['confirm_mdp']) {
    header('Location: monprofil.php?error_mdp=1#changer-mdp');
    exit;
}

$hash = password_hash($_POST['nouveau_mdp'], PASSWORD_BCRYPT);
$stmt = $pdo->prepare("UPDATE visiteur SET mdp=:mdp WHERE id=:id");
$stmt->execute(['mdp' => $hash, 'id' => $_SESSION['user_id']]);

header('Location: monprofil.php?success_mdp=1');
exit;
?>