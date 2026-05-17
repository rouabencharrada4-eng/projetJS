<?php

session_start();
require_once '../config/db.php';

$pdo = getDB();

$wishlist_ids = [];
$nb_panier = 0;

if (isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'client') {
    $stmt = $pdo->prepare('SELECT produit_id FROM wishlist WHERE utilisateur_id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $wishlist_ids = array_column($stmt->fetchAll(), 'produit_id');

    $stmt2 = $pdo->prepare('SELECT COALESCE(SUM(quantite),0) FROM panier WHERE utilisateur_id=?');
    $stmt2->execute([$_SESSION['user_id']]);
    $nb_panier = intval($stmt2->fetchColumn());
}

$produits = [
    [
        'nom'      => 'Lot de 10 Ustensiles',
        'marque'   => 'NOVASTORE',
        'modele'   => 'Cuisine pratique – Qualité premium',
        'prix'     => 12.000,
        'image'    => '../images/lot.jpg',
        'note'     => 4.5,
        'avis'     => 27,
        'couleurs' => [
            ['nom' => 'Gris', 'hex' => '#9ca3af'],
            ['nom' => 'Rose', 'hex' => '#f9a8d4'],
        ],
    ],
    [
        'nom'      => 'Lot 6 Ustensiles + Support',
        'marque'   => 'NOVASTORE',
        'modele'   => 'Matière nylon et polypropylène',
        'prix'     => 25.000,
        'image'    => '../images/lotsupport.jpg',
        'note'     => 4.0,
        'avis'     => 15,
        'couleurs' => [
            ['nom' => 'Gris', 'hex' => '#9ca3af'],
            ['nom' => 'Rose', 'hex' => '#f9a8d4'],
        ],
    ],
    [
        'nom'      => 'Lot De 3 Faitouts',
        'marque'   => 'NOVASTORE',
        'modele'   => 'En Acier Inoxydable Sans PFAS – Elo Brillant',
        'prix'     => 190.000,
        'image'    => '../images/tnajer.jpg',
        'note'     => 5.0,
        'avis'     => 42,
        'couleurs' => [
            ['nom' => 'Argent', 'hex' => '#d1d5db'],
        ],
    ],
];


$noms_produits = array_column($produits, 'nom');
$produits_db_ids = [];

if (!empty($noms_produits)) {
    $placeholders = implode(',', array_fill(0, count($noms_produits), '?'));

    $stmt = $pdo->prepare("
        SELECT id, nom, stock, prix, note_moyenne, nb_avis
        FROM produits
        WHERE nom IN ($placeholders)
          AND actif = 1
    ");
    $stmt->execute($noms_produits);

    foreach ($stmt->fetchAll() as $row) {
        $produits_db_ids[$row['nom']] = [
            'id' => intval($row['id']),
            'stock' => intval($row['stock']),
            'prix' => floatval($row['prix']),
            'note' => floatval($row['note_moyenne']),
            'avis' => intval($row['nb_avis'])
        ];
    }
}

function etoiles($note) {
    $html = '';

    for ($i = 1; $i <= 5; $i++) {
        $html .= $i <= round(floatval($note))
            ? '<i class="fas fa-star"></i>'
            : '<i class="far fa-star"></i>';
    }

    return $html;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ustensiles de Cuisine – NovaStore</title>

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
            background: linear-gradient(135deg, #f97316, #c2410c);
            border-radius: 18px;
            padding: 36px 24px;
            margin-bottom: 48px;
            color: white;
            text-align: center;
            box-shadow: 0 10px 30px rgba(249, 115, 22, 0.25);
        }

        .page-banner h3 {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            margin-bottom: 8px;
        }

        .page-banner p {
            color: rgba(255,255,255,0.88);
            font-size: 0.98rem;
        }

        .ustensiles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(290px, 1fr));
            gap: 32px;
            max-width: 1120px;
            margin: 0 auto;
        }

        .ustensile-card {
            background: white;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 8px 28px rgba(15, 23, 42, 0.08);
            border: 1px solid #eef2f7;
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }

        .ustensile-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.13);
        }

        .ustensile-img-box {
            width: 100%;
            height: 255px;
            background: linear-gradient(180deg, #ffffff, #f8fafc);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border-bottom: 1px solid #f1f5f9;
        }

        .ustensile-img {
            width: 88%;
            height: 88%;
            object-fit: contain;
            object-position: center;
            display: block;
            transition: transform 0.25s ease;
        }

        .ustensile-card:hover .ustensile-img {
            transform: scale(1.04);
        }

        .ustensile-body {
            padding: 20px;
        }

        .ustensile-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 14px;
            margin-bottom: 12px;
        }

        .ustensile-name {
            font-size: 1.06rem;
            font-weight: 800;
            color: #1D3557;
            line-height: 1.35;
        }

        .ustensile-modele {
            font-size: 0.86rem;
            color: #64748b;
            margin-top: 4px;
            line-height: 1.45;
        }

        .ustensile-price {
            font-size: 1.28rem;
            font-weight: 800;
            color: #007bff;
            white-space: nowrap;
        }

        .ustensile-rating {
            color: #ffc107;
            font-size: 0.86rem;
            display: flex;
            align-items: center;
            gap: 2px;
            margin: 12px 0 14px;
        }

        .options-label {
            font-size: 0.78rem;
            font-weight: 800;
            color: #334155;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 14px 0 9px;
        }

        .couleurs-row {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }

        .couleur-wrapper {
            position: relative;
            display: inline-flex;
            align-items: center;
        }

        .couleur-btn {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: 3px solid #e2e8f0;
            cursor: pointer;
            transition: 0.2s ease;
        }

        .couleur-btn:hover,
        .couleur-btn.selected {
            border-color: #E63946;
            transform: scale(1.12);
            box-shadow: 0 0 0 4px rgba(230, 57, 70, 0.12);
        }

        .couleur-tooltip {
            display: none;
            position: absolute;
            bottom: 125%;
            left: 50%;
            transform: translateX(-50%);
            background: #1D3557;
            color: white;
            padding: 5px 9px;
            border-radius: 6px;
            font-size: 0.75rem;
            white-space: nowrap;
            z-index: 10;
        }

        .couleur-wrapper:hover .couleur-tooltip {
            display: block;
        }

        .selected-color-text {
            font-size: 0.82rem;
            color: #64748b;
            margin-top: 8px;
            min-height: 18px;
        }

        .btn-ajouter {
            width: 100%;
            padding: 13px 14px;
            background: #1D3557;
            color: white;
            border: none;
            border-radius: 12px;
            font-family: 'DM Sans', sans-serif;
            font-weight: 800;
            font-size: 0.95rem;
            cursor: pointer;
            transition: 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            margin-top: 18px;
        }

        .btn-ajouter:hover {
            background: #E63946;
            transform: translateY(-1px);
        }

        .btn-ajouter:disabled {
            opacity: 0.45;
            cursor: not-allowed;
            transform: none;
        }

        .product-unavailable {
            margin-top: 8px;
            color: #f59e0b;
            font-size: 0.78rem;
            font-weight: 700;
        }

        @media (max-width: 768px) {
            .ustensiles-grid {
                grid-template-columns: 1fr;
            }

            .ustensile-img-box {
                height: 230px;
            }
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
                        <i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['prenom'] ?? 'Profil') ?>
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
            <span style="color:#1D3557;font-weight:600;">Ustensiles de Cuisine</span>
        </div>
    </div>
</div>

<section style="padding:50px 0;">

        <div class="ustensiles-grid">
            <?php foreach ($produits as $index => $p): ?>
                <?php
                    $produit_db = $produits_db_ids[$p['nom']] ?? null;
                    $id_reel = $produit_db['id'] ?? 0;
                    $stock_reel = $produit_db['stock'] ?? 0;
                    $prix_affiche = $produit_db['prix'] ?? $p['prix'];
                    $note_affiche = $produit_db['note'] ?? $p['note'];
                    $avis_affiche = $produit_db['avis'] ?? $p['avis'];
                    $card_id = 'ustensile-' . ($index + 1);
                ?>

                <div class="ustensile-card" id="card-<?= htmlspecialchars($card_id) ?>">

                    <div class="ustensile-img-box">
                        <img src="<?= htmlspecialchars($p['image']) ?>"
                             alt="<?= htmlspecialchars($p['nom']) ?>"
                             class="ustensile-img">
                    </div>

                    <div class="ustensile-body">

                        <div class="ustensile-header">
                            <div>
                                <span class="brand-tag"><?= htmlspecialchars($p['marque']) ?></span>
                                <div class="ustensile-name"><?= htmlspecialchars($p['nom']) ?></div>
                                <div class="ustensile-modele"><?= htmlspecialchars($p['modele']) ?></div>
                            </div>

                            <div class="ustensile-price">
                                <?= number_format($prix_affiche, 3) ?> DT
                            </div>
                        </div>

                        <div class="ustensile-rating">
                            <?= etoiles($note_affiche) ?>
                            <span style="font-size:0.8rem;color:#6c757d;margin-left:4px;">
                                (<?= intval($avis_affiche) ?>)
                            </span>
                        </div>

                        <div class="options-label">Couleur</div>

                        <div class="couleurs-row" id="couleurs-<?= htmlspecialchars($card_id) ?>">
                            <?php foreach ($p['couleurs'] as $idx => $c): ?>
                                <div class="couleur-wrapper">
                                    <button type="button"
                                            class="couleur-btn <?= $idx === 0 ? 'selected' : '' ?>"
                                            style="background:<?= htmlspecialchars($c['hex']) ?>;<?= $c['hex'] === '#f9fafb' ? 'border-color:#dee2e6;' : '' ?>"
                                            onclick="selectionnerCouleur('<?= htmlspecialchars($card_id) ?>', '<?= htmlspecialchars($c['nom'], ENT_QUOTES) ?>', this)"
                                            title="<?= htmlspecialchars($c['nom']) ?>">
                                    </button>
                                    <span class="couleur-tooltip"><?= htmlspecialchars($c['nom']) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div id="couleur-selected-<?= htmlspecialchars($card_id) ?>"
                             class="selected-color-text">
                            Couleur sélectionnée : <?= htmlspecialchars($p['couleurs'][0]['nom']) ?>
                        </div>

                        <?php if ($id_reel > 0 && $stock_reel > 0): ?>
                            <button class="btn-ajouter btn-cart-icon"
                                    type="button"
                                    data-id="<?= intval($id_reel) ?>">
                                <i class="fas fa-shopping-cart"></i>
                                Ajouter au panier
                            </button>
                        <?php else: ?>
                            <button class="btn-ajouter"
                                    type="button"
                                    disabled
                                    title="Produit non disponible">
                                <i class="fas fa-ban"></i>
                                Produit non disponible
                            </button>

                            <p class="product-unavailable">
                                Produit à ajouter dans la base pour activer le panier.
                            </p>
                        <?php endif; ?>

                    </div>
                </div>
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
            <p>&copy; <?= date('Y') ?> NovaStore. Tous droits réservés.</p>
        </div>
    </div>
</footer>

<script>
    window.BASE_URL = '../';

    function selectionnerCouleur(produitId, couleur, btn) {
        document.querySelectorAll(`#couleurs-${produitId} .couleur-btn`).forEach(b => {
            b.classList.remove('selected');
        });

        btn.classList.add('selected');

        const selectedText = document.getElementById(`couleur-selected-${produitId}`);
        if (selectedText) {
            selectedText.textContent = 'Couleur sélectionnée : ' + couleur;
        }
    }

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

    function toggleTheme() {
        const body = document.body;
        const btn = document.getElementById('theme-toggle');

        body.classList.toggle('dark');

        if (body.classList.contains('dark')) {
            if (btn) btn.textContent = '☀️';
            localStorage.setItem('theme', 'dark');
        } else {
            if (btn) btn.textContent = '🌙';
            localStorage.setItem('theme', 'light');
        }
    }

    if (localStorage.getItem('theme') === 'dark') {
        document.body.classList.add('dark');

        const btn = document.getElementById('theme-toggle');
        if (btn) btn.textContent = '☀️';
    }
</script>

<script src="../main.js"></script>

</body>
</html>