<?php
require_once 'config/db.php';
$pdo = getDB();

$users = $pdo->query('SELECT id, email FROM utilisateurs WHERE role = "client"')->fetchAll();

foreach ($users as $u) {
    $hash = password_hash('Test1234!', PASSWORD_BCRYPT);
    $pdo->prepare('UPDATE utilisateurs SET mot_de_passe = ? WHERE id = ?')
        ->execute([$hash, $u['id']]);
    echo "✅ " . $u['email'] . " → mot de passe : Test1234!<br>";
}
echo "<br>Terminé !";
?>