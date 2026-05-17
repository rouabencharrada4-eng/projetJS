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


$stmt = $pdo->prepare('
    SELECT p.*, c.nom AS categorie, c.slug
    FROM produits p
    JOIN categories c ON c.id = p.categorie_id
    WHERE p.actif = 1
      AND (
            c.slug = "alimentaire"
            OR c.slug = "fruits"
            OR c.slug = "legumes"
            OR c.slug = "laitiers"
            OR c.slug = "produits-laitiers"
            OR c.slug = "boissons"
            OR c.slug = "oeufs"
            OR c.slug = "petit-dejeuner"
            OR c.slug = "petitdej"
            OR p.categorie_id = 1
          )
    ORDER BY p.nom ASC
');
$stmt->execute();
$produits_db = $stmt->fetchAll();


$sections = [
    'fruits' => [
        'titre' => 'Fruits',
        'icon'  => 'fas fa-apple-alt',
        'color' => '#10b981',
        'produits' => []
    ],
    'legumes' => [
        'titre' => 'Légumes',
        'icon'  => 'fas fa-leaf',
        'color' => '#3b82f6',
        'produits' => []
    ],
    'laitiers' => [
        'titre' => 'Produits laitiers',
        'icon'  => 'fas fa-glass-whiskey',
        'color' => '#8b5cf6',
        'produits' => []
    ],
    'boissons' => [
        'titre' => 'Boissons',
        'icon'  => 'fas fa-cocktail',
        'color' => '#f59e0b',
        'produits' => []
    ],
    'oeufs' => [
        'titre' => 'Oeufs',
        'icon'  => 'fas fa-egg',
        'color' => '#E63946',
        'produits' => []
    ],
    'petitdej' => [
        'titre' => 'Petit déjeuner',
        'icon'  => 'fas fa-bread-slice',
        'color' => '#06b6d4',
        'produits' => []
    ],
    'autres' => [
        'titre' => 'Autres produits alimentaires',
        'icon'  => 'fas fa-shopping-basket',
        'color' => '#64748b',
        'produits' => []
    ],
];



function normaliserTexte($texte) {
    $texte = mb_strtolower(trim((string)$texte), 'UTF-8');

    $replacements = [
        'œ' => 'oe',
        'Œ' => 'oe',
        'é' => 'e',
        'è' => 'e',
        'ê' => 'e',
        'ë' => 'e',
        'à' => 'a',
        'â' => 'a',
        'ä' => 'a',
        'î' => 'i',
        'ï' => 'i',
        'ô' => 'o',
        'ö' => 'o',
        'ù' => 'u',
        'û' => 'u',
        'ü' => 'u',
        'ç' => 'c',
        '’' => "'",
        '‘' => "'",
        '`' => "'",
    ];

    $texte = strtr($texte, $replacements);
    $texte = preg_replace('/\s+/', ' ', $texte);

    return trim($texte);
}

function detecterSectionProduit($p) {
    $nom = normaliserTexte($p['nom'] ?? '');
    $marque = normaliserTexte($p['marque'] ?? '');
    $modele = normaliserTexte($p['modele'] ?? '');
    $categorie = normaliserTexte($p['categorie'] ?? '');
    $slug = normaliserTexte($p['slug'] ?? '');

    $texte = $nom . ' ' . $marque . ' ' . $modele . ' ' . $categorie . ' ' . $slug;

    if (
        str_contains($texte, 'pomme de terre') ||
        str_contains($texte, 'pommes de terre') ||
        str_contains($texte, 'carotte') ||
        str_contains($texte, 'carottes') ||
        str_contains($texte, 'oignon') ||
        str_contains($texte, 'oignons') ||
        str_contains($texte, 'legume') ||
        str_contains($texte, 'legumes')
    ) {
        return 'legumes';
    }

    if (
        str_contains($texte, 'oeuf') ||
        str_contains($texte, 'oeufs') ||
        str_contains($texte, "d'oeuf") ||
        str_contains($texte, "d'oeufs") ||
        str_contains($texte, 'plateau') ||
        str_contains($texte, 'egg')
    ) {
        return 'oeufs';
    }

    if (
        str_contains($texte, 'banane') ||
        str_contains($texte, 'bananes') ||
        str_contains($texte, 'kiwi') ||
        str_contains($texte, 'pomme') ||
        str_contains($texte, 'pommes') ||
        str_contains($texte, 'fruit') ||
        str_contains($texte, 'fruits')
    ) {
        return 'fruits';
    }

    if (
        str_contains($texte, 'lait') ||
        str_contains($texte, 'yaghourt') ||
        str_contains($texte, 'yaourt') ||
        str_contains($texte, 'yogourt') ||
        str_contains($texte, 'fromage') ||
        str_contains($texte, 'laitier') ||
        str_contains($texte, 'laitiers')
    ) {
        return 'laitiers';
    }

    if (
        str_contains($texte, 'eau gazeuse') ||
        str_contains($texte, 'eau minerale') ||
        str_contains($texte, 'eau minérale') ||
        str_contains($texte, 'jus') ||
        str_contains($texte, 'boisson') ||
        str_contains($texte, 'boissons') ||
        str_contains($texte, 'boga') ||
        str_contains($texte, 'fourat')
    ) {
        return 'boissons';
    }

    if (
        str_contains($texte, 'pain') ||
        str_contains($texte, 'toast') ||
        str_contains($texte, 'pain de mie') ||
        str_contains($texte, 'confiture') ||
        str_contains($texte, 'beurre') ||
        str_contains($texte, 'petit dejeuner') ||
        str_contains($texte, 'petit-dejeuner')
    ) {
        return 'petitdej';
    }

    return 'autres';
}

function cleDoublonProduit($p) {
    $nom = normaliserTexte($p['nom'] ?? '');
    $modele = normaliserTexte($p['modele'] ?? '');

    $texte = $nom . ' ' . $modele;

    if (
        str_contains($texte, 'toast') ||
        str_contains($texte, 'pain de mie')
    ) {
        return 'produit_toast';
    }

    if (
        str_contains($texte, 'plateau') &&
        (
            str_contains($texte, 'oeuf') ||
            str_contains($texte, 'oeufs')
        )
    ) {
        return 'produit_oeufs_plateau';
    }

    if (
        str_contains($texte, 'pomme de terre') ||
        str_contains($texte, 'pommes de terre')
    ) {
        return 'produit_pommes_de_terre';
    }

    return 'nom_' . $nom;
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


$produits_deja_affiches = [];

foreach ($produits_db as $p) {
    $id = intval($p['id'] ?? 0);
    $cle_doublon = cleDoublonProduit($p);

    if ($id > 0 && isset($produits_deja_affiches['id_' . $id])) {
        continue;
    }

    if ($cle_doublon !== '' && isset($produits_deja_affiches[$cle_doublon])) {
        continue;
    }

    if ($id > 0) {
        $produits_deja_affiches['id_' . $id] = true;
    }

    if ($cle_doublon !== '') {
        $produits_deja_affiches[$cle_doublon] = true;
    }

    $section_key = detecterSectionProduit($p);

    if (!isset($sections[$section_key])) {
        $section_key = 'autres';
    }

    $sections[$section_key]['produits'][] = $p;
}



function carteProduit($p, $wishlist_ids) {
    $id = intval($p['id'] ?? 0);
    $stock = intval($p['stock'] ?? 0);

    $wishlist_ids_int = array_map('intval', $wishlist_ids);
    $in_wishlist = in_array($id, $wishlist_ids_int);

    $prix_parts = explode('.', number_format(floatval($p['prix'] ?? 0), 3, '.', ''));

    $image = $p['image'] ?? 'images/placeholder.jpg';

    if (!str_starts_with($image, '../')) {
        $image = '../' . $image;
    }

    ob_start();
    ?>
    <div class="product-card">

        <button class="wishlist-btn <?= $in_wishlist ? 'active' : '' ?>"
                type="button"
                data-id="<?= $id ?>">
            <i class="<?= $in_wishlist ? 'fas' : 'far' ?> fa-heart"></i>
        </button>

        <div class="product-img-box">
            <img src="<?= htmlspecialchars($image) ?>"
                 alt="<?= htmlspecialchars($p['nom'] ?? 'Produit') ?>">
        </div>

        <div class="product-info">

            <div class="product-footer-price">
                <div class="price-container">
                    <span class="price-main"><?= htmlspecialchars($prix_parts[0]) ?></span>
                    <span class="price-currency">DT</span>
                    <span class="price-cents"><?= htmlspecialchars($prix_parts[1] ?? '000') ?></span>
                </div>

                <?php if ($id > 0 && $stock > 0): ?>
                    <button class="btn-cart-icon"
                            type="button"
                            data-id="<?= $id ?>">
                        <i class="fas fa-shopping-cart"></i>
                    </button>
                <?php else: ?>
                    <button class="btn-cart-icon"
                            type="button"
                            disabled
                            title="Rupture de stock">
                        <i class="fas fa-ban"></i>
                    </button>
                <?php endif; ?>
            </div>

            <?php if (!empty($p['marque'])): ?>
                <span class="brand-tag"><?= htmlspecialchars($p['marque']) ?></span>
            <?php endif; ?>

            <h3 class="product-name"><?= htmlspecialchars($p['nom'] ?? '') ?></h3>

            <?php if (!empty($p['modele'])): ?>
                <p class="product-model"><?= htmlspecialchars($p['modele']) ?></p>
            <?php endif; ?>

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
    <title>Alimentaire – NovaStore</title>

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
            background: linear-gradient(135deg, #10b981, #059669);
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
            color: rgba(255,255,255,0.85);
        }

        .sections-nav {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: center;
            margin-bottom: 48px;
        }

        .section-nav-btn {
            padding: 10px 20px;
            border-radius: 25px;
            border: 2px solid #e9ecef;
            background: white;
            cursor: pointer;
            font-family: 'DM Sans', sans-serif;
            font-weight: 600;
            font-size: 0.88rem;
            color: #374151;
            transition: 0.2s;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .section-nav-btn:hover {
            border-color: #E63946;
            color: #E63946;
            background: #fce7f3;
        }

        .section-label {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 48px 0 24px;
            padding-bottom: 12px;
            border-bottom: 3px solid #f1f5f9;
        }

        .section-label-icon {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .section-label h3 {
            font-family: 'Playfair Display', serif;
            font-size: 1.4rem;
            color: #1D3557;
            margin: 0;
        }

        .section-label span {
            font-size: 0.85rem;
            color: #6c757d;
            margin-left: auto;
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
            <span style="color:#1D3557;font-weight:600;">Alimentaire</span>
        </div>
    </div>
</div>

<section style="padding:50px 0;">

   <div class="sections-nav">
            <a href="#fruits" class="section-nav-btn">
                <i class="fas fa-apple-alt"></i> Fruits
            </a>

            <a href="#legumes" class="section-nav-btn">
                <i class="fas fa-leaf"></i> Légumes
            </a>

            <a href="#laitiers" class="section-nav-btn">
                <i class="fas fa-glass-whiskey"></i> Produits laitiers
            </a>

            <a href="#boissons" class="section-nav-btn">
                <i class="fas fa-cocktail"></i> Boissons
            </a>

            <a href="#oeufs" class="section-nav-btn">
                <i class="fas fa-egg"></i> Oeufs
            </a>

            <a href="#petitdej" class="section-nav-btn">
                <i class="fas fa-bread-slice"></i> Petit déjeuner
            </a>
        </div>

        <?php foreach ($sections as $section_id => $section): ?>
            <?php if (empty($section['produits'])) continue; ?>

            <div id="<?= htmlspecialchars($section_id) ?>">

                <div class="section-label">
                    <div class="section-label-icon" style="background:<?= htmlspecialchars($section['color']) ?>;">
                        <i class="<?= htmlspecialchars($section['icon']) ?>"></i>
                    </div>

                    <h3><?= htmlspecialchars($section['titre']) ?></h3>

                    <span>
                        <?= count($section['produits']) ?>
                        produit<?= count($section['produits']) > 1 ? 's' : '' ?>
                    </span>
                </div>

                <div class="products-grid">
                    <?php foreach ($section['produits'] as $p): ?>
                        <?= carteProduit($p, $wishlist_ids) ?>
                    <?php endforeach; ?>
                </div>

            </div>
        <?php endforeach; ?>

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