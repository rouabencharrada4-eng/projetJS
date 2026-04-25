<?php
// ============================================================
//   NOVASTORE - client/panier.php
//   Panier du client connecté
// ============================================================

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header('Location: ../auth/login.php');
    exit;
}

require_once '../config/db.php';
$pdo = getDB();

$message  = '';
$type_msg = '';

// ---- Modifier la quantité ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'update_qty') {
        $produit_id = intval($_POST['produit_id']);
        $quantite   = intval($_POST['quantite']);

        if ($quantite <= 0) {
            $pdo->prepare('DELETE FROM panier WHERE utilisateur_id=? AND produit_id=?')
                ->execute([$_SESSION['user_id'], $produit_id]);
        } else {
            // Vérifier le stock disponible
            $stmt = $pdo->prepare('SELECT stock FROM produits WHERE id=?');
            $stmt->execute([$produit_id]);
            $produit = $stmt->fetch();

            if ($quantite > $produit['stock']) {
                $message  = 'Stock insuffisant. Maximum disponible : ' . $produit['stock'];
                $type_msg = 'error';
            } else {
                $pdo->prepare('UPDATE panier SET quantite=? WHERE utilisateur_id=? AND produit_id=?')
                    ->execute([$quantite, $_SESSION['user_id'], $produit_id]);
            }
        }
    }

    if ($_POST['action'] === 'remove') {
        $pdo->prepare('DELETE FROM panier WHERE utilisateur_id=? AND produit_id=?')
            ->execute([$_SESSION['user_id'], intval($_POST['produit_id'])]);
    }

    if ($_POST['action'] === 'vider') {
        $pdo->prepare('DELETE FROM panier WHERE utilisateur_id=?')
            ->execute([$_SESSION['user_id']]);
        $message  = 'Panier vidé.';
        $type_msg = 'success';
    }

    // ---- Passer la commande ----
    if ($_POST['action'] === 'commander') {
        $adresse_id     = intval($_POST['adresse_id']) ?: null;
        $mode_paiement  = $_POST['mode_paiement'] ?? 'especes';
        $notes          = trim($_POST['notes'] ?? '');

        // Récupérer les articles du panier
        $stmt = $pdo->prepare('
            SELECT p.id, p.nom, p.prix, p.stock, pan.quantite
            FROM panier pan
            JOIN produits p ON p.id = pan.produit_id
            WHERE pan.utilisateur_id = ?
        ');
        $stmt->execute([$_SESSION['user_id']]);
        $articles = $stmt->fetchAll();

        if (empty($articles)) {
            $message  = 'Votre panier est vide.';
            $type_msg = 'error';
        } else {
            // Vérifier les stocks
            $erreur_stock = false;
            foreach ($articles as $art) {
                if ($art['quantite'] > $art['stock']) {
                    $message      = 'Stock insuffisant pour : ' . $art['nom'];
                    $type_msg     = 'error';
                    $erreur_stock = true;
                    break;
                }
            }

            if (!$erreur_stock) {
                // Calculer le total
                $total = 0;
                foreach ($articles as $art) {
                    $total += $art['prix'] * $art['quantite'];
                }

                // Créer la commande
                $stmt = $pdo->prepare('
                    INSERT INTO commandes (utilisateur_id, adresse_id, total, mode_paiement, notes)
                    VALUES (?, ?, ?, ?, ?)
                ');
                $stmt->execute([$_SESSION['user_id'], $adresse_id, $total, $mode_paiement, $notes]);
                $commande_id = $pdo->lastInsertId();

                // Insérer les lignes de commande + mettre à jour les stocks
                foreach ($articles as $art) {
                    $sous_total = $art['prix'] * $art['quantite'];
                    $pdo->prepare('
                        INSERT INTO lignes_commande (commande_id, produit_id, quantite, prix_unitaire, sous_total)
                        VALUES (?, ?, ?, ?, ?)
                    ')->execute([$commande_id, $art['id'], $art['quantite'], $art['prix'], $sous_total]);

                    $pdo->prepare('UPDATE produits SET stock = stock - ? WHERE id = ?')
                        ->execute([$art['quantite'], $art['id']]);
                }

                // Vider le panier
                $pdo->prepare('DELETE FROM panier WHERE utilisateur_id=?')
                    ->execute([$_SESSION['user_id']]);

                $message  = '✅ Commande #' . $commande_id . ' passée avec succès !';
                $type_msg = 'success';
            }
        }
    }
}

// ---- Récupérer le panier ----
$stmt = $pdo->prepare('
    SELECT pan.quantite, p.id, p.nom, p.marque, p.prix, p.image, p.stock, p.modele
    FROM panier pan
    JOIN produits p ON p.id = pan.produit_id
    WHERE pan.utilisateur_id = ?
    ORDER BY pan.added_at DESC
');
$stmt->execute([$_SESSION['user_id']]);
$articles = $stmt->fetchAll();

// Total du panier
$total = 0;
foreach ($articles as $art) {
    $total += $art['prix'] * $art['quantite'];
}

// Adresses de livraison
$stmt = $pdo->prepare('SELECT * FROM adresses WHERE utilisateur_id=? ORDER BY par_defaut DESC');
$stmt->execute([$_SESSION['user_id']]);
$adresses = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Panier – NovaStore</title>
    <link rel="stylesheet" href="../style.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background: #f1f5f9; }
        .page { max-width: 1100px; margin: 40px auto; padding: 0 20px; }
        .page-title { font-family: 'Playfair Display', serif; font-size: 1.8rem; color: #1D3557; margin-bottom: 28px; }

        /* Layout 2 colonnes */
        .panier-layout {
            display: grid;
            grid-template-columns: 1fr 360px;
            gap: 24px;
            align-items: start;
        }

        /* Card générique */
        .card {
            background: white; border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06); overflow: hidden;
        }
        .card-header {
            padding: 20px 24px; border-bottom: 1px solid #f1f5f9;
            display: flex; align-items: center; justify-content: space-between;
        }
        .card-header h3 { font-size: 1rem; color: #1D3557; font-weight: 700; }

        /* Articles */
        .article-row {
            display: flex; align-items: center; gap: 16px;
            padding: 20px 24px; border-bottom: 1px solid #f1f5f9;
        }
        .article-row:last-child { border-bottom: none; }
        .article-img {
            width: 80px; height: 80px; object-fit: contain;
            border-radius: 8px; background: #f8f9fa; flex-shrink: 0;
        }
        .article-img-placeholder {
            width: 80px; height: 80px; border-radius: 8px; background: #f8f9fa;
            display: flex; align-items: center; justify-content: center; color: #ccc;
            flex-shrink: 0;
        }
        .article-info { flex: 1; }
        .article-name { font-weight: 700; color: #1D3557; margin-bottom: 2px; }
        .article-brand { font-size: 0.8rem; color: #E63946; font-weight: 600; }
        .article-model { font-size: 0.8rem; color: #6c757d; }
        .article-price { font-size: 1.1rem; font-weight: 700; color: #007bff; white-space: nowrap; }
        .article-subtotal { font-size: 0.8rem; color: #6c757d; }

        /* Quantité */
        .qty-control {
            display: flex; align-items: center; gap: 8px;
        }
        .qty-btn {
            width: 30px; height: 30px; border-radius: 6px;
            border: 2px solid #e9ecef; background: white; cursor: pointer;
            font-size: 1rem; font-weight: 700; display: flex;
            align-items: center; justify-content: center; transition: 0.2s;
        }
        .qty-btn:hover { border-color: #E63946; color: #E63946; }
        .qty-input {
            width: 48px; text-align: center; border: 2px solid #e9ecef;
            border-radius: 6px; padding: 4px; font-family: 'DM Sans', sans-serif;
            font-weight: 700; font-size: 0.95rem;
        }

        /* Bouton supprimer */
        .btn-remove {
            background: #fee2e2; color: #ef4444; border: none;
            padding: 6px 10px; border-radius: 6px; cursor: pointer;
            font-size: 0.85rem; transition: 0.2s;
        }
        .btn-remove:hover { background: #fecaca; }

        /* Vider panier */
        .btn-vider {
            background: none; border: none; color: #ef4444;
            font-size: 0.85rem; cursor: pointer; font-family: 'DM Sans', sans-serif;
            font-weight: 600; display: flex; align-items: center; gap: 4px;
        }

        /* Récap commande */
        .recap { padding: 24px; }
        .recap-line {
            display: flex; justify-content: space-between;
            margin-bottom: 12px; font-size: 0.95rem; color: #374151;
        }
        .recap-line.total {
            font-size: 1.2rem; font-weight: 700; color: #1D3557;
            border-top: 2px solid #f1f5f9; padding-top: 12px; margin-top: 8px;
        }
        .recap-line.livraison { color: #10b981; font-weight: 600; }

        /* Formulaire commande */
        .form-group { margin-bottom: 14px; }
        .form-group label { display: block; font-weight: 600; font-size: 0.85rem; color: #374151; margin-bottom: 6px; }
        .form-group select, .form-group textarea {
            width: 100%; padding: 10px 14px; border: 2px solid #e9ecef;
            border-radius: 8px; font-family: 'DM Sans', sans-serif;
            font-size: 0.9rem; outline: none; transition: 0.2s;
        }
        .form-group select:focus, .form-group textarea:focus { border-color: #E63946; }

        .btn-commander {
            width: 100%; background: #E63946; color: white; border: none;
            padding: 14px; border-radius: 10px; font-family: 'DM Sans', sans-serif;
            font-size: 1rem; font-weight: 700; cursor: pointer; transition: 0.2s;
            display: flex; align-items: center; justify-content: center; gap: 8px;
            margin-top: 16px;
        }
        .btn-commander:hover { background: #c1121f; transform: translateY(-1px); }

        /* Panier vide */
        .panier-vide {
            text-align: center; padding: 60px 20px; color: #6c757d;
        }
        .panier-vide i { font-size: 4rem; color: #dee2e6; margin-bottom: 16px; display: block; }
        .panier-vide h3 { font-size: 1.3rem; margin-bottom: 8px; color: #374151; }
        .btn-continuer {
            display: inline-flex; align-items: center; gap: 8px;
            background: #1D3557; color: white; padding: 12px 24px;
            border-radius: 8px; text-decoration: none; font-weight: 600;
            margin-top: 20px; transition: 0.2s;
        }
        .btn-continuer:hover { background: #E63946; }

        @media (max-width: 768px) {
            .panier-layout { grid-template-columns: 1fr; }
            .article-row { flex-wrap: wrap; }
        }
    </style>
</head>
<body>

<!-- NAVBAR -->
<header class="navbar">
    <div class="nav-container">
        <a href="../index.php" class="logo">Nova<strong>Store</strong></a>
        <nav class="nav-actions">
            <a href="profil.php" class="btn-nav"><i class="fas fa-user"></i> Mon profil</a>
            <a href="commandes.php" class="btn-nav"><i class="fas fa-box"></i> Mes commandes</a>
            <a href="../auth/logout.php" class="btn-nav" style="color:#E63946;">
                <i class="fas fa-sign-out-alt"></i> Déconnexion
            </a>
        </nav>
    </div>
</header>

<div class="page">

    <?php if ($message): ?>
    <div class="alert alert-<?= $type_msg ?>" style="margin-bottom:20px;">
        <i class="fas fa-<?= $type_msg === 'success' ? 'check' : 'exclamation' ?>-circle"></i>
        <?= htmlspecialchars($message) ?>
        <?php if ($type_msg === 'success' && str_contains($message, 'Commande')): ?>
            <a href="commandes.php" style="margin-left:12px; font-weight:700; color:#065f46;">Voir mes commandes →</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <h1 class="page-title">
        <i class="fas fa-shopping-cart" style="color:#E63946;"></i>
        Mon panier
        <?php if (!empty($articles)): ?>
        <span style="font-size:1rem; color:#6c757d; font-family:'DM Sans',sans-serif; font-weight:400;">
            (<?= count($articles) ?> article<?= count($articles) > 1 ? 's' : '' ?>)
        </span>
        <?php endif; ?>
    </h1>

    <?php if (empty($articles)): ?>
    <!-- Panier vide -->
    <div class="card">
        <div class="panier-vide">
            <i class="fas fa-shopping-cart"></i>
            <h3>Votre panier est vide</h3>
            <p>Découvrez nos produits et ajoutez-les à votre panier !</p>
            <a href="../index.php" class="btn-continuer">
                <i class="fas fa-store"></i> Continuer mes achats
            </a>
        </div>
    </div>

    <?php else: ?>
    <div class="panier-layout">

        <!-- ===== ARTICLES ===== -->
        <div>
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-list" style="color:#E63946; margin-right:8px;"></i>Articles</h3>
                    <form method="POST">
                        <input type="hidden" name="action" value="vider">
                        <button type="submit" class="btn-vider" onclick="return confirm('Vider le panier ?')">
                            <i class="fas fa-trash"></i> Vider le panier
                        </button>
                    </form>
                </div>

                <?php foreach ($articles as $art): ?>
                <div class="article-row">
                    <!-- Image -->
                    <?php if ($art['image']): ?>
                    <img src="../<?= htmlspecialchars($art['image']) ?>" alt="" class="article-img">
                    <?php else: ?>
                    <div class="article-img-placeholder"><i class="fas fa-image fa-2x"></i></div>
                    <?php endif; ?>

                    <!-- Infos -->
                    <div class="article-info">
                        <div class="article-brand"><?= htmlspecialchars($art['marque'] ?? '') ?></div>
                        <div class="article-name"><?= htmlspecialchars($art['nom']) ?></div>
                        <div class="article-model"><?= htmlspecialchars($art['modele'] ?? '') ?></div>
                    </div>

                    <!-- Prix unitaire -->
                    <div style="text-align:right; min-width:90px;">
                        <div class="article-price"><?= number_format($art['prix'], 3) ?> DT</div>
                        <div class="article-subtotal">
                            Sous-total : <?= number_format($art['prix'] * $art['quantite'], 3) ?> DT
                        </div>
                    </div>

                    <!-- Quantité -->
                    <form method="POST" style="display:flex; align-items:center; gap:6px;">
                        <input type="hidden" name="action" value="update_qty">
                        <input type="hidden" name="produit_id" value="<?= $art['id'] ?>">
                        <div class="qty-control">
                            <button type="submit" name="quantite" value="<?= $art['quantite'] - 1 ?>" class="qty-btn">−</button>
                            <input type="number" name="quantite" value="<?= $art['quantite'] ?>"
                                   min="0" max="<?= $art['stock'] ?>" class="qty-input"
                                   onchange="this.form.submit()">
                            <button type="submit" name="quantite" value="<?= $art['quantite'] + 1 ?>" class="qty-btn"
                                    <?= $art['quantite'] >= $art['stock'] ? 'disabled' : '' ?>>+</button>
                        </div>
                    </form>

                    <!-- Supprimer -->
                    <form method="POST">
                        <input type="hidden" name="action" value="remove">
                        <input type="hidden" name="produit_id" value="<?= $art['id'] ?>">
                        <button type="submit" class="btn-remove" title="Supprimer">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Continuer les achats -->
            <div style="margin-top:16px;">
                <a href="../index.php" style="color:#6c757d; text-decoration:none; font-size:0.9rem;">
                    <i class="fas fa-arrow-left"></i> Continuer mes achats
                </a>
            </div>
        </div>

        <!-- ===== RÉCAP + COMMANDE ===== -->
        <div>
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-receipt" style="color:#E63946; margin-right:8px;"></i>Récapitulatif</h3>
                </div>
                <div class="recap">
                    <!-- Lignes récap -->
                    <?php foreach ($articles as $art): ?>
                    <div class="recap-line">
                        <span><?= htmlspecialchars($art['nom']) ?> x<?= $art['quantite'] ?></span>
                        <span><?= number_format($art['prix'] * $art['quantite'], 3) ?> DT</span>
                    </div>
                    <?php endforeach; ?>

                    <div class="recap-line livraison">
                        <span><i class="fas fa-truck"></i> Livraison</span>
                        <span><?= $total >= 200 ? 'Gratuite ✅' : '+ 7.000 DT' ?></span>
                    </div>

                    <div class="recap-line total">
                        <span>Total</span>
                        <span><?= number_format($total >= 200 ? $total : $total + 7, 3) ?> DT</span>
                    </div>

                    <?php if ($total < 200): ?>
                    <p style="font-size:0.8rem; color:#f59e0b; margin-top:8px;">
                        <i class="fas fa-info-circle"></i>
                        Il vous manque <?= number_format(200 - $total, 3) ?> DT pour la livraison gratuite !
                    </p>
                    <?php endif; ?>

                    <hr style="margin:20px 0; border-color:#f1f5f9;">

                    <!-- Formulaire commande -->
                    <form method="POST">
                        <input type="hidden" name="action" value="commander">

                        <div class="form-group">
                            <label><i class="fas fa-map-marker-alt"></i> Adresse de livraison</label>
                            <select name="adresse_id">
                                <option value="">-- Sélectionner une adresse --</option>
                                <?php foreach ($adresses as $addr): ?>
                                <option value="<?= $addr['id'] ?>" <?= $addr['par_defaut'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($addr['adresse'] . ', ' . $addr['ville']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (empty($adresses)): ?>
                            <a href="profil.php#adresses" style="font-size:0.8rem; color:#E63946;">
                                + Ajouter une adresse dans mon profil
                            </a>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-credit-card"></i> Mode de paiement</label>
                            <select name="mode_paiement">
                                <option value="especes">💵 Paiement à la livraison</option>
                                <option value="carte">💳 Carte bancaire</option>
                                <option value="virement">🏦 Virement bancaire</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-comment"></i> Notes (optionnel)</label>
                            <textarea name="notes" rows="2" placeholder="Instructions spéciales..."></textarea>
                        </div>

                        <button type="submit" class="btn-commander">
                            <i class="fas fa-check-circle"></i> Confirmer la commande
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div>
    <?php endif; ?>
</div>

</body>
</html>