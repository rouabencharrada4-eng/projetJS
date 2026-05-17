<?php

header('Content-Type: application/json; charset=utf-8');

require_once '../config/db.php';

$pdo = getDB();

$q   = trim($_GET['q'] ?? '');
$cat = intval($_GET['cat'] ?? 0);

if (strlen($q) < 2 && !$cat) {
    echo json_encode([
        'success' => true,
        'produits' => [],
        'count' => 0
    ]);
    exit;
}

$sql = '
    SELECT 
        p.id,
        p.nom,
        p.marque,
        p.prix,
        p.image,
        p.stock,
        p.note_moyenne,
        c.nom AS categorie
    FROM produits p
    JOIN categories c ON c.id = p.categorie_id
    WHERE p.actif = 1
';

$params = [];

if ($q) {
    $sql .= ' AND (p.nom LIKE ? OR p.marque LIKE ? OR p.modele LIKE ?)';
    $params[] = "%$q%";
    $params[] = "%$q%";
    $params[] = "%$q%";
}

if ($cat) {
    $sql .= ' AND p.categorie_id = ?';
    $params[] = $cat;
}

$sql .= ' ORDER BY p.note_moyenne DESC, p.nom ASC LIMIT 6';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$produits = $stmt->fetchAll();

echo json_encode([
    'success'  => true,
    'produits' => $produits,
    'count'    => count($produits),
]);
exit;