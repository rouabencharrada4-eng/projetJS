<?php

session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'redirect' => 'auth/login.php'
    ]);
    exit;
}

if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    echo json_encode([
        'success' => false,
        'message' => 'Les administrateurs ne peuvent pas acheter.'
    ]);
    exit;
}

require_once '../config/db.php';
$pdo = getDB();

$data = json_decode(file_get_contents('php://input'), true);

if (!is_array($data)) {
    echo json_encode([
        'success' => false,
        'message' => 'Données invalides.'
    ]);
    exit;
}

$produit_id = intval($data['produit_id'] ?? 0);
$quantite = intval($data['quantite'] ?? 1);

if ($quantite <= 0) {
    $quantite = 1;
}

if ($produit_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Produit invalide.'
    ]);
    exit;
}

$stmt = $pdo->prepare('
    SELECT id, nom, stock, prix
    FROM produits
    WHERE id = ? AND actif = 1
    LIMIT 1
');
$stmt->execute([$produit_id]);
$produit = $stmt->fetch();

if (!$produit) {
    echo json_encode([
        'success' => false,
        'message' => 'Produit introuvable.'
    ]);
    exit;
}

if (intval($produit['stock']) <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Produit en rupture de stock.'
    ]);
    exit;
}

$stmt = $pdo->prepare('
    SELECT id, quantite
    FROM panier
    WHERE utilisateur_id = ? AND produit_id = ?
    LIMIT 1
');
$stmt->execute([$_SESSION['user_id'], $produit_id]);
$existant = $stmt->fetch();

if ($existant) {
    $nouvelle_qte = intval($existant['quantite']) + $quantite;

    if ($nouvelle_qte > intval($produit['stock'])) {
        $nouvelle_qte = intval($produit['stock']);
    }

    $stmt = $pdo->prepare('
        UPDATE panier
        SET quantite = ?
        WHERE id = ?
    ');
    $stmt->execute([$nouvelle_qte, $existant['id']]);

    $action = 'updated';
} else {
    $qte_insert = min($quantite, intval($produit['stock']));

    $stmt = $pdo->prepare('
        INSERT INTO panier (utilisateur_id, produit_id, quantite)
        VALUES (?, ?, ?)
    ');
    $stmt->execute([$_SESSION['user_id'], $produit_id, $qte_insert]);

    $action = 'added';
}

$stmt = $pdo->prepare('
    SELECT COALESCE(SUM(quantite), 0)
    FROM panier
    WHERE utilisateur_id = ?
');
$stmt->execute([$_SESSION['user_id']]);
$total_panier = intval($stmt->fetchColumn());

echo json_encode([
    'success' => true,
    'action' => $action,
    'message' => '✅ ' . $produit['nom'] . ' ajouté au panier !',
    'total_panier' => $total_panier
]);
exit;