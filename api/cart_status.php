<?php
// ============================================================
//   NOVASTORE - api/cart_status.php
//   Retourne le nombre d'articles dans le panier (AJAX)
// ============================================================

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    echo json_encode(['total' => 0, 'connecte' => false]);
    exit;
}

require_once '../config/db.php';
$pdo = getDB();

$stmt = $pdo->prepare('SELECT COALESCE(SUM(quantite), 0) FROM panier WHERE utilisateur_id = ?');
$stmt->execute([$_SESSION['user_id']]);
$total = intval($stmt->fetchColumn());

$stmt2 = $pdo->prepare('SELECT COUNT(*) FROM wishlist WHERE utilisateur_id = ?');
$stmt2->execute([$_SESSION['user_id']]);
$wishlist = intval($stmt2->fetchColumn());

// IDs des produits en wishlist (pour colorier les coeurs)
$stmt3 = $pdo->prepare('SELECT produit_id FROM wishlist WHERE utilisateur_id = ?');
$stmt3->execute([$_SESSION['user_id']]);
$wishlist_ids = array_column($stmt3->fetchAll(), 'produit_id');

echo json_encode([
    'connecte'     => true,
    'nom'          => $_SESSION['prenom'],
    'total_panier' => $total,
    'total_wishlist'=> $wishlist,
    'wishlist_ids' => $wishlist_ids,
]);