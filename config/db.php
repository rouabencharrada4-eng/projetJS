<?php
// ============================================================
//   NOVASTORE - config/db.php
//   Connexion à la base de données MySQL via PDO
// ============================================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'novastore');
define('DB_USER', 'roua');          // ← change avec ton utilisateur MySQL
define('DB_PASS', 'Roua@2003');              // ← change avec ton mot de passe MySQL
define('DB_CHARSET', 'utf8mb4');

function getDB(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST
             . ';dbname='    . DB_NAME
             . ';charset='   . DB_CHARSET;

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // lance des exceptions
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,         // retourne des tableaux associatifs
            PDO::ATTR_EMULATE_PREPARES   => false,                     // requêtes préparées réelles
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // En production, ne jamais afficher le message brut
            error_log('Erreur DB : ' . $e->getMessage());
            http_response_code(500);
            die(json_encode(['erreur' => 'Connexion base de données impossible.']));
        }
    }

    return $pdo;
}