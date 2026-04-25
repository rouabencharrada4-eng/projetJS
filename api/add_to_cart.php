<?php
// ============================================================
//   NOVASTORE - api/add_to_cart.php
//   Ajouter / retirer un produit du panier (AJAX)
// ============================================================

session_start();
header('Content-Type: application/json');

// Non connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'redirect' => '../auth/login.php']);
    exit;
}

// Admin ne peut pas acheter
if ($_SESSION['role'] === 'admin') {
    echo json_encode(['success' => false, 'message' => 'Les administrateurs ne peuvent pas acheter.']);
    exit;
}

require_once '../config/db.php';
$pdo = getDB();

$data       = json_decode(file_get_contents('php://input'), true);
$produit_id = intval($data['produit_id'] ?? 0);
$quantite   = intval($data['quantite']   ?? 1);

if (!$produit_id) {
    echo json_encode(['success' => false, 'message' => 'Produit invalide.']);
    exit;
}

// Vérifier que le produit existe et est en stock
$stmt = $pdo->prepare('SELECT id, nom, stock, prix FROM produits WHERE id = ? AND actif = 1');
$stmt->execute([$produit_id]);
$produit = $stmt->fetch();

if (!$produit) {
    echo json_encode(['success' => false, 'message' => 'Produit introuvable.']);
    exit;
}

if ($produit['stock'] <= 0) {
    echo json_encode(['success' => false, 'message' => 'Produit en rupture de stock.']);
    exit;
}

// Vérifier si déjà dans le panier
$stmt = $pdo->prepare('SELECT id, quantite FROM panier WHERE utilisateur_id = ? AND produit_id = ?');
$stmt->execute([$_SESSION['user_id'], $produit_id]);
$existant = $stmt->fetch();

if ($existant) {
    // Mettre à jour la quantité
    $nouvelle_qte = $existant['quantite'] + $quantite;
    if ($nouvelle_qte > $produit['stock']) {
        $nouvelle_qte = $produit['stock'];
    }
    $pdo->prepare('UPDATE panier SET quantite = ? WHERE id = ?')
        ->execute([$nouvelle_qte, $existant['id']]);
    $action = 'updated';
} else {
    // Ajouter au panier
    $pdo->prepare('INSERT INTO panier (utilisateur_id, produit_id, quantite) VALUES (?, ?, ?)')
        ->execute([$_SESSION['user_id'], $produit_id, min($quantite, $produit['stock'])]);
    $action = 'added';
}

// Compter le total d'articles dans le panier
$stmt = $pdo->prepare('SELECT SUM(quantite) FROM panier WHERE utilisateur_id = ?');
$stmt->execute([$_SESSION['user_id']]);
$total_panier = intval($stmt->fetchColumn());

echo json_encode([
    'success'      => true,
    'action'       => $action,
    'message'      => '✅ ' . htmlspecialchars($produit['nom']) . ' ajouté au panier !',
    'total_panier' => $total_panier,
]);