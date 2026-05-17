<?php
session_start();
require_once '../config/db.php';

$pdo = getDB();

$wishlist_ids = [];

if (isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'client') {
    $stmt = $pdo->prepare('SELECT produit_id FROM wishlist WHERE utilisateur_id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $wishlist_ids = array_column($stmt->fetchAll(), 'produit_id');
}

$stmt = $pdo->prepare('
    SELECT p.*, c.nom AS categorie
    FROM produits p
    JOIN categories c ON c.id = p.categorie_id
    WHERE c.slug IN ("electromenager", "ustensiles")
      AND p.actif = 1
    ORDER BY p.note_moyenne DESC
');
$stmt->execute();
$produits_db = $stmt->fetchAll();

$nb_panier = 0;

if (isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'client') {
    $stmt2 = $pdo->prepare('SELECT COALESCE(SUM(quantite),0) FROM panier WHERE utilisateur_id=?');
    $stmt2->execute([$_SESSION['user_id']]);
    $nb_panier = intval($stmt2->fetchColumn());
}

$produits_extra = [];

function etoiles($note) {
    $html = '';

    for ($i = 1; $i <= 5; $i++) {
        $html .= $i <= round($note)
            ? '<i class="fas fa-star"></i>'
            : '<i class="far fa-star"></i>';
    }

    return $html;
}

function carteProduit($p, $wishlist_ids) {
    $id = $p['id'] ?? 0;
    $is_real_product = is_numeric($id) && intval($id) > 0;

    $wishlist_ids_int = array_map('intval', $wishlist_ids);
    $in_wishlist = $is_real_product && in_array(intval($id), $wishlist_ids_int);

    $prix_parts = explode('.', number_format(floatval($p['prix']), 3, '.', ''));
    $image = $p['image'] ?? 'images/placeholder.jpg';
    $stock = intval($p['stock'] ?? 0);

    ob_start();
    ?>
    <div class="product-card">

        <?php if (!empty($p['badge'])): ?>
            <div class="product-badge" style="position:absolute;top:10px;left:10px;background:#E63946;color:white;padding:3px 10px;border-radius:20px;font-size:0.75rem;font-weight:700;">
                <?= htmlspecialchars($p['badge']) ?>
            </div>
        <?php endif; ?>

        <?php if ($is_real_product): ?>
            <button class="wishlist-btn <?= $in_wishlist ? 'active' : '' ?>"
                    type="button"
                    data-id="<?= intval($id) ?>">
                <i class="<?= $in_wishlist ? 'fas' : 'far' ?> fa-heart"></i>
            </button>
        <?php else: ?>
            <button class="wishlist-btn" type="button" title="Produit non disponible" disabled>
                <i class="far fa-heart"></i>
            </button>
        <?php endif; ?>

        <div class="product-img-box">
            <img src="<?= htmlspecialchars($image) ?>" alt="<?= htmlspecialchars($p['nom']) ?>">
        </div>

        <div class="product-info">

            <div class="product-footer-price">
                <div class="price-container">
                    <span class="price-main"><?= htmlspecialchars($prix_parts[0]) ?></span>
                    <span class="price-currency">DT</span>
                    <span class="price-cents"><?= htmlspecialchars($prix_parts[1] ?? '000') ?></span>
                </div>

                <?php if ($is_real_product && $stock > 0): ?>
                    <button class="btn-cart-icon"
                            type="button"
                            data-id="<?= intval($id) ?>">
                        <i class="fas fa-shopping-cart"></i>
                    </button>
                <?php else: ?>
                    <button class="btn-cart-icon"
                            type="button"
                            disabled
                            title="Produit non disponible">
                        <i class="fas fa-ban"></i>
                    </button>
                <?php endif; ?>
            </div>

            <?php if (!empty($p['marque'])): ?>
                <span class="brand-tag"><?= htmlspecialchars($p['marque']) ?></span>
            <?php endif; ?>

            <h3 class="product-name"><?= htmlspecialchars($p['nom']) ?></h3>

            <p class="product-model">
                <?= htmlspecialchars($p['modele'] ?? '') ?>
            </p>

            <div class="rating">
                <?= etoiles($p['note_moyenne'] ?? 0) ?>

                <?php if (($p['nb_avis'] ?? 0) > 0): ?>
                    <span style="font-size:0.8rem; color:#6c757d; margin-left:4px;">
                        (<?= intval($p['nb_avis']) ?>)
                    </span>
                <?php endif; ?>
            </div>

            <?php if ($stock <= 0): ?>
                <p style="margin-top:8px;font-size:0.78rem;color:#ef4444;font-weight:600;">
                    Rupture de stock
                </p>
            <?php endif; ?>

        </div>
    </div>
    <?php

    return ob_get_clean();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Électroménager – NovaStore</title>

    <link rel="stylesheet" href="../style.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        .breadcrumb {
            background: white;
            border-bottom: 1px solid #e9ecef;
            padding: 12px 0;
        }

        .breadcrumb-inner {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            color: #6c757d;
        }

        .breadcrumb-inner a {
            color: #E63946;
            text-decoration: none;
        }

        .page-banner {
            background: linear-gradient(135deg, #1D3557, #2d4a6b);
            border-radius: 16px;
            padding: 32px;
            margin-bottom: 48px;
            color: white;
            text-align: center;
        }

        .page-banner h3 {
            font-family: 'Playfair Display', serif;
            font-size: 1.6rem;
            margin-bottom: 8px;
        }

        .page-banner p {
            color: rgba(255,255,255,0.75);
        }

        .btn-cart-icon:disabled,
        .wishlist-btn:disabled {
            opacity: 0.45;
            cursor: not-allowed;
        }
    </style>
</head>

<body>

<div class="top-bar">
    <div class="container-top-bar">
        <p>Livraison gratuite depuis 200 DT !</p>
    </div>
</div>

<header class="navbar">
    <div class="nav-container">

        <a href="../index.php" class="logo" style="text-decoration:none;">
            <img src="../images/logo.png" alt="NovaStore" class="logo-img">
            Nova<strong>Store</strong>
        </a>

        <div class="nav-search">
            <input type="text" placeholder="Rechercher un produit..." id="search-input">
            <button type="button" onclick="lancerRecherche()">Rechercher</button>
        </div>

        <nav class="nav-actions">
            <?php if (isset($_SESSION['user_id'])): ?>

                <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>

                    <a href="../admin/dashboard.php" class="btn-nav">
                        <i class="fas fa-chart-pie"></i> Dashboard
                    </a>

                    <a href="../auth/logout.php" class="btn-nav" style="color:#E63946;">
                        <i class="fas fa-sign-out-alt"></i> Déconnexion
                    </a>

                <?php else: ?>

                    <a href="../client/profil.php" class="btn-nav">
                        <i class="fas fa-user"></i>
                        <?= htmlspecialchars($_SESSION['prenom'] ?? 'Profil') ?>
                    </a>

                    <a href="../client/panier.php" class="btn-nav btn-primary">
                        <i class="fas fa-shopping-cart"></i> Panier

                        <?php if ($nb_panier > 0): ?>
                            <span id="panier-badge" style="background:white;color:#E63946;border-radius:50%;width:20px;height:20px;display:inline-flex;align-items:center;justify-content:center;font-size:0.75rem;font-weight:700;margin-left:4px;">
                                <?= $nb_panier ?>
                            </span>
                        <?php else: ?>
                            <span id="panier-badge" style="display:none;"></span>
                        <?php endif; ?>
                    </a>

                <?php endif; ?>

            <?php else: ?>

                <a href="../auth/login.php" class="btn-nav">Connexion</a>
                <a href="../auth/register.php" class="btn-nav btn-primary">S'inscrire</a>

            <?php endif; ?>

            <button id="theme-toggle"
                    type="button"
                    onclick="toggleTheme()"
                    style="background:white;border:2px solid #e9ecef;border-radius:50%;width:40px;height:40px;cursor:pointer;font-size:1.2rem;display:flex;align-items:center;justify-content:center;transition:0.3s;flex-shrink:0;">
                🌙
            </button>
        </nav>

    </div>
</header>

<div class="breadcrumb">
    <div class="container">
        <div class="breadcrumb-inner">
            <a href="../index.php">Accueil</a>
            <i class="fas fa-chevron-right" style="font-size:0.7rem;"></i>
            <span style="color:#1D3557;font-weight:600;">Électroménager</span>
        </div>
    </div>
</div>

<section class="product-section" style="padding:50px 0;">

        <h2 class="section-title">
            Tous nos produits
            <span style="font-size:1rem; color:#6c757d; font-weight:400; font-family:'DM Sans',sans-serif;">
                (<?= count($produits_db) + count($produits_extra) ?> produits)
            </span>
        </h2>

        <div class="products-grid">

            <?php foreach ($produits_db as $p): ?>
                <?php
                $p['image'] = '../' . ($p['image'] ?? 'images/placeholder.jpg');
                ?>
                <?= carteProduit($p, $wishlist_ids) ?>
            <?php endforeach; ?>

            <?php foreach ($produits_extra as $p): ?>
                <?= carteProduit($p, $wishlist_ids) ?>
            <?php endforeach; ?>

        </div>

    </div>
</section>

<footer class="footer">
    <div class="container">

        <div class="footer-grid">

            <div>
                <div class="logo-footer">
                    <img src="../images/logo.png" alt="NovaStore" class="logo-img-footer">
                </div>
                <p>La qualité professionnelle au service de votre quotidien.</p>
            </div>

            <div>
                <h4>Aide & Service</h4>
                <a href="#">Livraison</a>
                <a href="#">Retours</a>
                <a href="#">Conditions générales</a>
            </div>

            <div>
                <h4>Contactez-nous</h4>
                <a href="tel:+21672772779">+216 72 772 779</a>
                <a href="mailto:contact@novastore.com">contact@novastore.com</a>
                <p>123 Ghazela 2, Ariana</p>
            </div>

            <div>
                <h4>Suivez-nous</h4>
                <div class="social-links">
                    <a href="https://instagram.com" target="_blank">
                        <i class="fab fa-instagram"></i> Instagram
                    </a>
                    <a href="https://facebook.com" target="_blank">
                        <i class="fab fa-facebook"></i> Facebook
                    </a>
                </div>
            </div>

        </div>

        <div class="footer-bottom">
            <p>© <?= date('Y') ?> NovaStore. Tous droits réservés.</p>
        </div>

    </div>
</footer>

<script>
    window.BASE_URL = '../';

    function lancerRecherche() {
        const q = document.getElementById('search-input').value.trim();

        if (q) {
            window.location.href = `../search.php?q=${encodeURIComponent(q)}`;
        }
    }

    document.getElementById('search-input')?.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            lancerRecherche();
        }
    });
</script>

<script src="../main.js"></script>

</body>
</html>