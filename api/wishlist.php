<?php
// ============================================================
//   NOVASTORE - api/wishlist.php
//   Ajouter / retirer un produit de la wishlist (AJAX)
// ============================================================

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'redirect' => '../auth/login.php']);
    exit;
}

if ($_SESSION['role'] === 'admin') {
    echo json_encode(['success' => false, 'message' => 'Action non disponible.']);
    exit;
}

require_once '../config/db.php';
$pdo = getDB();

$data       = json_decode(file_get_contents('php://input'), true);
$produit_id = intval($data['produit_id'] ?? 0);

if (!$produit_id) {
    echo json_encode(['success' => false, 'message' => 'Produit invalide.']);
    exit;
}

// Vérifier si déjà en wishlist
$stmt = $pdo->prepare('SELECT id FROM wishlist WHERE utilisateur_id = ? AND produit_id = ?');
$stmt->execute([$_SESSION['user_id'], $produit_id]);
$existant = $stmt->fetch();

if ($existant) {
    // Retirer de la wishlist
    $pdo->prepare('DELETE FROM wishlist WHERE id = ?')->execute([$existant['id']]);
    $action  = 'removed';
    $message = 'Retiré de vos favoris.';
    $active  = false;
} else {
    // Ajouter à la wishlist
    $pdo->prepare('INSERT INTO wishlist (utilisateur_id, produit_id) VALUES (?, ?)')
        ->execute([$_SESSION['user_id'], $produit_id]);
    $action  = 'added';
    $message = '❤️ Ajouté à vos favoris !';
    $active  = true;
}

// Total wishlist
$stmt = $pdo->prepare('SELECT COUNT(*) FROM wishlist WHERE utilisateur_id = ?');
$stmt->execute([$_SESSION['user_id']]);
$total_wishlist = intval($stmt->fetchColumn());

echo json_encode([
    'success'        => true,
    'action'         => $action,
    'active'         => $active,
    'message'        => $message,
    'total_wishlist' => $total_wishlist,
]);